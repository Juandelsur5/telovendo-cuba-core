<?php
/**
 * Front to the WordPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells WordPress to load the theme.
 *
 * @package WordPress
 */

/**
 * Tells WordPress to load the WordPress theme and output it.
 *
 * @var bool
 */
define( 'WP_USE_THEMES', true );

// ============================================
// BYPASS DIRECTO: Inyección antes de que Astra tome control
// Cargar WordPress sin renderizar tema, ejecutar inyección, luego continuar
// ============================================
if (!get_option('cuba_poblada_v1')) {
    // Cargar WordPress core sin renderizar el tema
    require_once( __DIR__ . '/wp-load.php' );
    
    // Función de inyección inline (mismo código que ejecutar_poblacion_cuba)
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
            $json_path = realpath($ruta2);
        }
    }
    
    // Opción 3: Desde el tema padre (ruta absoluta)
    if (!$json_path) {
        $ruta3 = get_template_directory() . '/../../src-telovendo/custom-modules/municipios-cuba.json';
        if (file_exists($ruta3)) {
            $json_path = realpath($ruta3);
        }
    }
    
    // Opción 4: Desde ABSPATH (raíz de WordPress)
    if (!$json_path) {
        $ruta4 = ABSPATH . 'src-telovendo/custom-modules/municipios-cuba.json';
        if (file_exists($ruta4)) {
            $json_path = realpath($ruta4);
        }
    }
    
    if ($json_path && file_exists($json_path)) {
        // Leer y decodificar JSON
        $data = json_decode(file_get_contents($json_path), true);
        
        if (json_last_error() === JSON_ERROR_NONE && taxonomy_exists('ubicacion_cubana')) {
            // Procesar datos del JSON
            foreach ($data['provincias'] as $provincia_nombre => $provincia_data) {
                // Insertar Provincia (nivel 1)
                $provincia_existente = term_exists($provincia_nombre, 'ubicacion_cubana', 0);
                if ($provincia_existente) {
                    $provincia_id = $provincia_existente['term_id'];
                } else {
                    $resultado = wp_insert_term($provincia_nombre, 'ubicacion_cubana', array('parent' => 0));
                    if (!is_wp_error($resultado)) {
                        $provincia_id = $resultado['term_id'];
                    } else {
                        continue;
                    }
                }
                
                // Iterar sobre municipios de la provincia
                if (isset($provincia_data['municipios'])) {
                    foreach ($provincia_data['municipios'] as $municipio_nombre => $municipio_data) {
                        // Insertar Municipio (nivel 2, hijo de Provincia)
                        $municipio_existente = term_exists($municipio_nombre, 'ubicacion_cubana', $provincia_id);
                        if ($municipio_existente) {
                            $municipio_id = $municipio_existente['term_id'];
                        } else {
                            $resultado = wp_insert_term($municipio_nombre, 'ubicacion_cubana', array('parent' => $provincia_id));
                            if (!is_wp_error($resultado)) {
                                $municipio_id = $resultado['term_id'];
                            } else {
                                continue;
                            }
                        }
                        
                        // Iterar sobre barrios del municipio
                        if (isset($municipio_data['barrios']) && is_array($municipio_data['barrios'])) {
                            foreach ($municipio_data['barrios'] as $barrio_nombre) {
                                // Insertar Barrio/Reparto (nivel 3, hijo de Municipio)
                                $barrio_existente = term_exists($barrio_nombre, 'ubicacion_cubana', $municipio_id);
                                if (!$barrio_existente) {
                                    wp_insert_term($barrio_nombre, 'ubicacion_cubana', array('parent' => $municipio_id));
                                }
                            }
                        }
                    }
                }
            }
            
            // Marcar como poblado y mostrar mensaje
            update_option('cuba_poblada_v1', true);
            wp_die('SISTEMA POBLADO: Ya puede entrar al panel y ver las provincias.');
        }
    }
}

/** Loads the WordPress Environment and Template */
require( __DIR__ . '/wp-blog-header.php' );

