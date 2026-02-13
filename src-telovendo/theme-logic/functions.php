<?php
// ============================================
// INYECCIÓN MASIVA DE DATOS - Función Core
// ============================================
function ejecutar_poblacion_cuba() {
    // Ruta absoluta al archivo JSON - Intentar múltiples ubicaciones
    $json_path = null;
    
    // Opción 1: WP_CONTENT_DIR (ruta absoluta)
    $ruta1 = WP_CONTENT_DIR . '/src-telovendo/custom-modules/municipios-cuba.json';
    if (file_exists($ruta1)) {
        $json_path = $ruta1;
    }
    
    // Opción 2: Desde el tema hijo (ruta absoluta)
    if (!$json_path) {
        $ruta2 = get_stylesheet_directory() . '/../../src-telovendo/custom-modules/municipios-cuba.json';
        if (file_exists($ruta2)) {
            $json_path = realpath($ruta2); // Convertir a ruta absoluta
        }
    }
    
    // Opción 3: Desde el tema padre (ruta absoluta)
    if (!$json_path) {
        $ruta3 = get_template_directory() . '/../../src-telovendo/custom-modules/municipios-cuba.json';
        if (file_exists($ruta3)) {
            $json_path = realpath($ruta3); // Convertir a ruta absoluta
        }
    }
    
    // Opción 4: Desde ABSPATH (raíz de WordPress)
    if (!$json_path) {
        $ruta4 = ABSPATH . 'src-telovendo/custom-modules/municipios-cuba.json';
        if (file_exists($ruta4)) {
            $json_path = realpath($ruta4); // Convertir a ruta absoluta
        }
    }
    
    if (!$json_path || !file_exists($json_path)) {
        return false;
    }
    
    // Leer y decodificar JSON
    $data = json_decode(file_get_contents($json_path), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    
    // Lógica de inserción jerárquica: Provincia > Municipio > Barrio
    $taxonomia = 'ubicacion_cubana';
    
    // Verificar que la taxonomía existe
    if (!taxonomy_exists($taxonomia)) {
        return false;
    }
    
    // Contadores
    $provincias_insertadas = 0;
    $municipios_insertados = 0;
    $barrios_insertados = 0;
    
    // Procesar datos del JSON
    foreach ($data['provincias'] as $provincia_nombre => $provincia_data) {
        // Insertar Provincia (nivel 1)
        $provincia_existente = term_exists($provincia_nombre, $taxonomia, 0);
        if ($provincia_existente) {
            $provincia_id = $provincia_existente['term_id'];
        } else {
            $resultado = wp_insert_term($provincia_nombre, $taxonomia, array('parent' => 0));
            if (!is_wp_error($resultado)) {
                $provincia_id = $resultado['term_id'];
                $provincias_insertadas++;
            } else {
                continue;
            }
        }
        
        // Iterar sobre municipios de la provincia
        if (isset($provincia_data['municipios'])) {
            foreach ($provincia_data['municipios'] as $municipio_nombre => $municipio_data) {
                // Insertar Municipio (nivel 2, hijo de Provincia)
                $municipio_existente = term_exists($municipio_nombre, $taxonomia, $provincia_id);
                if ($municipio_existente) {
                    $municipio_id = $municipio_existente['term_id'];
                } else {
                    $resultado = wp_insert_term($municipio_nombre, $taxonomia, array('parent' => $provincia_id));
                    if (!is_wp_error($resultado)) {
                        $municipio_id = $resultado['term_id'];
                        $municipios_insertados++;
                    } else {
                        continue;
                    }
                }
                
                // Iterar sobre barrios del municipio
                if (isset($municipio_data['barrios']) && is_array($municipio_data['barrios'])) {
                    foreach ($municipio_data['barrios'] as $barrio_nombre) {
                        // Insertar Barrio/Reparto (nivel 3, hijo de Municipio)
                        $barrio_existente = term_exists($barrio_nombre, $taxonomia, $municipio_id);
                        if (!$barrio_existente) {
                            $resultado = wp_insert_term($barrio_nombre, $taxonomia, array('parent' => $municipio_id));
                            if (!is_wp_error($resultado)) {
                                $barrios_insertados++;
                            }
                        }
                    }
                }
            }
        }
    }
    
    return true;
}

// ============================================
// INYECCIÓN MASIVA DE DATOS - Hook Seguro
// Ejecutar visitando: ?ejecutar_poblacion=1
// ============================================
function inyectar_lugares_cuba() {
    if (isset($_GET['ejecutar_poblacion']) && $_GET['ejecutar_poblacion'] == '1') {
        // Verificar permisos de administrador
        if (!current_user_can('manage_options')) {
            wp_die('Error: No tienes permisos para ejecutar este script.');
        }
        
        if (ejecutar_poblacion_cuba()) {
            // Mensaje de éxito - único contenido en pantalla
            wp_die('IGNICIÓN EXITOSA: Cuba está poblada.');
        } else {
            wp_die('Error: No se pudo ejecutar la población de datos.');
        }
    }
}
add_action('after_setup_theme', 'inyectar_lugares_cuba', 1);

// ============================================
// FUERZA BRUTA: Inyectar al cargar cualquier página
// ============================================
add_action('wp_loaded', function() {
    if (!get_option('cuba_poblada_v1')) {
        // Aquí va la lógica de inyectar las 15 provincias y municipios
        if (ejecutar_poblacion_cuba()) {
            update_option('cuba_poblada_v1', true);
            wp_die('SISTEMA POBLADO: Ya puede entrar al panel y ver las provincias.');
        }
    }
}, 1);

// Cargar el motor de listado de propiedades
require_once get_stylesheet_directory() . '/../../src-telovendo/property-listing/property-engine.php';

// Cargar el motor de datos RAG para Jicotea-Genio
require_once get_stylesheet_directory() . '/src-telovendo/custom-modules/jicotea-data-engine.php';

// Cargar el motor GPS para localización de direcciones
require_once get_stylesheet_directory() . '/src-telovendo/custom-modules/jicotea-gps-engine.php';

// Cargar el motor de búsqueda jerárquica
require_once get_stylesheet_directory() . '/src-telovendo/custom-modules/jicotea-search-engine.php';

// Motor de visualización Te Lo Vendo Cuba
add_action('property_price_display', function() {
    $precio = get_field('precio_usd');
    if($precio) {
        echo '<div class="glass-price">$' . number_format($precio) . ' USD</div>';
    }
});

// Registrar menú de navegación
function telovendo_register_menus() {
    register_nav_menus(array(
        'primary' => 'Menú Principal'
    ));
}
add_action('after_setup_theme', 'telovendo_register_menus');

// Cargar estilos de plantillas
function telovendo_enqueue_template_styles() {
    wp_enqueue_style('telovendo-templates', get_stylesheet_directory_uri() . '/src-telovendo/assets/css/theme-templates.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'telovendo_enqueue_template_styles');

// Cargar estilo-cuba.css con ALTA PRIORIDAD para sobreescribir Astra
function telovendo_enqueue_cuba_styles() {
    // Cargar después de todos los estilos (incluido Astra) con prioridad 999
    wp_enqueue_style(
        'estilo-cuba', 
        get_stylesheet_directory_uri() . '/src-telovendo/theme-logic/estilo-cuba.css', 
        array(), // Sin dependencias para que se cargue al final
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'telovendo_enqueue_cuba_styles', 999);

// ============================================
// SHORTCODE: [jicotea_ia] - Chatbot Flotante
// ============================================
function jicotea_ia_shortcode($atts) {
    // Encolar estilos del chatbot
    wp_enqueue_style(
        'jicotea-chat-css',
        get_stylesheet_directory_uri() . '/src-telovendo/assets/css/jicotea-chat.css',
        array(),
        '1.0.0'
    );
    
    // Encolar scripts del chatbot
    wp_enqueue_script(
        'jicotea-autocomplete',
        get_stylesheet_directory_uri() . '/src-telovendo/assets/js/jicotea-autocomplete.js',
        array(),
        '1.0.0',
        true
    );
    
    wp_enqueue_script(
        'jicotea-chat',
        get_stylesheet_directory_uri() . '/src-telovendo/assets/js/jicotea-chat.js',
        array('jicotea-autocomplete'),
        '1.0.0',
        true
    );
    
    // Retornar el HTML del anchor del chatbot
    $output = '<div id="jicotea-ia-anchor">';
    $output .= '<img src="' . esc_url(get_stylesheet_directory_uri() . '/chatbot jicotea.png') . '" class="jicotea-genio-ia" alt="Jicotea-Genio IA" />';
    $output .= '</div>';
    
    return $output;
}
add_shortcode('jicotea_ia', 'jicotea_ia_shortcode');

