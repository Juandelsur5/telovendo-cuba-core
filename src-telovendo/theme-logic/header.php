<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/src-telovendo/assets/css/style-futurista.css">
</head>
<body <?php body_class(); ?>>
<style type="text/css">
/* ============================================
   BANDERA DE CUBA - INYECCIÓN DIRECTA EN BODY
   !important para sobreescribir Astra
   ============================================ */
body {
    background: linear-gradient(to bottom, 
        #002590 0% 20%, #ffffff 20% 40%, 
        #002590 40% 60%, #ffffff 60% 80%, 
        #002590 80% 100%) fixed !important;
    background-attachment: fixed !important;
    background-size: cover !important;
    background-position: center !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh !important;
    position: relative !important;
}

body::before {
    content: '' !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.15) !important;
    z-index: 0 !important;
    pointer-events: none !important;
}

body > * {
    position: relative !important;
    z-index: 1 !important;
}

#triangulo-cuba {
    width: 0 !important;
    height: 0 !important;
    border-top: 50vh solid transparent !important;
    border-bottom: 50vh solid transparent !important;
    border-left: 40vw solid #cf142b !important;
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    z-index: 1 !important;
}

/* Botón Flotante de Jicotea */
#jicotea-ia-btn { 
    position: fixed !important; 
    bottom: 20px !important; 
    right: 20px !important; 
    z-index: 9999 !important; 
    background: #002a8f !important; 
    color: white !important; 
    padding: 15px !important; 
    border-radius: 50% !important; 
    cursor: pointer !important; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important; 
    width: 60px !important;
    height: 60px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 30px !important;
    transition: transform 0.3s ease !important;
    border: none !important;
    outline: none !important;
}

#jicotea-ia-btn:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.4) !important;
}

#jicotea-ia-btn:active {
    transform: scale(0.95) !important;
}

.hero-cuba { 
    background: linear-gradient(to bottom, #002a8f 33%, #ffffff 33%, #ffffff 66%, #cf142b 66%) !important; 
    height: 100vh !important; 
    display: flex !important; 
    align-items: center !important; 
    justify-content: center !important; 
    color: white !important; 
    text-align: center !important; 
}
</style>
<div id="jicotea-ia-btn" onclick="alert('Jicotea: ¡Hola Ingeniero! Sistema en línea.')">🐢</div>
<?php wp_body_open(); ?>
    <!-- Triángulo rojo de la bandera (Layer 0) -->
    <div id="triangulo-cuba"></div>
    
    <!-- Header con Menú de Navegación -->
    <header class="telovendo-header">
        <div class="header-container">
            <!-- Logo a la izquierda -->
            <div class="header-logo">
                <a href="<?php echo home_url(); ?>">
                    <?php if (has_custom_logo()) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <h1 class="site-title">Te Lo Vendo Cuba</h1>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Menú de usuario a la derecha -->
            <nav class="header-menu">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_class' => 'nav-menu',
                    'container' => false,
                    'fallback_cb' => 'telovendo_default_menu'
                ));
                ?>
            </nav>
        </div>
    </header>
    
    <!-- Menú móvil inferior (solo visible en móvil) -->
    <nav class="mobile-bottom-nav">
        <a href="<?php echo home_url(); ?>" class="mobile-bottom-nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
            <span class="mobile-nav-icon">🏠</span>
            <span class="mobile-nav-label">Inicio</span>
        </a>
        <a href="<?php echo get_post_type_archive_link('propiedad'); ?>" class="mobile-bottom-nav-item">
            <span class="mobile-nav-icon">🔍</span>
            <span class="mobile-nav-label">Explorar</span>
        </a>
        <a href="#jicotea" class="mobile-bottom-nav-item">
            <span class="mobile-nav-icon">🤖</span>
            <span class="mobile-nav-label">Asistente</span>
        </a>
    </nav>

<?php
// Función de respaldo para menú por defecto
function telovendo_default_menu() {
    echo '<ul class="nav-menu">';
    echo '<li><a href="' . home_url() . '">Inicio</a></li>';
    echo '<li><a href="' . get_post_type_archive_link('propiedad') . '">Propiedades</a></li>';
    echo '<li><a href="' . home_url('/contacto') . '">Contacto</a></li>';
    echo '</ul>';
}
?>

