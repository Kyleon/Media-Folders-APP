<?php
/**
 * Plugin Name: YZMF Hotlink Watermark
 * Plugin URI:  https://yezraelperez.es
 * Description: Detecta hotlinking de imágenes desde dominios externos y sirve una versión con marca de agua. Cache en disco, configurable.
 * Version:     1.0.0
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Author:      Yezrael Pérez
 * License:     GPL-2.0+
 * Text Domain: yzmf-hotlink-wm
 */

defined( 'ABSPATH' ) || exit;

define( 'YZMF_HW_VERSION', '1.0.0' );
define( 'YZMF_HW_PATH',    plugin_dir_path( __FILE__ ) );
define( 'YZMF_HW_URL',     plugin_dir_url( __FILE__ ) );
// Caché: wp-content/uploads/yzmf-hotlink-cache/
define( 'YZMF_HW_CACHE_DIR', WP_CONTENT_DIR . '/uploads/yzmf-hotlink-cache' );
define( 'YZMF_HW_CACHE_URL', WP_CONTENT_URL . '/uploads/yzmf-hotlink-cache' );

require_once YZMF_HW_PATH . 'includes/class-handler.php';
require_once YZMF_HW_PATH . 'includes/class-admin.php';
require_once YZMF_HW_PATH . 'includes/class-rewrite.php';

add_action( 'plugins_loaded', function () {
    YZMF_HW_Handler::init();
    YZMF_HW_Admin::init();
    YZMF_HW_Rewrite::init();
} );

register_activation_hook( __FILE__, function () {
    if ( ! file_exists( YZMF_HW_CACHE_DIR ) ) {
        wp_mkdir_p( YZMF_HW_CACHE_DIR );
        file_put_contents( YZMF_HW_CACHE_DIR . '/.htaccess',
            "# Permitir acceso público a las imagenes con marca de agua\n" .
            "Allow from all\n" );
    }
    YZMF_HW_Rewrite::install_rules();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
    YZMF_HW_Rewrite::remove_rules();
    flush_rewrite_rules();
} );
