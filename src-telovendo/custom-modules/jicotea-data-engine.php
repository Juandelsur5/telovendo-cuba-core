<?php
/**
 * Motor de Datos RAG para Jicotea-Genio
 * Indexa propiedades desde ACF y Custom Post Types
 */

// Endpoint REST API para obtener propiedades indexadas
function jicotea_get_properties_api() {
    register_rest_route('jicotea/v1', '/properties', array(
        'methods' => 'GET',
        'callback' => 'jicotea_fetch_properties_data',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'jicotea_get_properties_api');

// Función para extraer datos de propiedades desde ACF
function jicotea_fetch_properties_data($request) {
    $municipio = $request->get_param('municipio');
    $precio_min = $request->get_param('precio_min');
    $precio_max = $request->get_param('precio_max');
    
    $args = array(
        'post_type' => 'propiedad',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    // Filtro por municipio si existe
    if ($municipio) {
        $args['meta_query'][] = array(
            'key' => 'municipio_cuba',
            'value' => $municipio,
            'compare' => 'LIKE'
        );
    }
    
    // Filtro por rango de precio
    if ($precio_min || $precio_max) {
        $precio_query = array(
            'key' => 'precio_usd',
            'type' => 'NUMERIC'
        );
        if ($precio_min) $precio_query['value'] = $precio_min;
        if ($precio_max) $precio_query['value'] = array($precio_min ?: 0, $precio_max);
        if ($precio_min && $precio_max) {
            $precio_query['compare'] = 'BETWEEN';
        } else {
            $precio_query['compare'] = $precio_min ? '>=' : '<=';
        }
        $args['meta_query'][] = $precio_query;
    }
    
    $query = new WP_Query($args);
    $properties = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $properties[] = array(
                'id' => get_the_ID(),
                'titulo' => get_the_title(),
                'precio_usd' => get_field('precio_usd'),
                'municipio_cuba' => get_field('municipio_cuba'),
                'provincia' => get_field('provincia_cuba'),
                'tipo_operacion' => wp_get_post_terms(get_the_ID(), 'tipo_operacion', array('fields' => 'names')),
                'link' => get_permalink(),
                'thumbnail' => get_the_post_thumbnail_url(get_the_ID(), 'medium')
            );
        }
    }
    
    wp_reset_postdata();
    
    return new WP_REST_Response($properties, 200);
}

// Función helper para obtener todas las propiedades indexadas (para RAG)
function jicotea_get_all_properties_for_rag() {
    $args = array(
        'post_type' => 'propiedad',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $query = new WP_Query($args);
    $indexed_data = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $indexed_data[] = array(
                'titulo' => get_the_title(),
                'precio_usd' => get_field('precio_usd'),
                'municipio' => get_field('municipio_cuba'),
                'provincia' => get_field('provincia_cuba'),
                'descripcion' => get_the_excerpt()
            );
        }
    }
    
    wp_reset_postdata();
    return $indexed_data;
}

// Endpoint REST API para obtener datos de municipios (acceso al JSON)
function jicotea_register_municipios_api() {
    register_rest_route('jicotea/v1', '/municipios', array(
        'methods' => 'GET',
        'callback' => 'jicotea_get_municipios_data',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'jicotea_register_municipios_api');

// Función para obtener datos de municipios desde el JSON
function jicotea_get_municipios_data($request) {
    $json_path = get_stylesheet_directory() . '/../../src-telovendo/custom-modules/municipios-cuba.json';
    
    // Intentar ruta alternativa si la primera no funciona
    if (!file_exists($json_path)) {
        $json_path = get_template_directory() . '/../../src-telovendo/custom-modules/municipios-cuba.json';
    }
    
    if (!file_exists($json_path)) {
        return new WP_Error('file_not_found', 'municipios-cuba.json no encontrado', array('status' => 404));
    }
    
    $json_content = file_get_contents($json_path);
    $data = json_decode($json_content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Error al decodificar JSON', array('status' => 500));
    }
    
    return new WP_REST_Response($data, 200);
}

