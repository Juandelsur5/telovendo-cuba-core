<?php
/**
 * Script de Inyección Masiva de Datos - Te Lo Vendo Cuba
 * Pobla las taxonomías jerárquicas con datos de municipios-cuba.json
 * 
 * INSTRUCCIONES:
 * 1. Cargar en navegador: https://telovendocuba.com/inyectar-datos.php
 * 2. Verificar en panel de WordPress
 * 3. BORRAR este archivo después de usarlo por seguridad
 */

// Cargar WordPress
require_once(__DIR__ . '/wp-load.php');

// Verificar que el usuario tenga permisos de administrador
if (!current_user_can('manage_options')) {
    die('Error: No tienes permisos para ejecutar este script.');
}

// Ruta al archivo JSON
$json_path = __DIR__ . '/src-telovendo/custom-modules/municipios-cuba.json';

if (!file_exists($json_path)) {
    die('Error: No se encontró el archivo municipios-cuba.json en: ' . $json_path);
}

// Leer y decodificar JSON
$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error: No se pudo decodificar el JSON. Error: ' . json_last_error_msg());
}

// Contadores
$provincias_insertadas = 0;
$municipios_insertados = 0;
$barrios_insertados = 0;
$errores = array();

// Función helper para insertar término con manejo de errores
function insertar_termino_seguro($nombre, $taxonomia, $padre_id = 0) {
    // Verificar si el término ya existe
    $termino_existente = term_exists($nombre, $taxonomia, $padre_id);
    
    if ($termino_existente) {
        return $termino_existente['term_id'];
    }
    
    // Insertar nuevo término
    $resultado = wp_insert_term(
        $nombre,
        $taxonomia,
        array(
            'parent' => $padre_id,
            'description' => ''
        )
    );
    
    if (is_wp_error($resultado)) {
        return null;
    }
    
    return $resultado['term_id'];
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Inyección de Datos - Te Lo Vendo Cuba</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        .section { margin: 20px 0; padding: 15px; background: white; border-radius: 5px; }
        h1 { color: #002590; }
        h2 { color: #cf142b; margin-top: 30px; }
        ul { list-style-type: none; padding-left: 0; }
        li { padding: 5px 0; }
    </style>
</head>
<body>
    <h1>🚀 Inyección Masiva de Datos - Te Lo Vendo Cuba</h1>
    <div class='section'>
        <h2>Procesando datos de municipios-cuba.json...</h2>";

// Procesar datos del JSON
$taxonomia = 'ubicacion_cubana';

// Verificar que la taxonomía existe
if (!taxonomy_exists($taxonomia)) {
    die("<p class='error'>Error: La taxonomía 'ubicacion_cubana' no existe. Asegúrate de que el motor de propiedades esté activo.</p></body></html>");
}

// Iterar sobre provincias
foreach ($data['provincias'] as $provincia_nombre => $provincia_data) {
    // Insertar Provincia (nivel 1)
    $provincia_id = insertar_termino_seguro($provincia_nombre, $taxonomia, 0);
    
    if ($provincia_id) {
        $provincias_insertadas++;
        echo "<p class='info'>✓ Provincia: <strong>{$provincia_nombre}</strong> (ID: {$provincia_id})</p>";
        
        // Iterar sobre municipios de la provincia
        if (isset($provincia_data['municipios'])) {
            foreach ($provincia_data['municipios'] as $municipio_nombre => $municipio_data) {
                // Insertar Municipio (nivel 2, hijo de Provincia)
                $municipio_id = insertar_termino_seguro($municipio_nombre, $taxonomia, $provincia_id);
                
                if ($municipio_id) {
                    $municipios_insertados++;
                    echo "<p class='info' style='margin-left: 20px;'>✓ Municipio: <strong>{$municipio_nombre}</strong> (ID: {$municipio_id}, Padre: {$provincia_nombre})</p>";
                    
                    // Iterar sobre barrios del municipio
                    if (isset($municipio_data['barrios']) && is_array($municipio_data['barrios'])) {
                        foreach ($municipio_data['barrios'] as $barrio_nombre) {
                            // Insertar Barrio/Reparto (nivel 3, hijo de Municipio)
                            $barrio_id = insertar_termino_seguro($barrio_nombre, $taxonomia, $municipio_id);
                            
                            if ($barrio_id) {
                                $barrios_insertados++;
                                echo "<p class='info' style='margin-left: 40px;'>✓ Barrio: <strong>{$barrio_nombre}</strong> (ID: {$barrio_id}, Padre: {$municipio_nombre})</p>";
                            } else {
                                $errores[] = "Error al insertar barrio: {$barrio_nombre}";
                            }
                        }
                    }
                } else {
                    $errores[] = "Error al insertar municipio: {$municipio_nombre}";
                }
            }
        }
    } else {
        $errores[] = "Error al insertar provincia: {$provincia_nombre}";
    }
}

// Resumen final
echo "</div>
    <div class='section'>
        <h2>✅ Resumen de Inyección</h2>
        <ul>
            <li class='success'>Provincias insertadas: <strong>{$provincias_insertadas}</strong></li>
            <li class='success'>Municipios insertados: <strong>{$municipios_insertados}</strong></li>
            <li class='success'>Barrios insertados: <strong>{$barrios_insertados}</strong></li>
        </ul>";

if (!empty($errores)) {
    echo "<h2 class='error'>⚠️ Errores Encontrados</h2><ul>";
    foreach ($errores as $error) {
        echo "<li class='error'>{$error}</li>";
    }
    echo "</ul>";
}

echo "<h2 class='success'>🎉 ¡Inyección Completada!</h2>
        <p><strong>Próximos pasos:</strong></p>
        <ol>
            <li>Ve a tu panel de WordPress → Propiedades → Añadir nueva</li>
            <li>Busca la sección 'Ubicaciones Cuba'</li>
            <li>Verifica que las ventanas de selección muestren Provincias, Municipios y Barrios</li>
            <li><strong style='color: #dc3545;'>IMPORTANTE: Borra este archivo (inyectar-datos.php) por seguridad</strong></li>
        </ol>
    </div>
</body>
</html>";

