<?php
/**
 * Template Name: Página de Inicio
 * Template para la página principal con buscador hero y ofertas destacadas
 */

get_header(); ?>

<main class="home-page">
    <!-- Sección Hero con Buscador Centralizado -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Encuentra tu hogar en Cuba</h1>
            <p class="hero-subtitle">Propiedades exclusivas en toda la isla</p>
            
            <!-- Buscador Hero con Autocompletado Geográfico -->
            <div class="hero-search">
                <form class="search-form" action="<?php echo get_post_type_archive_link('propiedad'); ?>" method="get">
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
    
    <!-- Sección de Ofertas Destacadas -->
    <section class="featured-properties">
        <div class="section-header">
            <h2 class="section-title">Ofertas Destacadas</h2>
            <p class="section-subtitle">Las mejores propiedades disponibles ahora</p>
        </div>
        
        <div class="propiedades-grid">
            <?php
            // Consulta de propiedades destacadas
            $args = array(
                'post_type' => 'propiedad',
                'posts_per_page' => 6,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'destacada',
                        'value' => '1',
                        'compare' => '='
                    )
                )
            );
            
            // Si no hay destacadas, mostrar las más recientes
            $query = new WP_Query($args);
            if (!$query->have_posts()) {
                $args['meta_query'] = array();
                $query = new WP_Query($args);
            }
            
            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post();
                    $precio = get_field('precio_usd');
                    $municipio = get_field('municipio_cuba');
                    $tipo_operacion = wp_get_post_terms(get_the_ID(), 'tipo_operacion', array('fields' => 'names'));
                    ?>
                    <article class="propiedad-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="propiedad-thumbnail">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium', array('class' => 'propiedad-image')); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="propiedad-content">
                            <h3 class="propiedad-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <?php if ($municipio) : ?>
                                <p class="propiedad-location">📍 <?php echo esc_html($municipio); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($tipo_operacion && !empty($tipo_operacion)) : ?>
                                <span class="badge-operacion"><?php echo esc_html($tipo_operacion[0]); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($precio) : ?>
                                <div class="glass-price">$<?php echo number_format($precio); ?> USD</div>
                            <?php endif; ?>
                            
                            <div class="propiedad-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <a href="<?php the_permalink(); ?>" class="propiedad-link">Ver detalles →</a>
                        </div>
                    </article>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                ?>
                <p class="no-properties">No hay propiedades disponibles en este momento.</p>
                <?php
            endif;
            ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>

