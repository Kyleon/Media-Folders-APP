<?php
/**
 * Custom Post Types del portal de cliente.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_CP_CPT {

    const POST_TYPE   = 'yzmf_client_gallery';
    const ACTION_TYPE = 'yzmf_cp_action';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        // Asegurar token al guardar
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'ensure_token' ], 10, 1 );
        // Reescritura: /g/{token} → resolver galería
        add_action( 'init', [ __CLASS__, 'register_rewrites' ] );
        add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
        // Auto-flush cuando cambia la versión del plugin (no requiere
        // reactivar el plugin tras un upload por SFTP)
        add_action( 'init', [ __CLASS__, 'maybe_flush' ], 99 );
    }

    /**
     * Flush automático de rewrite rules si la versión del plugin ha cambiado
     * desde el último flush. Evita 404 tras un deploy via SFTP en el que
     * el activation hook NO se dispara.
     */
    public static function maybe_flush() {
        $stored = get_option( 'yzmf_cp_rewrites_version' );
        if ( $stored !== YZMF_CP_VERSION ) {
            self::register_rewrites();
            flush_rewrite_rules( false );
            update_option( 'yzmf_cp_rewrites_version', YZMF_CP_VERSION );
        }
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'label'             => 'Galerías de cliente',
            'labels'            => [
                'name'          => 'Galerías de cliente',
                'singular_name' => 'Galería de cliente',
                'add_new_item'  => 'Nueva galería de cliente',
                'edit_item'     => 'Editar galería',
            ],
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_rest'      => false,
            'menu_icon'         => 'dashicons-images-alt2',
            'menu_position'     => 21,
            'supports'          => [ 'title', 'author' ],
            'capability_type'   => 'post',
        ] );

        register_post_type( self::ACTION_TYPE, [
            'label'             => 'Acciones de cliente',
            'public'            => false,
            'show_ui'           => false,
            'show_in_menu'      => false,
            'supports'          => [ 'title', 'editor' ],
        ] );
    }

    public static function register_rewrites() {
        // El token es base64-urlsafe: incluye - y _ además de [A-Za-z0-9]
        add_rewrite_rule( '^g/([A-Za-z0-9_-]+)/?$', 'index.php?yzmf_cp_token=$matches[1]', 'top' );
    }

    public static function add_query_vars( $vars ) {
        $vars[] = 'yzmf_cp_token';
        return $vars;
    }

    public static function ensure_token( $post_id ) {
        if ( wp_is_post_revision( $post_id ) ) return;
        $existing = get_post_meta( $post_id, '_yzmf_cp_token', true );
        if ( ! $existing ) {
            $tok = self::generate_token();
            update_post_meta( $post_id, '_yzmf_cp_token', $tok );
        }
    }

    public static function generate_token() {
        // 12 chars URL-safe (~71 bits de entropía). Suficiente para galerías privadas.
        $bytes = random_bytes( 9 );
        return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
    }

    /* ─────────── Helpers ─────────── */

    public static function find_by_token( $token ) {
        $q = new WP_Query( [
            'post_type'      => self::POST_TYPE,
            'meta_key'       => '_yzmf_cp_token',
            'meta_value'     => $token,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'no_found_rows'  => true,
        ] );
        return $q->posts[0] ?? null;
    }

    public static function is_expired( $post ) {
        $exp = (int) get_post_meta( $post->ID, '_yzmf_cp_expires', true );
        return $exp > 0 && $exp < time();
    }

    public static function check_password( $post, $pwd ) {
        $hash = (string) get_post_meta( $post->ID, '_yzmf_cp_password', true );
        if ( ! $hash ) return true;  // sin password
        if ( ! $pwd ) return false;
        return wp_check_password( $pwd, $hash );
    }

    public static function get_images( $post ) {
        $ids = get_post_meta( $post->ID, '_yzmf_cp_images', true );
        if ( ! is_array( $ids ) ) $ids = [];
        return array_values( array_filter( array_map( 'intval', $ids ) ) );
    }

    public static function set_images( $post_id, $ids ) {
        $clean = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
        update_post_meta( $post_id, '_yzmf_cp_images', $clean );
    }

    public static function record_action( $gallery_id, $att_id, $type, $payload = '' ) {
        $title = sprintf( '[%s] att %d', $type, $att_id );
        $action_id = wp_insert_post( [
            'post_type'    => self::ACTION_TYPE,
            'post_status'  => 'publish',
            'post_parent'  => $gallery_id,
            'post_title'   => $title,
            'post_content' => is_string( $payload ) ? $payload : wp_json_encode( $payload ),
        ] );
        if ( $action_id && ! is_wp_error( $action_id ) ) {
            update_post_meta( $action_id, '_att_id', (int) $att_id );
            update_post_meta( $action_id, '_action_type', $type );
        }
        return $action_id;
    }

    public static function get_actions( $gallery_id, $type = null ) {
        $args = [
            'post_type'      => self::ACTION_TYPE,
            'post_parent'    => $gallery_id,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if ( $type ) {
            $args['meta_key']   = '_action_type';
            $args['meta_value'] = $type;
        }
        return get_posts( $args );
    }
}
