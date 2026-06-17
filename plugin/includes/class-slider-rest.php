<?php
/**
 * Endpoints REST del módulo Slider.
 * Namespace: yzmf/v1
 *
 * Rutas:
 *   GET    /sliders                 → lista (resumen)
 *   POST   /sliders                 → crear
 *   GET    /sliders/{id}            → detalle (con data completo)
 *   PUT    /sliders/{id}            → actualizar título y/o data JSON
 *   DELETE /sliders/{id}            → mover a papelera
 *   POST   /sliders/{id}/duplicate  → clonar
 *
 * Autenticación: Application Passwords (igual que el resto del plugin).
 * Permisos: capability `upload_files` (delegado a YZMF_REST::can_upload).
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Slider_REST {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        $ns = YZMF_REST::NS;

        register_rest_route( $ns, '/sliders', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_sliders' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_slider' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( $ns, '/sliders/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_slider' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_slider' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_slider' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( $ns, '/sliders/(?P<id>\d+)/duplicate', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'duplicate_slider' ],
            'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
        ] );
    }

    /* ─────────── Handlers ─────────── */

    public static function list_sliders( WP_REST_Request $req ) {
        $page     = max( 1, (int) $req->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) ( $req->get_param( 'per_page' ) ?: 50 ) ) );
        $search   = trim( (string) $req->get_param( 'search' ) );

        $args = [
            'post_type'      => YZMF_Slider::POST_TYPE,
            'post_status'    => [ 'publish', 'draft' ],
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ];
        if ( $search !== '' ) {
            $args['s'] = $search;
        }

        $q     = new WP_Query( $args );
        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = YZMF_Slider::summary( $p->ID );
        }

        $resp = new WP_REST_Response( $items );
        $resp->header( 'X-WP-Total',      (int) $q->found_posts );
        $resp->header( 'X-WP-TotalPages', (int) $q->max_num_pages );
        return $resp;
    }

    public static function create_slider( WP_REST_Request $req ) {
        $title = trim( (string) ( $req->get_param( 'title' ) ?: 'Nuevo slider' ) );

        $id = wp_insert_post( [
            'post_type'   => YZMF_Slider::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $title,
        ], true );

        if ( is_wp_error( $id ) ) {
            return new WP_Error( 'yzmf_slider_create_failed', $id->get_error_message(), [ 'status' => 500 ] );
        }

        // Inicializa con data por defecto
        YZMF_Slider::save_data( $id, YZMF_Slider::default_data() );

        return self::respond_full( $id, 201 );
    }

    public static function get_slider( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        return self::respond_full( $id );
    }

    public static function update_slider( WP_REST_Request $req ) {
        $id   = (int) $req['id'];
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== YZMF_Slider::POST_TYPE ) {
            return new WP_Error( 'yzmf_slider_not_found', 'No existe', [ 'status' => 404 ] );
        }

        $params = $req->get_json_params();
        if ( ! is_array( $params ) ) {
            $params = $req->get_params();
        }

        // Título (opcional)
        if ( isset( $params['title'] ) ) {
            wp_update_post( [
                'ID'         => $id,
                'post_title' => sanitize_text_field( $params['title'] ),
            ] );
        }

        // Data JSON (opcional)
        if ( isset( $params['data'] ) ) {
            $result = YZMF_Slider::save_data( $id, $params['data'] );
            if ( is_wp_error( $result ) ) return $result;
        }

        return self::respond_full( $id );
    }

    public static function delete_slider( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== YZMF_Slider::POST_TYPE ) {
            return new WP_Error( 'yzmf_slider_not_found', 'No existe', [ 'status' => 404 ] );
        }

        $force = (bool) $req->get_param( 'force' );
        $result = $force ? wp_delete_post( $id, true ) : wp_trash_post( $id );

        if ( ! $result ) {
            return new WP_Error( 'yzmf_slider_delete_failed', 'No se pudo eliminar', [ 'status' => 500 ] );
        }

        return rest_ensure_response( [
            'id'      => $id,
            'deleted' => true,
            'force'   => $force,
        ] );
    }

    public static function duplicate_slider( WP_REST_Request $req ) {
        $id     = (int) $req['id'];
        $new_id = YZMF_Slider::duplicate( $id );

        if ( is_wp_error( $new_id ) ) return $new_id;

        return self::respond_full( $new_id, 201 );
    }

    /* ─────────── Helpers ─────────── */

    private static function respond_full( $id, $status = 200 ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== YZMF_Slider::POST_TYPE ) {
            return new WP_Error( 'yzmf_slider_not_found', 'No existe', [ 'status' => 404 ] );
        }

        $resp = rest_ensure_response( [
            'id'       => $id,
            'title'    => $post->post_title,
            'status'   => $post->post_status,
            'modified' => mysql_to_rfc3339( $post->post_modified_gmt ),
            'data'     => YZMF_Slider::get_data( $id ),
        ] );
        $resp->set_status( $status );
        return $resp;
    }
}
