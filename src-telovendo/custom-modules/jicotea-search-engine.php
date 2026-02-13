<?php
/**
 * Jicotea Search Engine: Búsqueda Jerárquica y Fallback
 * Sistema de filtrado por Provincia > Municipio > Barrio/Reparto
 */

// Endpoint REST API para búsqueda jerárquica con fallback
function jicotea_register_search_api() {
    register_rest_route('jicotea/v1', '/search', array(
        'methods' => 'GET',
        'callback' => 'jicotea_hierarchical_search',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'jicotea_register_search_api');

// Función de búsqueda jerárquica con fallback
function jicotea_hierarchical_search($request) {
    $provincia = sanitize_text_field($request->get_param('provincia'));
    $municipio = sanitize_text_field($request->get_param('municipio'));
    $barrio = sanitize_text_field($request->get_param('barrio'));
    $precio_min = $request->get_param('precio_min');
    $precio_max = $request->get_param('precio_max');
    
    $args = array(
        'post_type' => 'propiedad',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    // Lógica de Fallback de Búsqueda: Si no hay Barrio, buscar por Municipio; si no hay Municipio, buscar por Provincia
    $tax_query = array();
    
    if ($barrio) {
        // Búsqueda específica por barrio
        $tax_query[] = array(
            'taxonomy' => 'ubicacion_cubana',
            'field' => 'name',
            'terms' => $barrio,
            'operator' => 'IN'
        );
    } elseif ($municipio) {
        // Fallback: Búsqueda por municipio (todos los barrios del municipio)
        $tax_query[] = array(
            'taxonomy' => 'ubicacion_cubana',
            'field' => 'name',
            'terms' => $municipio,
            'operator' => 'IN',
            'include_children' => true // Incluir todos los barrios hijos
        );
    } elseif ($provincia) {
        // Fallback: Búsqueda por provincia (todos los municipios y barrios)
        $tax_query[] = array(
            'taxonomy' => 'ubicacion_cubana',
            'field' => 'name',
            'terms' => $provincia,
            'operator' => 'IN',
            'include_children' => true // Incluir todos los municipios y barrios hijos
        );
    }
    
    if (!empty($tax_query)) {
        $args['tax_query'] = $tax_query;
    }
    
    // Filtro por rango de precio
    if ($precio_min || $precio_max) {
        $precio_query = array(
            'key' => 'precio_usd',
            'type' => 'NUMERIC'
        );
        if ($precio_min && $precio_max) {
            $precio_query['value'] = array($precio_min, $precio_max);
            $precio_query['compare'] = 'BETWEEN';
        } elseif ($precio_min) {
            $precio_query['value'] = $precio_min;
            $precio_query['compare'] = '>=';
        } else {
            $precio_query['value'] = $precio_max;
            $precio_query['compare'] = '<=';
        }
        $args['meta_query'][] = $precio_query;
    }
    
    $query = new WP_Query($args);
    $properties = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $ubicaciones = wp_get_post_terms(get_the_ID(), 'ubicacion_cubana', array('fields' => 'all'));
            
            $properties[] = array(
                'id' => get_the_ID(),
                'titulo' => get_the_title(),
                'precio_usd' => get_field('precio_usd'),
                'municipio_cuba' => get_field('municipio_cuba'),
                'provincia_cuba' => get_field('provincia_cuba'),
                'ubicaciones' => array_map(function($term) {
                    return array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'parent' => $term->parent,
                        'taxonomy' => $term->taxonomy
                    );
                }, $ubicaciones),
                'tipo_operacion' => wp_get_post_terms(get_the_ID(), 'tipo_operacion', array('fields' => 'names')),
                'link' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium')
            );
        }
    }
    
    wp_reset_postdata();
    
    return new WP_REST_Response(array(
        'success' => true,
        'count' => count($properties),
        'properties' => $properties,
        'filters_applied' => array(
            'provincia' => $provincia,
            'municipio' => $municipio,
            'barrio' => $barrio,
            'precio_min' => $precio_min,
            'precio_max' => $precio_max
        )
    ), 200);
}

// Endpoint para obtener estructura jerárquica de ubicaciones
function jicotea_register_locations_api() {
    register_rest_route('jicotea/v1', '/locations', array(
        'methods' => 'GET',
        'callback' => 'jicotea_get_locations_hierarchy',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'jicotea_register_locations_api');

// Obtener jerarquía completa de ubicaciones
function jicotea_get_locations_hierarchy($request) {
    $taxonomy = 'ubicacion_cubana';
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'hierarchical' => true
    ));
    
    // Organizar en jerarquía
    $hierarchy = array();
    foreach ($terms as $term) {
        if ($term->parent == 0) {
            // Es una provincia
            $hierarchy[$term->term_id] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'type' => 'provincia',
                'children' => array()
            );
        }
    }
    
    // Agregar municipios
    foreach ($terms as $term) {
        if ($term->parent != 0 && isset($hierarchy[$term->parent])) {
            $hierarchy[$term->parent]['children'][$term->term_id] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'type' => 'municipio',
                'parent' => $term->parent,
                'children' => array()
            );
        }
    }
    
    // Agregar barrios
    foreach ($terms as $term) {
        foreach ($hierarchy as $prov_id => $provincia) {
            if (isset($provincia['children'][$term->parent])) {
                $hierarchy[$prov_id]['children'][$term->parent]['children'][$term->term_id] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'type' => 'barrio',
                    'parent' => $term->parent
                );
            }
        }
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'hierarchy' => array_values($hierarchy)
    ), 200);
}

