<?php
/**
 * Template para vista individual de propiedades
 */

get_header(); ?>

<main class="single-property-page">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('property-single'); ?>>
            
            <!-- Galería de Imágenes -->
            <section class="property-gallery">
                <?php
                $gallery = get_field('galeria');
                if ($gallery && is_array($gallery)) :
                    ?>
                    <div class="gallery-container">
                        <div class="gallery-main">
                            <?php 
                            $first_image = $gallery[0];
                            if (is_array($first_image)) {
                                echo '<img src="' . esc_url($first_image['url']) . '" alt="' . esc_attr($first_image['alt']) . '" class="gallery-main-image">';
                            } else {
                                echo '<img src="' . esc_url($first_image) . '" alt="' . esc_attr(get_the_title()) . '" class="gallery-main-image">';
                            }
                            ?>
                        </div>
                        <div class="gallery-thumbnails">
                            <?php 
                            foreach (array_slice($gallery, 1, 4) as $image) :
                                if (is_array($image)) {
                                    $img_url = $image['url'];
                                    $img_alt = $image['alt'];
                                } else {
                                    $img_url = $image;
                                    $img_alt = get_the_title();
                                }
                                ?>
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>" class="gallery-thumb">
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif (has_post_thumbnail()) : ?>
                    <div class="gallery-container">
                        <div class="gallery-main">
                            <?php the_post_thumbnail('large', array('class' => 'gallery-main-image')); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Contenido Principal -->
            <div class="property-content-wrapper">
                <!-- Información Técnica Flotante -->
                <aside class="property-info-box">
                    <div class="info-box-content">
                        <?php
                        $precio = get_field('precio_usd');
                        $habitaciones = get_field('habitaciones');
                        $banos = get_field('banos');
                        $superficie = get_field('superficie');
                        $municipio = get_field('municipio_cuba');
                        $provincia = get_field('provincia_cuba');
                        ?>
                        
                        <?php if ($precio) : ?>
                            <div class="info-item price">
                                <span class="info-label">Precio</span>
                                <span class="info-value">$<?php echo number_format($precio); ?> USD</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($habitaciones) : ?>
                            <div class="info-item">
                                <span class="info-label">Habitaciones</span>
                                <span class="info-value"><?php echo esc_html($habitaciones); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($banos) : ?>
                            <div class="info-item">
                                <span class="info-label">Baños</span>
                                <span class="info-value"><?php echo esc_html($banos); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($superficie) : ?>
                            <div class="info-item">
                                <span class="info-label">Superficie</span>
                                <span class="info-value"><?php echo esc_html($superficie); ?> m²</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($municipio || $provincia) : ?>
                            <div class="info-item location">
                                <span class="info-label">Ubicación</span>
                                <span class="info-value">
                                    <?php 
                                    if ($municipio) echo esc_html($municipio);
                                    if ($municipio && $provincia) echo ', ';
                                    if ($provincia) echo esc_html($provincia);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-actions">
                            <a href="https://wa.me/53512345678?text=<?php echo urlencode('Me interesa: ' . get_the_title()); ?>" 
                               class="btn-whatsapp" target="_blank">
                                📱 Contactar por WhatsApp
                            </a>
                        </div>
                    </div>
                </aside>
                
                <!-- Descripción y Detalles -->
                <div class="property-main-content">
                    <header class="property-header">
                        <h1 class="property-title"><?php the_title(); ?></h1>
                        <?php
                        $tipo_operacion = wp_get_post_terms(get_the_ID(), 'tipo_operacion', array('fields' => 'names'));
                        if ($tipo_operacion && !empty($tipo_operacion)) :
                            ?>
                            <span class="badge-operacion"><?php echo esc_html($tipo_operacion[0]); ?></span>
                        <?php endif; ?>
                    </header>
                    
                    <div class="property-description">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php
                    $caracteristicas = get_field('caracteristicas');
                    if ($caracteristicas) :
                        ?>
                        <section class="property-features">
                            <h2>Características</h2>
                            <ul class="features-list">
                                <?php foreach ($caracteristicas as $feature) : ?>
                                    <li><?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                </div>
            </div>
            
        </article>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>

