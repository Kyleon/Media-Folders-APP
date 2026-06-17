<?php
/**
 * Plugin Name: YZ Media Folders
 * Plugin URI:  https://nubedocs.es
 * Description: Gestor de medios propio con carpetas, drag & drop, modal de edición, sliders configurables y REST API. Independiente de la librería nativa de WordPress.
 * Version:     2.5.0
 * Author:      Yezrael Pérez · Nubedocs
 * Author URI:  https://nubedocs.es
 * License:     GPL-2.0+
 * Text Domain: yz-media-folders
 */

defined( 'ABSPATH' ) || exit;

define( 'YZMF_VERSION',  '2.5.0' );
define( 'YZMF_PATH',     plugin_dir_path( __FILE__ ) );
define( 'YZMF_URL',      plugin_dir_url( __FILE__ ) );
define( 'YZMF_TAXONOMY', 'yz_media_folder' );

require_once YZMF_PATH . 'includes/class-taxonomy.php';
require_once YZMF_PATH . 'includes/class-ajax.php';
require_once YZMF_PATH . 'includes/class-media-service.php';
require_once YZMF_PATH . 'includes/class-admin.php';
require_once YZMF_PATH . 'includes/class-map.php';
require_once YZMF_PATH . 'includes/class-rest.php';
require_once YZMF_PATH . 'includes/class-portfolio-bridge.php';
require_once YZMF_PATH . 'includes/class-schema.php';
require_once YZMF_PATH . 'includes/class-lightroom.php';
require_once YZMF_PATH . 'includes/class-brand.php';
require_once YZMF_PATH . 'includes/class-auth-log.php';
require_once YZMF_PATH . 'includes/class-basic-auth.php';
require_once YZMF_PATH . 'includes/class-slider.php';
require_once YZMF_PATH . 'includes/class-slider-rest.php';
require_once YZMF_PATH . 'includes/class-slider-shortcode.php';
require_once YZMF_PATH . 'includes/class-elementor-integration.php';

add_action( 'plugins_loaded', function () {
    YZMF_Taxonomy::init();
    YZMF_Ajax::init();
    YZMF_Admin::init();
    YZMF_Map::init();
    YZMF_REST::init();
    YZMF_Portfolio_Bridge::init();
    YZMF_Schema::init();
    YZMF_Lightroom::init();
    YZMF_Brand::init();
    YZMF_Auth_Log::init();
    YZMF_Basic_Auth::init();
    YZMF_Slider::init();
    YZMF_Slider_REST::init();
    YZMF_Slider_Shortcode::init();
    YZMF_Elementor_Integration::init();
} );

/**
 * Invalida los transients de stats/tags/colors cuando hay cambios que
 * afectan a sus números. Así DashLatest, DashHeatmap y los KPIs reflejan
 * al instante una subida, borrado, edición o asignación a carpeta.
 */
if ( ! function_exists( 'yzmf_bust_stats_cache' ) ) {
    function yzmf_bust_stats_cache() {
        delete_transient( 'yzmf_stats_cache' );
        delete_transient( 'yzmf_stats_exif_cache' );
        delete_transient( 'yzmf_tags_cache' );
        delete_transient( 'yzmf_colors_cache' );
    }
}
add_action( 'add_attachment',      'yzmf_bust_stats_cache' );
add_action( 'delete_attachment',   'yzmf_bust_stats_cache' );
add_action( 'edit_attachment',     'yzmf_bust_stats_cache' );
add_action( 'save_post_portfolio', 'yzmf_bust_stats_cache' );
add_action( 'edited_term', function ( $term_id, $tt_id, $taxonomy ) {
    if ( $taxonomy === YZMF_TAXONOMY ) yzmf_bust_stats_cache();
}, 10, 3 );
add_action( 'created_term', function ( $term_id, $tt_id, $taxonomy ) {
    if ( $taxonomy === YZMF_TAXONOMY ) yzmf_bust_stats_cache();
}, 10, 3 );

register_activation_hook( __FILE__, function () {
    // La taxonomía se registra en init; aquí solo refrescamos rewrite rules.
    flush_rewrite_rules();
} );
