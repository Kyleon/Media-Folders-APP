<?php
/**
 * Template Name: Elementor Pantalla Completa
 * Description: Header y footer del tema YPVA con contenido Elementor
 *              a ancho completo, sin .container ni sidebars del tema.
 *              Llama a the_content() una sola vez para evitar la
 *              duplicación que Elementor genera con plantillas
 *              estándar del tema (page-full.php incrusta el contenido
 *              dos veces cuando Elementor está activo).
 *
 * @package YPVA Child
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header(); ?>

    <div class="ypva-elementor-wide">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        wp_reset_postdata();
        ?>
    </div>

<?php get_footer();
