<?php
/**
 * Plugin Name: YZ Media Folders
 * Plugin URI:  https://nubedocs.es
 * Description: Gestor de medios propio con carpetas, drag & drop, modal de edición y REST API. Independiente de la librería nativa de WordPress.
 * Version:     2.4.0
 * Author:      Yezrael Pérez · Nubedocs
 * Author URI:  https://nubedocs.es
 * License:     GPL-2.0+
 * Text Domain: yz-media-folders
 */

defined( 'ABSPATH' ) || exit;

define( 'YZMF_VERSION',  '2.4.0' );
define( 'YZMF_PATH',     plugin_dir_path( __FILE__ ) );
define( 'YZMF_URL',      plugin_dir_url( __FILE__ ) );
define( 'YZMF_TAXONOMY', 'yz_media_folder' );

require_once YZMF_PATH . 'includes/class-taxonomy.php';
require_once YZMF_PATH . 'includes/class-ajax.php';
require_once YZMF_PATH . 'includes/class-admin.php';
require_once YZMF_PATH . 'includes/class-map.php';
require_once YZMF_PATH . 'includes/class-rest.php';
require_once YZMF_PATH . 'includes/class-portfolio-bridge.php';

add_action( 'plugins_loaded', function () {
    YZMF_Taxonomy::init();
    YZMF_Ajax::init();
    YZMF_Admin::init();
    YZMF_Map::init();
    YZMF_REST::init();
    YZMF_Portfolio_Bridge::init();
} );

register_activation_hook( __FILE__, function () {
    // La taxonomía se registra en init; aquí solo refrescamos rewrite rules.
    flush_rewrite_rules();
} );
