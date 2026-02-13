<?php
// Registro del tipo de contenido "Propiedades"
function telovendo_register_property_type() {
    $labels = array(
        'name' => 'Propiedades Cuba',
        'singular_name' => 'Propiedad'
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-admin-home',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest' => true, // Importante para editores modernos
    );
    register_post_type( 'propiedad', $args );
}
add_action( 'init', 'telovendo_register_property_type' );

// Crear categorías: Venta, Renta, Permuta
function telovendo_register_taxonomies() {
    register_taxonomy( 'tipo_operacion', 'propiedad', array(
        'label' => 'Tipo de Operación',
        'hierarchical' => true,
        'show_in_rest' => true,
    ));
    
    // Jerarquía de Taxonomías: Ubicación Cubana (Provincia > Municipio > Barrio/Reparto)
    register_taxonomy( 'ubicacion_cubana', 'propiedad', array(
        'label' => 'Ubicación Cubana',
        'hierarchical' => true,
        'show_in_rest' => true,
        'rewrite' => array('slug' => 'ubicacion'),
        'labels' => array(
            'name' => 'Ubicaciones',
            'singular_name' => 'Ubicación',
            'menu_name' => 'Ubicaciones Cuba',
            'all_items' => 'Todas las Ubicaciones',
            'parent_item' => 'Ubicación Padre',
            'parent_item_colon' => 'Ubicación Padre:',
            'new_item_name' => 'Nueva Ubicación',
            'add_new_item' => 'Añadir Nueva Ubicación',
            'edit_item' => 'Editar Ubicación',
            'update_item' => 'Actualizar Ubicación',
            'view_item' => 'Ver Ubicación',
            'separate_items_with_commas' => 'Separar ubicaciones con comas',
            'add_or_remove_items' => 'Añadir o quitar ubicaciones',
            'choose_from_most_used' => 'Elegir de las más usadas',
            'popular_items' => 'Ubicaciones Populares',
            'search_items' => 'Buscar Ubicaciones',
            'not_found' => 'No encontrado',
            'no_terms' => 'No hay ubicaciones',
            'items_list' => 'Lista de Ubicaciones',
            'items_list_navigation' => 'Navegación de lista de ubicaciones',
        ),
    ));
}
add_action( 'init', 'telovendo_register_taxonomies' );

