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

// ============================================
// INYECCIÓN DIRECTA DE CSS FUTURISTA - wp_head
// Prioridad 1 para sobreescribir Astra
// ============================================
function telovendo_inject_futurista_css() {
    ?>
    <style id="telovendo-futurista-css" type="text/css">
    /* Estructura Global de la Bandera de Cuba - Mobile-First V2.1 */
    body {
        /* Fondo fijo de la bandera de Cuba en alta resolución */
        background: linear-gradient(to bottom, 
            #002590 0% 20%, #ffffff 20% 40%, 
            #002590 40% 60%, #ffffff 60% 80%, 
            #002590 80% 100%) fixed !important;
        background-attachment: fixed !important;
        background-size: cover !important;
        background-position: center !important;
        margin: 0 !important;
        min-height: 100vh !important;
        
        /* Overlay semi-transparente para legibilidad en pantallas OLED */
        position: relative !important;
    }

    body::before {
        content: '' !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: rgba(0, 0, 0, 0.15) !important; /* Overlay oscuro para contraste */
        z-index: 0 !important;
        pointer-events: none !important;
    }

    /* Contenedor principal sobre el overlay */
    body > * {
        position: relative !important;
        z-index: 1 !important;
    }

    #triangulo-cuba {
        width: 0 !important;
        height: 0 !important;
        border-top: 50vh solid transparent !important;
        border-bottom: 50vh solid transparent !important;
        border-left: 40vw solid #cf142b !important; /* Triángulo rojo de la bandera */
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 1 !important;
    }

    /* Tarjetas de propiedades futuristas - Mobile-First */
    .propiedad-card {
        background: rgba(255, 255, 255, 0.95) !important; /* Fondo blanco sólido para legibilidad */
        backdrop-filter: blur(15px) !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
        border-radius: 20px !important; /* Bordes redondeados optimizados */
        padding: 20px !important;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important; /* Sombra suave */
        margin-bottom: 20px !important;
        
        /* Eliminar cualquier icono de bandera interna */
        position: relative !important;
    }

    .propiedad-card::before,
    .propiedad-card::after {
        display: none !important; /* Eliminar pseudo-elementos de bandera */
    }

    .propiedad-card:hover {
        transform: translateY(-2px) !important;
        background: rgba(255, 255, 255, 1) !important;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    /* Grilla Táctica - Mobile-First: Una columna en móvil */
    @media (max-width: 768px) {
        .propiedades-grid,
        .properties-grid {
            display: flex !important;
            flex-direction: column !important;
            gap: 20px !important;
            padding: 15px !important;
        }
        
        .propiedad-card {
            width: 100% !important;
            max-width: 100% !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }
    }

    /* Desktop: Grid de múltiples columnas */
    @media (min-width: 769px) {
        .propiedades-grid,
        .properties-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)) !important;
            gap: 25px !important;
            padding: 25px !important;
        }
    }

    .badge-operacion {
        background: linear-gradient(90deg, #00d2ff 0%, #3a7bd5 100%) !important;
        color: white !important;
        padding: 5px 15px !important;
        border-radius: 50px !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
    }

    .glass-price {
        background: rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(10px) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        padding: 10px 20px !important;
        border-radius: 15px !important;
        color: #fff !important;
        font-weight: bold !important;
    }
    </style>
    <?php
}
add_action('wp_head', 'telovendo_inject_futurista_css', 1);

// ============================================
// INYECCIÓN DIRECTA DE JICOTEA - wp_footer
// Prioridad 999 para forzar aparición al final, ignorando editor de WordPress
// ============================================
function inyectar_jicotea_ia() {
    $stylesheet_uri = get_stylesheet_directory_uri();
    ?>
    <!-- JICOTEA-GENIO: Inyección Directa -->
    <style id="jicotea-chat-css-direct" type="text/css">
    /* ============================================
       JICOTEA-GENIO: Interfaz de Chat Glassmorphism
       ============================================ */

    /* Contenedor del Jicotea anclado en el triángulo rojo */
    #jicotea-ia-anchor {
        position: fixed;
        left: 20px;
        bottom: 30px;
        z-index: 1000;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    /* Estados del anchor */
    #jicotea-ia-anchor.solo-jicotea {
        opacity: 1;
        transform: scale(1);
    }

    #jicotea-ia-anchor.chat-abierto {
        opacity: 1;
        transform: scale(1);
    }

    #jicotea-ia-anchor.minimizado {
        opacity: 0;
        transform: scale(0);
        pointer-events: none;
    }

    /* Avatar del Jicotea con animaciones */
    .jicotea-genio-ia {
        width: 80px;
        height: auto;
        max-width: 220px;
        min-width: 180px;
        filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
        animation: levitacion-jicotea 3s ease-in-out infinite;
        transition: transform 0.3s ease, width 0.4s ease;
    }

    /* Estado 1 (HABLANDO): Jicotea gesticulando con medidas específicas */
    #jicotea-ia-anchor:has(~ .jicotea-chat-container:not(.active)) .jicotea-genio-ia,
    .jicotea-genio-ia[data-state="hablando"] {
        width: 200px;
    }

    .jicotea-genio-ia:hover {
        transform: scale(1.1);
    }

    /* Animación de levitación para el Jicotea */
    @keyframes levitacion-jicotea {
        0%, 100% {
            transform: translateY(0px) rotate(0deg);
        }
        25% {
            transform: translateY(-8px) rotate(-2deg);
        }
        50% {
            transform: translateY(-12px) rotate(0deg);
        }
        75% {
            transform: translateY(-8px) rotate(2deg);
        }
    }

    /* Animación de parpadeo aleatorio para los ojos */
    @keyframes parpadeo-ojos {
        0%, 90%, 100% {
            opacity: 1;
        }
        95% {
            opacity: 0.3;
        }
    }

    .jicotea-genio-ia {
        animation: levitacion-jicotea 4s ease-in-out infinite, parpadeo-ojos 3s ease-in-out infinite;
    }

    /* Animaciones de humor activo */
    .jicotea-genio-ia.humor-activo {
        animation: levitacion-jicotea 2s ease-in-out infinite, 
                   parpadeo-picardia 1s ease-in-out infinite,
                   boca-hablando 0.3s ease-in-out infinite;
    }

    @keyframes parpadeo-picardia {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        25% {
            opacity: 0.3;
            transform: scale(0.95);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        75% {
            opacity: 0.5;
            transform: scale(0.98);
        }
    }

    @keyframes boca-hablando {
        0%, 100% {
            transform: translateY(0px) scaleY(1);
        }
        25% {
            transform: translateY(-1px) scaleY(1.05);
        }
        50% {
            transform: translateY(-2px) scaleY(1.1);
        }
        75% {
            transform: translateY(-1px) scaleY(1.05);
        }
    }

    @keyframes movimiento-cola {
        0%, 100% {
            transform: rotate(0deg);
        }
        25% {
            transform: rotate(5deg);
        }
        50% {
            transform: rotate(0deg);
        }
        75% {
            transform: rotate(-5deg);
        }
    }

    /* Glassmorphism para el Chat */
    .jicotea-chat-container {
        position: fixed;
        bottom: 120px;
        left: 20px;
        width: 320px;
        height: 450px;
        max-height: 450px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        display: none;
        flex-direction: column;
        z-index: 999;
        overflow: hidden;
        animation: slide-up-chat 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .jicotea-chat-container.active {
        display: flex;
    }

    @keyframes slide-up-chat {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Header del Chat */
    .jicotea-chat-header {
        background: rgba(255, 255, 255, 0.1);
        padding: 15px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .jicotea-chat-header img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .jicotea-chat-header-info h3 {
        margin: 0;
        color: #fff;
        font-size: 16px;
        font-weight: 600;
    }

    .jicotea-chat-header-info p {
        margin: 0;
        color: rgba(255, 255, 255, 0.7);
        font-size: 12px;
    }

    .jicotea-chat-close {
        margin-left: auto;
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.3s;
        padding: 8px;
        min-width: 36px;
        min-height: 36px;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }

    .jicotea-chat-close:hover,
    .jicotea-chat-close:active {
        opacity: 1;
        transform: scale(1.1);
    }

    /* Mobile-First: Bottom Sheet */
    @media (max-width: 768px) {
        .jicotea-chat-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100% !important;
            height: 90vh !important;
            max-height: 90vh !important;
            border-radius: 24px 24px 0 0;
            margin: 0;
            animation: slide-up-bottom-sheet 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes slide-up-bottom-sheet {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        #jicotea-ia-anchor {
            bottom: 80px;
            left: 15px;
            right: auto;
        }
        
        .jicotea-genio-ia {
            width: 60px !important;
            max-width: 60px !important;
            min-width: 60px !important;
        }
        
        .jicotea-chat-close,
        .jicotea-quick-action-btn {
            min-width: 48px !important;
            min-height: 48px !important;
            font-size: 18px;
            padding: 12px;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        
        .jicotea-x-flotante {
            min-width: 48px !important;
            min-height: 48px !important;
            font-size: 28px;
            bottom: 100px !important;
        }
    }

    /* Área de Mensajes */
    .jicotea-chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        max-height: 400px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .jicotea-chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .jicotea-chat-messages::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
    }

    .jicotea-chat-messages::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 10px;
    }

    /* Mensajes */
    .jicotea-message {
        display: flex;
        gap: 10px;
        animation: message-fade-in 0.3s ease;
    }

    @keyframes message-fade-in {
        from {
            opacity: 0;
            transform: translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .jicotea-message.user {
        flex-direction: row-reverse;
    }

    .jicotea-message-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .jicotea-message-bubble {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 18px;
        word-wrap: break-word;
        line-height: 1.4;
    }

    .jicotea-message.user .jicotea-message-bubble {
        background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .jicotea-message.jicotea .jicotea-message-bubble {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-bottom-left-radius: 4px;
        backdrop-filter: blur(10px);
    }

    /* Indicador de escritura */
    .jicotea-typing-indicator {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
    }

    .jicotea-typing-dot {
        width: 8px;
        height: 8px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        animation: typing-bounce 1.4s infinite;
    }

    .jicotea-typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .jicotea-typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing-bounce {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-10px);
        }
    }

    /* Input de Mensaje */
    .jicotea-chat-input-container {
        padding: 15px 20px;
        background: rgba(255, 255, 255, 0.05);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .jicotea-chat-input {
        flex: 1;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: 10px 16px;
        color: #fff;
        font-size: 14px;
        outline: none;
        transition: all 0.3s;
    }

    .jicotea-chat-input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .jicotea-chat-input::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .jicotea-chat-send {
        background: linear-gradient(135deg, #00d2ff 0%, #3a7bd5 100%);
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    }

    .jicotea-chat-send:hover {
        transform: scale(1.1);
    }

    .jicotea-chat-send:active {
        transform: scale(0.95);
    }

    /* Botón X Flotante */
    .jicotea-x-flotante {
        position: fixed;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1001;
        transition: all 0.3s ease;
        opacity: 0;
        pointer-events: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
        user-select: none;
    }

    .jicotea-x-flotante.visible {
        opacity: 1;
        pointer-events: all;
    }

    .jicotea-x-flotante:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }

    /* Burbuja Roja Minimizada */
    .jicotea-mini-bubble {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #cf142b;
        box-shadow: 0 4px 15px rgba(207, 20, 43, 0.4);
        cursor: pointer;
        z-index: 1000;
        opacity: 0;
        pointer-events: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        display: none;
        align-items: center;
        justify-content: center;
        animation: pulso-burbuja 2s ease-in-out infinite;
        touch-action: manipulation;
        -webkit-tap-highlight-color: transparent;
    }

    .jicotea-mini-bubble:hover {
        transform: scale(1.2);
        box-shadow: 0 6px 25px rgba(207, 20, 43, 0.7);
    }

    .jicotea-mini-bubble::before {
        content: '💬';
        font-size: 20px;
        filter: brightness(0) invert(1);
    }

    @keyframes pulso-burbuja {
        0%, 100% {
            box-shadow: 0 4px 15px rgba(207, 20, 43, 0.5);
            transform: scale(1);
        }
        50% {
            box-shadow: 0 4px 25px rgba(207, 20, 43, 0.8);
            transform: scale(1.05);
        }
    }
    </style>
    
    <!-- HTML del Chatbot -->
    <div id="jicotea-ia-anchor">
        <img src="<?php echo esc_url($stylesheet_uri . '/chatbot jicotea.png'); ?>" class="jicotea-genio-ia" alt="Jicotea-Genio IA" />
    </div>
    
    <!-- Scripts de Jicotea -->
    <script src="<?php echo esc_url($stylesheet_uri . '/src-telovendo/assets/js/jicotea-autocomplete.js'); ?>"></script>
    <script src="<?php echo esc_url($stylesheet_uri . '/src-telovendo/assets/js/jicotea-chat.js'); ?>"></script>
    <?php
}
add_action('wp_footer', 'inyectar_jicotea_ia', 999);

// ============================================
// BYPASS TOTAL: Inyectar Jicotea en el Footer
// Botón simple de prueba para verificar que el sistema funciona
// ============================================
add_action('wp_footer', function() {
    ?>
    <style>
        #jicotea-ia-btn { 
            position: fixed; 
            bottom: 20px; 
            right: 20px; 
            z-index: 9999; 
            background: #002a8f; 
            color: white; 
            padding: 15px; 
            border-radius: 50%; 
            cursor: pointer; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            transition: transform 0.3s ease;
        }
        #jicotea-ia-btn:hover {
            transform: scale(1.1);
        }
        .hero-cuba { 
            background: linear-gradient(to bottom, #002a8f 33%, #ffffff 33%, #ffffff 66%, #cf142b 66%); 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            text-align: center; 
        }
    </style>
    <div id="jicotea-ia-btn" onclick="alert('Jicotea: ¡Hola Ingeniero! Sistema en línea.')">🐢</div>
    <?php
}, 999);

