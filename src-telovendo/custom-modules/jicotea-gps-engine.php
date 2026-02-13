<?php
/**
 * Jicotea GPS Engine: Localizador de Direcciones en Cuba
 * Base de datos local de barrios/repartos famosos
 */

// Base de datos local de barrios y repartos de Cuba
function jicotea_get_cuba_locations_db() {
    return array(
        // La Habana
        'Vedado' => array('lat' => 23.1367, 'lng' => -82.3656, 'municipio' => 'La Habana'),
        'Centro Habana' => array('lat' => 23.1353, 'lng' => -82.3584, 'municipio' => 'La Habana'),
        'Habana Vieja' => array('lat' => 23.1367, 'lng' => -82.3506, 'municipio' => 'La Habana'),
        'Miramar' => array('lat' => 23.1000, 'lng' => -82.4167, 'municipio' => 'La Habana'),
        'Playa' => array('lat' => 23.0833, 'lng' => -82.4500, 'municipio' => 'La Habana'),
        'Alamar' => array('lat' => 23.1667, 'lng' => -82.2833, 'municipio' => 'La Habana'),
        
        // Pinar del Río
        'Viñales' => array('lat' => 22.6167, 'lng' => -83.7167, 'municipio' => 'Pinar del Río'),
        'Pinar del Río' => array('lat' => 22.4167, 'lng' => -83.7000, 'municipio' => 'Pinar del Río'),
        'Consolación del Sur' => array('lat' => 22.5000, 'lng' => -83.5167, 'municipio' => 'Pinar del Río'),
        
        // Varadero
        'Varadero' => array('lat' => 23.1500, 'lng' => -81.2833, 'municipio' => 'Varadero'),
        'Cárdenas' => array('lat' => 23.0333, 'lng' => -81.2000, 'municipio' => 'Varadero'),
        
        // Trinidad
        'Trinidad' => array('lat' => 21.8000, 'lng' => -79.9833, 'municipio' => 'Trinidad'),
        'Valle de los Ingenios' => array('lat' => 21.8333, 'lng' => -79.9500, 'municipio' => 'Trinidad'),
        
        // Santiago de Cuba
        'Santiago de Cuba' => array('lat' => 20.0247, 'lng' => -75.8219, 'municipio' => 'Santiago de Cuba'),
        'El Cobre' => array('lat' => 20.0500, 'lng' => -75.9500, 'municipio' => 'Santiago de Cuba'),
        'San Luis' => array('lat' => 20.1833, 'lng' => -75.8500, 'municipio' => 'Santiago de Cuba'),
        
        // Camagüey
        'Camagüey' => array('lat' => 21.3833, 'lng' => -77.9167, 'municipio' => 'Camagüey'),
        'Florida' => array('lat' => 21.5167, 'lng' => -78.2167, 'municipio' => 'Camagüey'),
        
        // Holguín
        'Holguín' => array('lat' => 20.8833, 'lng' => -76.2500, 'municipio' => 'Holguín'),
        'Guardalavaca' => array('lat' => 21.1167, 'lng' => -76.0167, 'municipio' => 'Holguín'),
        
        // Cienfuegos
        'Cienfuegos' => array('lat' => 22.1500, 'lng' => -80.4500, 'municipio' => 'Cienfuegos'),
        'Punta Gorda' => array('lat' => 22.1167, 'lng' => -80.4500, 'municipio' => 'Cienfuegos'),
        
        // Santa Clara
        'Santa Clara' => array('lat' => 22.4000, 'lng' => -79.9667, 'municipio' => 'Santa Clara'),
        'Remedios' => array('lat' => 22.5000, 'lng' => -79.5500, 'municipio' => 'Santa Clara')
    );
}

// Endpoint REST API para buscar direcciones
function jicotea_register_gps_api() {
    register_rest_route('jicotea/v1', '/buscar-direccion', array(
        'methods' => 'GET',
        'callback' => 'jicotea_buscar_direccion_cuba',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'jicotea_register_gps_api');

// Función principal de búsqueda de direcciones
function jicotea_buscar_direccion_cuba($request) {
    $query = sanitize_text_field($request->get_param('query'));
    
    if (empty($query)) {
        return new WP_Error('query_required', 'Query parameter is required', array('status' => 400));
    }
    
    // 1. Consultar base de datos local de Barrios/Repartos famosos
    $locations_db = jicotea_get_cuba_locations_db();
    $query_lower = strtolower($query);
    
    // Buscar coincidencias exactas o parciales
    $matches = array();
    foreach ($locations_db as $nombre => $data) {
        if (stripos($nombre, $query) !== false || stripos($query, $nombre) !== false) {
            $matches[] = array(
                'nombre' => $nombre,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'municipio' => $data['municipio'],
                'tipo' => 'local'
            );
        }
    }
    
    // Si hay coincidencias locales, devolverlas
    if (!empty($matches)) {
        return new WP_REST_Response(array(
            'success' => true,
            'results' => $matches,
            'source' => 'local_db'
        ), 200);
    }
    
    // 2. Si no hay coincidencia exacta, usar el servicio de Mapas (Geocoding)
    $geocoded = jicotea_geocode_external($query);
    
    if ($geocoded && isset($geocoded['lat']) && isset($geocoded['lng'])) {
        return new WP_REST_Response(array(
            'success' => true,
            'results' => array($geocoded),
            'source' => 'external_api'
        ), 200);
    }
    
    // Si no se encuentra nada
    return new WP_REST_Response(array(
        'success' => false,
        'message' => 'No se encontró la ubicación solicitada',
        'results' => array()
    ), 404);
}

// Función para geocodificación externa (usando servicios de mapas)
function jicotea_geocode_external($query) {
    // Nota: En producción, usar API de Google Maps, OpenStreetMap, o similar
    // Por ahora, retornamos null para indicar que se debe usar el servicio externo desde el frontend
    
    // Ejemplo de integración con OpenStreetMap Nominatim (gratuito)
    $query_encoded = urlencode($query . ', Cuba');
    $url = "https://nominatim.openstreetmap.org/search?q={$query_encoded}&format=json&limit=1";
    
    $response = wp_remote_get($url, array(
        'timeout' => 5,
        'headers' => array(
            'User-Agent' => 'Te Lo Vendo Cuba - Jicotea GPS Engine'
        )
    ));
    
    if (is_wp_error($response)) {
        return null;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
        return array(
            'nombre' => $query,
            'lat' => floatval($data[0]['lat']),
            'lng' => floatval($data[0]['lon']),
            'tipo' => 'external',
            'display_name' => isset($data[0]['display_name']) ? $data[0]['display_name'] : $query
        );
    }
    
    return null;
}

// Helper: Obtener coordenadas de un municipio
function jicotea_get_municipio_coords($municipio) {
    $locations_db = jicotea_get_cuba_locations_db();
    
    foreach ($locations_db as $nombre => $data) {
        if (stripos($data['municipio'], $municipio) !== false || 
            stripos($municipio, $data['municipio']) !== false) {
            return array(
                'lat' => $data['lat'],
                'lng' => $data['lng']
            );
        }
    }
    
    return null;
}

