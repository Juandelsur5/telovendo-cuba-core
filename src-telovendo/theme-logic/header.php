<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/src-telovendo/assets/css/style-futurista.css">
</head>
<body <?php body_class(); ?>>
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

