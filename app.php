<?php
// Headers para evitar caché - DEBE IR PRIMERO
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Obtener la URL base del tema
$stylesheet_uri = '/wp-content/themes/telovendo-cuba';
if (function_exists('get_stylesheet_directory_uri')) {
    $stylesheet_uri = get_stylesheet_directory_uri();
}

// BYPASS TOTAL: Echo con toda la arquitectura - Buscador Hero y Jicotea
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Te Lo Vendo Cuba - Encuentra tu hogar en Cuba</title>
    <style>
        /* ============================================
           BANDERA DE CUBA - Fondo Elegante
           ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg,
                #002590 0%,
                #002590 25%,
                #ffffff 25%,
                #ffffff 50%,
                #002590 50%,
                #002590 75%,
                #ffffff 75%,
                #ffffff 100%) fixed !important;
            background-size: 200% 200% !important;
            background-position: 0% 0% !important;
            min-height: 100vh !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
            position: relative !important;
            overflow-x: hidden !important;
        }
        
        body::before {
            content: "" !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(0, 0, 0, 0.05) !important;
            z-index: 0 !important;
            pointer-events: none !important;
        }
        
        body::after {
            content: "" !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 0 !important;
            height: 0 !important;
            border-top: 50vh solid transparent !important;
            border-bottom: 50vh solid transparent !important;
            border-left: 30vw solid #CF142B !important;
            z-index: 1 !important;
            opacity: 0.3 !important;
            pointer-events: none !important;
        }
        
        /* ============================================
           BUSCADOR HERO - Centro de la Página
           ============================================ */
        .hero-section {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 10 !important;
            padding: 20px !important;
        }
        
        .hero-content {
            max-width: 900px !important;
            width: 100% !important;
            text-align: center !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        .hero-title {
            font-size: clamp(32px, 5vw, 64px) !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            margin: 0 0 20px !important;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.4), 0 0 30px rgba(0, 37, 144, 0.3) !important;
            letter-spacing: -0.02em !important;
        }
        
        .hero-subtitle {
            font-size: clamp(16px, 2vw, 24px) !important;
            color: rgba(255, 255, 255, 0.95) !important;
            margin: 0 0 50px !important;
            text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.3) !important;
        }
        
        .hero-search {
            margin-top: 0 !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        .search-form {
            width: 100% !important;
            max-width: 700px !important;
            margin: 0 auto !important;
        }
        
        .search-input-wrapper {
            display: flex !important;
            gap: 0 !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(30px) !important;
            border-radius: 60px !important;
            padding: 8px !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.5) !important;
            transition: transform 0.3s ease !important;
        }
        
        .search-input-wrapper:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35) !important;
        }
        
        .search-input {
            flex: 1 !important;
            border: none !important;
            background: transparent !important;
            padding: 20px 30px !important;
            font-size: 18px !important;
            outline: none !important;
            color: #002590 !important;
            font-weight: 500 !important;
        }
        
        .search-input::placeholder {
            color: #999 !important;
        }
        
        .search-button {
            background: linear-gradient(135deg, #002590 0%, #0038a8 50%, #002590 100%) !important;
            color: white !important;
            border: none !important;
            border-radius: 50px !important;
            padding: 20px 40px !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            white-space: nowrap !important;
            text-transform: uppercase !important;
            letter-spacing: 0.02em !important;
        }
        
        .search-button:hover {
            transform: scale(1.05) !important;
        }
        
        /* Botón Flotante de Jicotea */
        #jicotea-ia-btn {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 9999 !important;
            background: #002a8f !important;
            color: white !important;
            width: 60px !important;
            height: 60px !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 30px !important;
            transition: transform 0.3s ease !important;
            border: none !important;
        }
        
        #jicotea-ia-btn:hover {
            transform: scale(1.1) !important;
        }
        
        @media (max-width: 768px) {
            .search-input-wrapper {
                flex-direction: column !important;
                border-radius: 25px !important;
                gap: 8px !important;
            }
            
            .search-input {
                padding: 18px 25px !important;
                font-size: 16px !important;
                border-radius: 20px !important;
            }
            
            .search-button {
                width: 100% !important;
                padding: 18px 30px !important;
                border-radius: 20px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sección Hero con Buscador Centralizado -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Encuentra tu hogar en Cuba</h1>
            <p class="hero-subtitle">Propiedades exclusivas en toda la isla</p>
            
            <!-- Buscador Hero -->
            <div class="hero-search">
                <form class="search-form" action="/propiedad" method="get">
                    <div class="search-input-wrapper">
                        <input 
                            type="text" 
                            name="ubicacion" 
                            id="hero-search-input"
                            class="search-input"
                            placeholder="Busca por provincia, municipio o barrio..."
                            autocomplete="off"
                        />
                        <button type="submit" class="search-button">
                            <span>🔍</span> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Botón Flotante de Jicotea -->
    <div id="jicotea-ia-btn" onclick="alert(\'Jicotea: ¡Hola Ingeniero! Sistema en línea.\')">🐢</div>
    
    <!-- Scripts de Jicotea -->
    <script src="' . esc_url($stylesheet_uri . '/src-telovendo/assets/js/jicotea-autocomplete.js') . '"></script>
    <script src="' . esc_url($stylesheet_uri . '/src-telovendo/assets/js/jicotea-chat.js') . '"></script>
</body>
</html>';

// Die para matar cualquier intento de Astra de renderizar el sitio viejo
die();

