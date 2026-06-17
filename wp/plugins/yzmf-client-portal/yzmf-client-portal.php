<?php
/**
 * Plugin Name: YZMF Client Portal
 * Plugin URI:  https://yezraelperez.es
 * Description: Galerías privadas para clientes con token único, favoritas, comentarios y descarga selectiva. Sin necesidad de cuenta de WordPress para el cliente.
 * Version:     1.0.4
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Tested up to: 6.7
 * Author:      Yezrael Pérez
 * License:     GPL-2.0+
 * Text Domain: yzmf-cp
 *
 * Estructura:
 *  - CPT  yzmf_client_gallery: contiene la configuración de cada galería
 *  - Meta _yzmf_cp_token: token público único para acceso (URL /g/{token})
 *  - Meta _yzmf_cp_password: hash bcrypt opcional
 *  - Meta _yzmf_cp_expires: timestamp de expiración opcional
 *  - Meta _yzmf_cp_images: array de attachment IDs
 *  - Meta _yzmf_cp_allow_download: boolean
 *  - Meta _yzmf_cp_client_email: email del cliente (para notificaciones)
 *  - Meta _yzmf_cp_views: contador de visitas
 *
 *  - CPT  yzmf_cp_action: registra cada acción (favorita / comentario) del cliente
 *    con post_parent = ID de la galería, meta _att_id, _action_type, _payload
 */

defined( 'ABSPATH' ) || exit;

define( 'YZMF_CP_VERSION', '1.0.4' );
define( 'YZMF_CP_PATH',    plugin_dir_path( __FILE__ ) );
define( 'YZMF_CP_URL',     plugin_dir_url( __FILE__ ) );

require_once YZMF_CP_PATH . 'includes/class-cpt.php';
require_once YZMF_CP_PATH . 'includes/class-rest.php';
require_once YZMF_CP_PATH . 'includes/class-frontend.php';
require_once YZMF_CP_PATH . 'includes/class-admin.php';

add_action( 'plugins_loaded', function () {
    YZMF_CP_CPT::init();
    YZMF_CP_REST::init();
    YZMF_CP_Frontend::init();
    YZMF_CP_Admin::init();
} );

register_activation_hook( __FILE__, function () {
    YZMF_CP_CPT::register();
    flush_rewrite_rules();
} );
