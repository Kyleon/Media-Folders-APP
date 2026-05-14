<?php
/**
 * Endpoints REST del plugin YZ Media Folders.
 * Namespace: yzmf/v1
 *
 * Permite consumir el plugin desde aplicaciones externas (PWA móvil, etc.)
 * usando Application Passwords o cookies de sesión.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_REST {

    const NS = 'yzmf/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
        add_action( 'rest_api_init', [ __CLASS__, 'enable_cors' ], 15 );
    }

    /**
     * CORS para que la PWA externa (app.yezraelperez.es) pueda llamar al REST.
     * Lista blanca configurable vía option `yzmf_cors_origins` (CSV de orígenes).
     * Maneja preflight OPTIONS y permite Authorization (Application Passwords).
     */
    public static function enable_cors() {
        $defaults = 'https://app.yezraelperez.es';
        $raw      = get_option( 'yzmf_cors_origins', $defaults );
        $allowed  = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

        $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( $_SERVER['HTTP_ORIGIN'] ) : '';
        if ( ! $origin || ! in_array( $origin, $allowed, true ) ) return;

        // Quitar el filtro por defecto de WP que envía '*'
        remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

        add_filter( 'rest_pre_serve_request', function ( $value ) use ( $origin ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
            header( 'Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages' );
            header( 'Vary: Origin' );
            return $value;
        } );

        // Responder al preflight OPTIONS sin entrar al ruteo
        if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Credentials: true' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce' );
            header( 'Access-Control-Max-Age: 600' );
            status_header( 204 );
            exit;
        }
    }

    /* ─────────── Permission callbacks ─────────── */

    public static function can_upload() {
        return current_user_can( 'upload_files' );
    }

    public static function can_delete() {
        return current_user_can( 'delete_posts' );
    }

    public static function can_manage() {
        return current_user_can( 'manage_options' );
    }

    /* ─────────── Routes ─────────── */

    public static function register_routes() {

        // ── FOLDERS ──────────────────────────────────────────────
        register_rest_route( self::NS, '/folders', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_folders' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_folder' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/folders/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_folder' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_folder' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/folders/(?P<id>\d+)/images', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'folder_images' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        // ── MEDIA ────────────────────────────────────────────────
        register_rest_route( self::NS, '/media', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_media' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'upload_media' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/media/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_media' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_media' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_media' ],
                'permission_callback' => [ __CLASS__, 'can_delete' ],
            ],
        ] );

        register_rest_route( self::NS, '/media/(?P<id>\d+)/folder', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'set_media_folder' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        // ── GEO ──────────────────────────────────────────────────
        register_rest_route( self::NS, '/media/(?P<id>\d+)/geo', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'set_media_geo' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/geo/bulk', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'set_media_geo_bulk' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/geo/all', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_media_with_geo' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/bulk-rename', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'bulk_rename' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/bulk-rename/preview', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'bulk_rename_preview' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/(?P<id>\d+)/ai', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'media_ai' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        // ── MAP ──────────────────────────────────────────────────
        register_rest_route( self::NS, '/map/data', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'map_data_public' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NS, '/map/locations', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_locations' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'save_location' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/map/locations/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'save_location' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_location' ],
                'permission_callback' => [ __CLASS__, 'can_upload' ],
            ],
        ] );

        // ── STATS ────────────────────────────────────────────────
        register_rest_route( self::NS, '/stats', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_stats' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/stats/exif', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_stats_exif' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/tags', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_tags' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/colors', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_colors' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/media/(?P<id>\d+)/palette', [
            'methods'             => 'PUT',
            'callback'            => [ __CLASS__, 'set_media_palette' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        // ── GEOCODING (proxy de Nominatim) ──────────────────────
        register_rest_route( self::NS, '/geocode/search', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'geocode_search' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/geocode/reverse', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'geocode_reverse' ],
            'permission_callback' => [ __CLASS__, 'can_upload' ],
        ] );
    }

    /* ─────────── FOLDERS ─────────── */

    public static function list_folders( WP_REST_Request $req ) {
        $tree = YZMF_Taxonomy::get_tree();
        return rest_ensure_response( $tree );
    }

    public static function create_folder( WP_REST_Request $req ) {
        $name   = sanitize_text_field( $req->get_param( 'name' ) );
        $parent = intval( $req->get_param( 'parent' ) ?: 0 );
        if ( ! $name ) return new WP_Error( 'yzmf_missing_name', 'Nombre requerido', [ 'status' => 400 ] );
        $r = wp_insert_term( $name, YZMF_TAXONOMY, [ 'parent' => $parent ] );
        if ( is_wp_error( $r ) ) return $r;
        $t = get_term( $r['term_id'], YZMF_TAXONOMY );
        return rest_ensure_response( [
            'id' => $t->term_id, 'name' => $t->name, 'parent' => $t->parent, 'count' => 0,
        ] );
    }

    public static function update_folder( WP_REST_Request $req ) {
        $id     = intval( $req['id'] );
        $name   = $req->get_param( 'name' );
        $parent = $req->get_param( 'parent' );

        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );

        $args = [];
        if ( $name !== null )   $args['name']   = sanitize_text_field( $name );
        if ( $parent !== null ) {
            $parent = intval( $parent );
            // No puede ser hijo de sí mismo
            if ( $parent === $id ) {
                return new WP_Error( 'yzmf_invalid_parent', 'Una carpeta no puede ser su propio padre.', [ 'status' => 400 ] );
            }
            // No puede ser hijo de sus descendientes
            if ( $parent > 0 ) {
                $descendants = get_term_children( $id, YZMF_TAXONOMY );
                if ( ! is_wp_error( $descendants ) && in_array( $parent, (array) $descendants, true ) ) {
                    return new WP_Error( 'yzmf_invalid_parent', 'No puede mover una carpeta a uno de sus descendientes.', [ 'status' => 400 ] );
                }
                if ( ! get_term( $parent, YZMF_TAXONOMY ) ) {
                    return new WP_Error( 'yzmf_invalid_parent', 'La carpeta padre no existe.', [ 'status' => 400 ] );
                }
            }
            $args['parent'] = $parent;
        }

        if ( empty( $args ) ) return new WP_Error( 'yzmf_invalid', 'Sin cambios', [ 'status' => 400 ] );

        $r = wp_update_term( $id, YZMF_TAXONOMY, $args );
        if ( is_wp_error( $r ) ) return $r;
        return rest_ensure_response( [ 'id' => $id ] + $args );
    }

    public static function delete_folder( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );
        $atts = get_posts( [
            'post_type' => 'attachment', 'posts_per_page' => -1, 'fields' => 'ids',
            'tax_query' => [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $id ] ],
        ] );
        foreach ( $atts as $att ) wp_remove_object_terms( $att, $id, YZMF_TAXONOMY );
        $r = wp_delete_term( $id, YZMF_TAXONOMY );
        if ( is_wp_error( $r ) ) return $r;
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    public static function folder_images( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        $req->set_param( 'folder', $id );
        return self::list_media( $req );
    }

    /* ─────────── MEDIA ─────────── */

    public static function list_media( WP_REST_Request $req ) {
        $folder   = $req->get_param( 'folder' );
        $folder   = $folder !== null ? intval( $folder ) : -1;
        $paged    = max( 1, intval( $req->get_param( 'page' ) ?: 1 ) );
        $perpage  = min( 100, max( 1, intval( $req->get_param( 'per_page' ) ?: 40 ) ) );
        $search   = sanitize_text_field( $req->get_param( 'search' ) ?: '' );
        $orderby  = sanitize_key( $req->get_param( 'orderby' ) ?: 'date' );
        $order    = strtoupper( $req->get_param( 'order' ) ?: 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        $mime_in  = sanitize_text_field( $req->get_param( 'mime' ) ?: '' );

        if ( ! in_array( $orderby, [ 'date', 'title', 'size' ], true ) ) $orderby = 'date';

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $perpage,
            'paged'          => $paged,
            'order'          => $order,
        ];
        if ( $orderby === 'size' ) {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_yzmf_filesize';
        } else {
            $args['orderby'] = $orderby;
        }

        $tag   = sanitize_text_field( $req->get_param( 'tag' ) ?: '' );
        $color = sanitize_text_field( $req->get_param( 'color' ) ?: '' );
        $meta_q = [];

        if ( $search === '__NO_ALT__' ) {
            $meta_q[] = [
                'relation' => 'OR',
                [ 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_wp_attachment_image_alt', 'value' => '', 'compare' => '=' ],
            ];
            $search = '';
        }
        if ( $search ) $args['s'] = $search;
        if ( $mime_in ) {
            $map = [ 'image' => 'image/', 'video' => 'video/', 'pdf' => 'application/pdf', 'audio' => 'audio/' ];
            if ( isset( $map[ $mime_in ] ) ) $args['post_mime_type'] = $map[ $mime_in ];
        }
        if ( $tag ) {
            $meta_q[] = [ 'key' => '_yzmf_ai_tags', 'value' => '"' . $tag . '"', 'compare' => 'LIKE' ];
        }
        if ( $color && preg_match( '/^#?[0-9A-Fa-f]{6}$/', $color ) ) {
            $hex = strtoupper( ltrim( $color, '#' ) );
            $meta_q[] = [ 'key' => '_yzmf_color_palette', 'value' => $hex, 'compare' => 'LIKE' ];
        }
        if ( ! empty( $meta_q ) ) {
            $meta_q['relation'] = 'AND';
            $args['meta_query'] = $meta_q;
        }

        if ( $folder === 0 ) {
            $args['tax_query'] = [ [ 'taxonomy' => YZMF_TAXONOMY, 'operator' => 'NOT EXISTS' ] ];
        } elseif ( $folder > 0 ) {
            $args['tax_query'] = [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $folder, 'include_children' => true ] ];
        }

        $q   = new WP_Query( $args );
        $ids = wp_list_pluck( $q->posts, 'ID' );
        if ( ! empty( $ids ) ) {
            update_meta_cache( 'post', $ids );
            update_object_term_cache( $ids, 'attachment' );
        }
        return rest_ensure_response( [
            'images'  => array_map( [ 'YZMF_Ajax', 'format_image' ], $q->posts ),
            'total'   => $q->found_posts,
            'pages'   => (int) $q->max_num_pages,
            'current' => $paged,
        ] );
    }

    public static function get_media( WP_REST_Request $req ) {
        $id  = intval( $req['id'] );
        $att = get_post( $id );
        if ( ! $att || $att->post_type !== 'attachment' ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        return rest_ensure_response( YZMF_Ajax::format_image( $att ) );
    }

    public static function update_media( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id || get_post_type( $id ) !== 'attachment' ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        $alt         = $req->get_param( 'alt' );
        $seo_title   = $req->get_param( 'seo_title' );
        $title       = $req->get_param( 'title' );
        $caption     = $req->get_param( 'caption' );
        $description = $req->get_param( 'description' );

        if ( $alt !== null )       update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        if ( $seo_title !== null ) update_post_meta( $id, '_yzmf_seo_title',          sanitize_text_field( $seo_title ) );

        $pd = [ 'ID' => $id ];
        if ( $title !== null )       $pd['post_title']   = sanitize_text_field( $title );
        if ( $caption !== null )     $pd['post_excerpt'] = sanitize_textarea_field( $caption );
        if ( $description !== null ) $pd['post_content'] = wp_kses_post( $description );
        if ( count( $pd ) > 1 )      wp_update_post( $pd );

        return rest_ensure_response( YZMF_Ajax::format_image( get_post( $id ) ) );
    }

    public static function delete_media( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );
        $ok = wp_delete_attachment( $id, false );
        if ( ! $ok ) return new WP_Error( 'yzmf_delete_failed', 'No se pudo eliminar', [ 'status' => 500 ] );
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    public static function set_media_folder( WP_REST_Request $req ) {
        $id        = intval( $req['id'] );
        $folder_id = intval( $req->get_param( 'folder_id' ) );
        if ( ! $id || get_post_type( $id ) !== 'attachment' ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        if ( $folder_id > 0 ) {
            if ( ! get_term( $folder_id, YZMF_TAXONOMY ) ) {
                return new WP_Error( 'yzmf_invalid_folder', 'Carpeta inexistente', [ 'status' => 400 ] );
            }
            wp_set_object_terms( $id, [ $folder_id ], YZMF_TAXONOMY );
        } else {
            wp_delete_object_term_relationships( $id, YZMF_TAXONOMY );
        }
        return rest_ensure_response( [ 'id' => $id, 'folder_id' => $folder_id ] );
    }

    public static function upload_media( WP_REST_Request $req ) {
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        $files = $req->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new WP_Error( 'yzmf_no_file', 'Sin archivo', [ 'status' => 400 ] );
        }
        $_FILES['file'] = $files['file'];
        $att_id = media_handle_upload( 'file', 0 );
        if ( is_wp_error( $att_id ) ) return $att_id;

        $folder_id = intval( $req->get_param( 'folder_id' ) ?: 0 );
        if ( $folder_id > 0 && get_term( $folder_id, YZMF_TAXONOMY ) ) {
            wp_set_object_terms( $att_id, [ $folder_id ], YZMF_TAXONOMY );
        }
        return rest_ensure_response( YZMF_Ajax::format_image( get_post( $att_id ) ) );
    }

    public static function set_media_geo( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id || get_post_type( $id ) !== 'attachment' ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        return rest_ensure_response( self::apply_geo_to_id( $id, $req->get_params() ) );
    }

    public static function set_media_geo_bulk( WP_REST_Request $req ) {
        $ids = array_filter( array_map( 'intval', (array) $req->get_param( 'ids' ) ?: [] ) );
        if ( ! $ids ) return new WP_Error( 'yzmf_invalid', 'Sin IDs', [ 'status' => 400 ] );
        $params = $req->get_params();
        $done = 0; $errors = [];
        foreach ( $ids as $id ) {
            if ( get_post_type( $id ) !== 'attachment' ) {
                $errors[] = [ 'id' => $id, 'error' => 'No attachment' ];
                continue;
            }
            self::apply_geo_to_id( $id, $params );
            $done++;
        }
        return rest_ensure_response( [
            'updated' => $done,
            'total'   => count( $ids ),
            'errors'  => $errors,
        ] );
    }

    /* ─────────── BULK RENAME ─────────── */

    /**
     * Operaciones soportadas:
     *  - replace          { find, replace, regex, case_sensitive }
     *  - prefix           { value }
     *  - suffix           { value }
     *  - sequence         { pattern, start, padding, order }   pattern usa {n}
     *  - from_filename    { strip_ext, separator_to_space }
     *  - from_alt         {}
     *  - case             { mode: lower|upper|title|sentence }
     *  - trim             {}
     *
     * Devuelve cada cambio: { id, old, new }
     */
    public static function bulk_rename_preview( WP_REST_Request $req ) {
        return rest_ensure_response( self::compute_bulk_rename( $req, false ) );
    }

    public static function bulk_rename( WP_REST_Request $req ) {
        $changes = self::compute_bulk_rename( $req, true );
        return rest_ensure_response( $changes );
    }

    private static function compute_bulk_rename( WP_REST_Request $req, $apply ) {
        $ids       = array_values( array_filter( array_map( 'intval', (array) $req->get_param( 'ids' ) ?: [] ) ) );
        $operation = sanitize_key( (string) $req->get_param( 'operation' ) );
        $params    = (array) ( $req->get_param( 'params' ) ?: [] );

        if ( ! $ids ) return [ 'updated' => 0, 'changes' => [], 'errors' => [ 'Sin IDs' ] ];
        if ( ! $operation ) return [ 'updated' => 0, 'changes' => [], 'errors' => [ 'Operación requerida' ] ];

        // Para sequence necesitamos el orden. Si viene 'order=date_asc' o similar, lo aplicamos.
        $order = (string) ( $params['order'] ?? '' );
        if ( $operation === 'sequence' && in_array( $order, [ 'date_asc', 'date_desc', 'title_asc', 'title_desc' ], true ) ) {
            $orderby_map = [
                'date_asc'   => [ 'date', 'ASC' ],
                'date_desc'  => [ 'date', 'DESC' ],
                'title_asc'  => [ 'title', 'ASC' ],
                'title_desc' => [ 'title', 'DESC' ],
            ];
            list( $orderby, $dir ) = $orderby_map[ $order ];
            $q = new WP_Query( [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post__in'       => $ids,
                'orderby'        => $orderby,
                'order'          => $dir,
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ] );
            $ids = array_map( 'intval', $q->posts );
        }

        $changes = [];
        $errors  = [];
        $i = 0;
        foreach ( $ids as $id ) {
            if ( get_post_type( $id ) !== 'attachment' ) {
                $errors[] = [ 'id' => $id, 'error' => 'No attachment' ];
                continue;
            }
            $post = get_post( $id );
            $old  = (string) $post->post_title;
            $new  = self::apply_rename_op( $old, $id, $operation, $params, $i );
            if ( $new === null ) {
                $errors[] = [ 'id' => $id, 'error' => 'Operación inválida' ];
                continue;
            }
            $i++;
            // Solo reportamos los cambios efectivos
            if ( $new !== $old ) {
                $changes[] = [ 'id' => $id, 'old' => $old, 'new' => $new ];
                if ( $apply ) {
                    wp_update_post( [ 'ID' => $id, 'post_title' => $new ] );
                }
            }
        }

        return [
            'applied'  => $apply,
            'updated'  => count( $changes ),
            'total'    => count( $ids ),
            'changes'  => $changes,
            'errors'   => $errors,
        ];
    }

    private static function apply_rename_op( $old, $id, $op, $params, $index ) {
        switch ( $op ) {
            case 'replace': {
                $find    = (string) ( $params['find'] ?? '' );
                $replace = (string) ( $params['replace'] ?? '' );
                $regex   = ! empty( $params['regex'] );
                $cs      = ! empty( $params['case_sensitive'] );
                if ( $find === '' ) return $old;
                if ( $regex ) {
                    // Delimitador # con flags. Si el regex es inválido, devolvemos $old.
                    $flags = $cs ? '' : 'i';
                    $flags .= 'u';
                    $pattern = '#' . str_replace( '#', '\\#', $find ) . '#' . $flags;
                    $r = @preg_replace( $pattern, $replace, $old );
                    return is_string( $r ) ? $r : $old;
                }
                return $cs ? str_replace( $find, $replace, $old ) : str_ireplace( $find, $replace, $old );
            }
            case 'prefix': {
                $v = (string) ( $params['value'] ?? '' );
                return $v . $old;
            }
            case 'suffix': {
                $v = (string) ( $params['value'] ?? '' );
                return $old . $v;
            }
            case 'sequence': {
                $pattern = (string) ( $params['pattern'] ?? '{n}' );
                $start   = (int) ( $params['start'] ?? 1 );
                $pad     = (int) ( $params['padding'] ?? 0 );
                $n = $index + $start;
                $num = $pad > 0 ? str_pad( (string) $n, $pad, '0', STR_PAD_LEFT ) : (string) $n;
                return str_replace( '{n}', $num, $pattern );
            }
            case 'from_filename': {
                $path = get_attached_file( $id );
                $name = $path ? basename( $path ) : '';
                if ( ! empty( $params['strip_ext'] ) ) {
                    $dot = strrpos( $name, '.' );
                    if ( $dot !== false ) $name = substr( $name, 0, $dot );
                }
                if ( ! empty( $params['separator_to_space'] ) ) {
                    $name = preg_replace( '/[_-]+/', ' ', $name );
                    $name = preg_replace( '/\s+/', ' ', $name );
                    $name = trim( $name );
                }
                return $name;
            }
            case 'from_alt': {
                $alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
                return $alt !== '' ? $alt : $old;
            }
            case 'case': {
                $mode = (string) ( $params['mode'] ?? 'title' );
                switch ( $mode ) {
                    case 'lower':    return mb_strtolower( $old, 'UTF-8' );
                    case 'upper':    return mb_strtoupper( $old, 'UTF-8' );
                    case 'sentence': {
                        $s = mb_strtolower( $old, 'UTF-8' );
                        return mb_strtoupper( mb_substr( $s, 0, 1, 'UTF-8' ), 'UTF-8' ) . mb_substr( $s, 1, null, 'UTF-8' );
                    }
                    case 'title':
                    default: {
                        // Title Case respetando UTF-8
                        return mb_convert_case( $old, MB_CASE_TITLE, 'UTF-8' );
                    }
                }
            }
            case 'trim': {
                return trim( preg_replace( '/\s+/u', ' ', $old ) );
            }
            default:
                return null;
        }
    }

    /**
     * Aplica datos de geo a un attachment. Si lat/lng vienen vacíos o nulos,
     * se elimina la geo. Devuelve el image formateado.
     */
    private static function apply_geo_to_id( $id, $params ) {
        $lat   = $params['lat']   ?? null;
        $lng   = $params['lng']   ?? null;
        $place = $params['place'] ?? null;

        $clearing = ( $lat === '' || $lng === '' || $lat === null || $lng === null );
        if ( $clearing ) {
            delete_post_meta( $id, '_yzmf_geo_lat' );
            delete_post_meta( $id, '_yzmf_geo_lng' );
            delete_post_meta( $id, '_yzmf_geo_place' );
            delete_post_meta( $id, '_yzmf_geo_source' );
        } else {
            $lat_f = floatval( $lat );
            $lng_f = floatval( $lng );
            // Validación: rangos legales
            if ( $lat_f < -90 || $lat_f > 90 || $lng_f < -180 || $lng_f > 180 ) {
                return [ 'error' => 'Coordenadas fuera de rango' ];
            }
            update_post_meta( $id, '_yzmf_geo_lat', round( $lat_f, 6 ) );
            update_post_meta( $id, '_yzmf_geo_lng', round( $lng_f, 6 ) );
            if ( $place !== null ) {
                update_post_meta( $id, '_yzmf_geo_place', sanitize_text_field( $place ) );
            }
            update_post_meta( $id, '_yzmf_geo_source', 'manual' );
        }

        return YZMF_Ajax::format_image( get_post( $id ) );
    }

    /**
     * Lista todas las imágenes con geo asignada. Para mostrar en mapa.
     * Devuelve [{ id, lat, lng, thumb, title, place, source, folder_ids }]
     */
    public static function list_media_with_geo( WP_REST_Request $req ) {
        $limit = min( 2000, max( 1, intval( $req->get_param( 'limit' ) ?: 1000 ) ) );

        $q = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_yzmf_geo_lat', 'compare' => 'EXISTS' ],
                [ 'key' => '_yzmf_geo_lng', 'compare' => 'EXISTS' ],
            ],
            'fields' => 'ids',
        ] );

        if ( ! empty( $q->posts ) ) {
            update_meta_cache( 'post', $q->posts );
        }

        $out = [];
        foreach ( $q->posts as $id ) {
            $lat = get_post_meta( $id, '_yzmf_geo_lat', true );
            $lng = get_post_meta( $id, '_yzmf_geo_lng', true );
            if ( $lat === '' || $lng === '' ) continue;
            // Servimos varios tamaños:
            //  - thumb (150) para mosaicos
            //  - medium (~768) y large (~1024) intermedios
            //  - full = archivo original tal cual lo subió el usuario
            // Cuando un tamaño intermedio no existe, WP devuelve el original,
            // así que los fallbacks se gestionan en el cliente.
            $full = wp_get_attachment_url( $id );
            $out[] = [
                'id'     => $id,
                'lat'    => (float) $lat,
                'lng'    => (float) $lng,
                'thumb'  => wp_get_attachment_image_url( $id, 'thumbnail' ) ?: $full,
                'medium' => wp_get_attachment_image_url( $id, 'medium' )    ?: $full,
                'large'  => wp_get_attachment_image_url( $id, 'large' )     ?: $full,
                'full'   => $full,
                'url'    => $full,                                                        // alias
                'title'  => get_the_title( $id ),
                'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
                'place'  => get_post_meta( $id, '_yzmf_geo_place', true ),
                'source' => get_post_meta( $id, '_yzmf_geo_source', true ) ?: 'manual',
            ];
        }
        return rest_ensure_response( $out );
    }

    public static function media_ai( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );
        $r = YZMF_Ajax::generate_ai_for_image( $id );
        if ( $r['success'] ) return rest_ensure_response( $r['data'] );
        return new WP_Error( 'yzmf_ai_failed', $r['data']['message'] ?? 'Error', [ 'status' => 500 ] );
    }

    /* ─────────── MAP ─────────── */

    public static function map_data_public( WP_REST_Request $req ) {
        $cache = get_transient( 'yzmf_map_public_data' );
        if ( $cache === false ) {
            $cache = YZMF_Map::get_map_data();
            set_transient( 'yzmf_map_public_data', $cache, 15 * MINUTE_IN_SECONDS );
        }
        $resp = rest_ensure_response( $cache );
        $resp->header( 'Cache-Control', 'public, max-age=600' );
        return $resp;
    }

    public static function list_locations( WP_REST_Request $req ) {
        return rest_ensure_response( YZMF_Map::get_all_locations() );
    }

    public static function save_location( WP_REST_Request $req ) {
        $id = isset( $req['id'] ) ? intval( $req['id'] ) : 0;
        if ( $id && get_post_type( $id ) !== YZMF_Map::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Ubicación inexistente', [ 'status' => 404 ] );
        }
        $name = sanitize_text_field( $req->get_param( 'name' ) );
        if ( ! $name ) return new WP_Error( 'yzmf_missing_name', 'Nombre requerido', [ 'status' => 400 ] );

        $post = [
            'post_type'   => YZMF_Map::CPT,
            'post_status' => 'publish',
            'post_title'  => $name,
        ];
        if ( $id ) { $post['ID'] = $id; $id = wp_update_post( $post ); }
        else        { $id = wp_insert_post( $post ); }
        if ( is_wp_error( $id ) ) return $id;

        update_post_meta( $id, '_yzmf_lat',         floatval( $req->get_param( 'lat' ) ) );
        update_post_meta( $id, '_yzmf_lng',         floatval( $req->get_param( 'lng' ) ) );
        update_post_meta( $id, '_yzmf_tag',         sanitize_text_field( $req->get_param( 'tag' ) ?: '' ) );
        update_post_meta( $id, '_yzmf_description', sanitize_textarea_field( $req->get_param( 'description' ) ?: '' ) );
        update_post_meta( $id, '_yzmf_gallery_url', esc_url_raw( $req->get_param( 'gallery_url' ) ?: '' ) );
        update_post_meta( $id, '_yzmf_folder_ids',  array_map( 'intval', (array) ( $req->get_param( 'folder_ids' ) ?: [] ) ) );
        update_post_meta( $id, '_yzmf_photo_ids',   array_map( 'intval', (array) ( $req->get_param( 'photo_ids' ) ?: [] ) ) );
        if ( $req->get_param( 'hero_id' ) ) set_post_thumbnail( $id, intval( $req->get_param( 'hero_id' ) ) );

        // Portfolios vinculados — viceversa: escribimos el meta location_id en cada portfolio.
        $portfolio_ids = $req->get_param( 'portfolio_ids' );
        if ( $portfolio_ids !== null && class_exists( 'YZMF_Portfolio_Bridge' ) ) {
            YZMF_Portfolio_Bridge::set_portfolios_for_location( $id, (array) $portfolio_ids );
        }

        delete_transient( 'yzmf_map_public_data' );
        return rest_ensure_response( [ 'id' => $id ] );
    }

    public static function delete_location( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id || get_post_type( $id ) !== YZMF_Map::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        wp_delete_post( $id, true );
        delete_transient( 'yzmf_map_public_data' );
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    /* ─────────── STATS ─────────── */

    public static function get_stats( WP_REST_Request $req ) {
        global $wpdb;

        // Cache corto. Si llega ?fresh=1 (PullRefresh en la PWA), saltamos
        // el cache para devolver datos al instante.
        $force_fresh = (bool) $req->get_param( 'fresh' );
        if ( ! $force_fresh ) {
            $cache = get_transient( 'yzmf_stats_cache' );
            if ( $cache !== false ) {
                return rest_ensure_response( $cache );
            }
        }

        $totals = [
            'media'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment'" ),
            'images'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'" ),
            'folders'    => (int) wp_count_terms( [ 'taxonomy' => YZMF_TAXONOMY, 'hide_empty' => false ] ),
            'portfolios' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='portfolio' AND post_status IN ('publish','draft','private','pending')" ),
            'locations'  => (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type=%s AND post_status='publish'",
                YZMF_Map::CPT
            ) ),
        ];

        // Espacio en disco usado por attachments (suma de _yzmf_filesize)
        $totals['storage_bytes'] = (int) $wpdb->get_var( "
            SELECT SUM(meta_value)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_yzmf_filesize'
        " );
        $totals['storage_h'] = size_format( $totals['storage_bytes'] ?: 0 );

        // Subidas por día — últimos 365 días (alimenta sparkline 30d y heatmap calendario)
        $rows = $wpdb->get_results( "
            SELECT DATE(post_date) AS d, COUNT(*) AS n
            FROM {$wpdb->posts}
            WHERE post_type='attachment'
              AND post_date >= DATE_SUB(NOW(), INTERVAL 365 DAY)
            GROUP BY DATE(post_date)
            ORDER BY d ASC
        " );
        $map = [];
        foreach ( $rows as $r ) $map[ $r->d ] = (int) $r->n;

        $by_day_365 = [];
        $cursor = strtotime( '-364 days', strtotime( date( 'Y-m-d' ) ) );
        $end    = strtotime( date( 'Y-m-d' ) );
        while ( $cursor <= $end ) {
            $d = date( 'Y-m-d', $cursor );
            $by_day_365[] = [ 'date' => $d, 'count' => $map[ $d ] ?? 0 ];
            $cursor = strtotime( '+1 day', $cursor );
        }
        // Subset de los últimos 30 días para la sparkline existente
        $by_day = array_slice( $by_day_365, -30 );

        // Top 5 carpetas con más imágenes
        $top_folders_terms = get_terms( [
            'taxonomy'   => YZMF_TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'number'     => 5,
        ] );
        $top_folders = [];
        if ( ! is_wp_error( $top_folders_terms ) ) {
            foreach ( $top_folders_terms as $t ) {
                $top_folders[] = [
                    'id'    => $t->term_id,
                    'name'  => $t->name,
                    'count' => (int) $t->count,
                ];
            }
        }

        // Imágenes sin alt text (importante para accesibilidad/SEO)
        $missing_alt = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
                   ON pm.post_id = p.ID AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type='attachment'
              AND p.post_mime_type LIKE 'image/%'
              AND ( pm.meta_id IS NULL OR pm.meta_value = '' )
        " );

        // Portfolios sin imagen destacada
        $portfolios_no_thumb = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
                   ON pm.post_id = p.ID AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_type='portfolio'
              AND p.post_status IN ('publish','draft','private','pending')
              AND pm.meta_id IS NULL
        " );

        // Imágenes sin geolocalización
        $missing_geo = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
                   ON pm.post_id = p.ID AND pm.meta_key = '_yzmf_geo_lat'
            WHERE p.post_type='attachment'
              AND p.post_mime_type LIKE 'image/%'
              AND ( pm.meta_id IS NULL OR pm.meta_value = '' )
        " );

        // Imágenes sin tags IA
        $missing_tags = (int) $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm
                   ON pm.post_id = p.ID AND pm.meta_key = '_yzmf_ai_tags'
            WHERE p.post_type='attachment'
              AND p.post_mime_type LIKE 'image/%'
              AND ( pm.meta_id IS NULL OR pm.meta_value = '' )
        " );

        // Imágenes sin carpeta asignada
        $missing_folder = (int) $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
            WHERE p.post_type='attachment'
              AND p.post_mime_type LIKE 'image/%%'
              AND tt.term_taxonomy_id IS NULL
        ", YZMF_TAXONOMY ) );

        $stats = [
            'totals'       => $totals,
            'uploads_30d'  => $by_day,
            'uploads_365d' => $by_day_365,
            'top_folders'  => $top_folders,
            'health'       => [
                'missing_alt'         => $missing_alt,
                'missing_geo'         => $missing_geo,
                'missing_tags'        => $missing_tags,
                'missing_folder'      => $missing_folder,
                'portfolios_no_thumb' => $portfolios_no_thumb,
            ],
        ];

        // Cache corto (60s) — el PullRefresh con ?fresh=1 lo salta
        set_transient( 'yzmf_stats_cache', $stats, 60 );
        return rest_ensure_response( $stats );
    }

    /**
     * Histograma de datos EXIF: cámaras, lentes (focal), aperturas, ISOs.
     * Recorre todas las imágenes y agrega valores. Cache 30 min.
     */
    public static function get_stats_exif( WP_REST_Request $req ) {
        $cache = get_transient( 'yzmf_stats_exif_cache' );
        if ( $cache !== false ) {
            return rest_ensure_response( $cache );
        }

        global $wpdb;
        // Cogemos todos los _wp_attachment_metadata de imágenes
        $rows = $wpdb->get_col( "
            SELECT pm.meta_value
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE pm.meta_key = '_wp_attachment_metadata'
              AND p.post_type = 'attachment'
              AND p.post_mime_type LIKE 'image/%'
        " );

        $cameras = []; $focals = []; $apertures = []; $isos = []; $shutters = [];

        foreach ( $rows as $blob ) {
            $meta = @maybe_unserialize( $blob );
            if ( ! is_array( $meta ) || empty( $meta['image_meta'] ) ) continue;
            $im = $meta['image_meta'];

            if ( ! empty( $im['camera'] ) ) {
                $k = trim( $im['camera'] );
                if ( $k !== '' ) $cameras[ $k ] = ( $cameras[ $k ] ?? 0 ) + 1;
            }
            if ( ! empty( $im['focal_length'] ) ) {
                $k = round( floatval( $im['focal_length'] ) ) . 'mm';
                $focals[ $k ] = ( $focals[ $k ] ?? 0 ) + 1;
            }
            if ( ! empty( $im['aperture'] ) ) {
                $k = 'f/' . round( floatval( $im['aperture'] ), 1 );
                $apertures[ $k ] = ( $apertures[ $k ] ?? 0 ) + 1;
            }
            if ( ! empty( $im['iso'] ) ) {
                $k = (string) intval( $im['iso'] );
                $isos[ $k ] = ( $isos[ $k ] ?? 0 ) + 1;
            }
            if ( ! empty( $im['shutter_speed'] ) ) {
                $s = floatval( $im['shutter_speed'] );
                $k = $s < 1 ? '1/' . round( 1 / $s ) : round( $s, 1 ) . 's';
                $shutters[ $k ] = ( $shutters[ $k ] ?? 0 ) + 1;
            }
        }

        // Convertir a arrays ordenados {label, count}
        $sortDesc = function( $arr, $limit = 20 ) {
            arsort( $arr );
            $out = [];
            $i = 0;
            foreach ( $arr as $k => $v ) {
                $out[] = [ 'label' => $k, 'count' => $v ];
                if ( ++$i >= $limit ) break;
            }
            return $out;
        };

        // Para focales/iso/aperturas/shutters mejor ordenar por valor numérico que por count
        $sortByLabelNumeric = function( $arr, $extract ) {
            uksort( $arr, function( $a, $b ) use ( $extract ) {
                return $extract( $a ) <=> $extract( $b );
            } );
            $out = [];
            foreach ( $arr as $k => $v ) $out[] = [ 'label' => $k, 'count' => $v ];
            return $out;
        };

        $stats = [
            'cameras'   => $sortDesc( $cameras ),
            'focals'    => $sortByLabelNumeric( $focals,    function( $k ) { return floatval( $k ); } ),
            'apertures' => $sortByLabelNumeric( $apertures, function( $k ) { return floatval( str_replace( 'f/', '', $k ) ); } ),
            'isos'      => $sortByLabelNumeric( $isos,      function( $k ) { return intval( $k ); } ),
            'shutters'  => $sortByLabelNumeric( $shutters,  function( $k ) {
                if ( strpos( $k, '1/' ) === 0 ) return 1.0 / floatval( substr( $k, 2 ) );
                return floatval( $k );
            } ),
            'total'     => array_sum( array_map( 'intval', $cameras ) ),
        ];

        set_transient( 'yzmf_stats_exif_cache', $stats, 30 * MINUTE_IN_SECONDS );
        return rest_ensure_response( $stats );
    }

    /**
     * Guarda la paleta dominante extraída en cliente.
     * Body: { palette: ['#RRGGBB', ...] }
     */
    public static function set_media_palette( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id || get_post_type( $id ) !== 'attachment' ) {
            return new WP_Error( 'yzmf_not_found', 'No encontrado', [ 'status' => 404 ] );
        }
        $palette = (array) $req->get_param( 'palette' );
        $clean = [];
        foreach ( $palette as $hex ) {
            $hex = strtoupper( ltrim( (string) $hex, '#' ) );
            if ( preg_match( '/^[0-9A-F]{6}$/', $hex ) ) {
                $clean[] = '#' . $hex;
            }
        }
        if ( empty( $clean ) ) {
            delete_post_meta( $id, '_yzmf_color_palette' );
        } else {
            update_post_meta( $id, '_yzmf_color_palette', $clean );
        }
        delete_transient( 'yzmf_colors_cache' );  // invalidar caché de colores
        return rest_ensure_response( [ 'id' => $id, 'palette' => $clean ] );
    }

    /**
     * Lista todas las tags IA con su frecuencia. Cache 30 min.
     */
    public static function get_tags( WP_REST_Request $req ) {
        $cache = get_transient( 'yzmf_tags_cache' );
        if ( $cache !== false ) return rest_ensure_response( $cache );

        global $wpdb;
        $rows = $wpdb->get_col( "
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_yzmf_ai_tags'
        " );

        $counts = [];
        foreach ( $rows as $blob ) {
            $arr = @maybe_unserialize( $blob );
            if ( ! is_array( $arr ) ) continue;
            foreach ( $arr as $t ) {
                $t = strtolower( trim( (string) $t ) );
                if ( $t === '' ) continue;
                $counts[ $t ] = ( $counts[ $t ] ?? 0 ) + 1;
            }
        }
        arsort( $counts );
        $out = [];
        foreach ( $counts as $tag => $count ) {
            $out[] = [ 'tag' => $tag, 'count' => $count ];
        }
        set_transient( 'yzmf_tags_cache', $out, 30 * MINUTE_IN_SECONDS );
        return rest_ensure_response( $out );
    }

    /**
     * Lista los colores dominantes encontrados con conteo. Cache 30 min.
     */
    public static function get_colors( WP_REST_Request $req ) {
        $cache = get_transient( 'yzmf_colors_cache' );
        if ( $cache !== false ) return rest_ensure_response( $cache );

        global $wpdb;
        $rows = $wpdb->get_col( "
            SELECT meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_yzmf_color_palette'
        " );

        $counts = [];
        foreach ( $rows as $blob ) {
            $arr = @maybe_unserialize( $blob );
            if ( ! is_array( $arr ) ) continue;
            foreach ( $arr as $hex ) {
                $hex = strtoupper( ltrim( (string) $hex, '#' ) );
                if ( ! preg_match( '/^[0-9A-F]{6}$/', $hex ) ) continue;
                $counts[ $hex ] = ( $counts[ $hex ] ?? 0 ) + 1;
            }
        }
        arsort( $counts );
        $out = [];
        $i = 0;
        foreach ( $counts as $hex => $count ) {
            $out[] = [ 'color' => '#' . $hex, 'count' => $count ];
            if ( ++$i >= 60 ) break;  // limitar a top 60 colores
        }
        set_transient( 'yzmf_colors_cache', $out, 30 * MINUTE_IN_SECONDS );
        return rest_ensure_response( $out );
    }

    /* ─────────── GEOCODING (proxy Nominatim) ─────────── */

    public static function geocode_search( WP_REST_Request $req ) {
        $q = sanitize_text_field( $req->get_param( 'q' ) ?: '' );
        if ( strlen( $q ) < 3 ) return rest_ensure_response( [] );

        $cache_key = 'yzmf_geo_s_' . md5( $q );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return rest_ensure_response( $cached );

        $url = add_query_arg( [
            'q'               => $q,
            'format'          => 'json',
            'limit'           => 6,
            'accept-language' => 'es',
        ], 'https://nominatim.openstreetmap.org/search' );

        $resp = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'YZMediaFolders/' . YZMF_VERSION . ' (' . home_url() . ')',
        ] );
        if ( is_wp_error( $resp ) ) return new WP_Error( 'yzmf_geo_failed', $resp->get_error_message(), [ 'status' => 502 ] );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $body ) ) $body = [];
        set_transient( $cache_key, $body, DAY_IN_SECONDS );
        return rest_ensure_response( $body );
    }

    public static function geocode_reverse( WP_REST_Request $req ) {
        $lat = floatval( $req->get_param( 'lat' ) );
        $lng = floatval( $req->get_param( 'lng' ) );
        if ( ! $lat || ! $lng ) return new WP_Error( 'yzmf_invalid', 'lat/lng requeridos', [ 'status' => 400 ] );

        $cache_key = 'yzmf_geo_r_' . md5( $lat . ',' . $lng );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return rest_ensure_response( $cached );

        $url = add_query_arg( [
            'lat'             => $lat,
            'lon'             => $lng,
            'format'          => 'json',
            'accept-language' => 'es',
        ], 'https://nominatim.openstreetmap.org/reverse' );

        $resp = wp_remote_get( $url, [
            'timeout'    => 10,
            'user-agent' => 'YZMediaFolders/' . YZMF_VERSION . ' (' . home_url() . ')',
        ] );
        if ( is_wp_error( $resp ) ) return new WP_Error( 'yzmf_geo_failed', $resp->get_error_message(), [ 'status' => 502 ] );
        $body = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( ! is_array( $body ) ) $body = [];
        set_transient( $cache_key, $body, DAY_IN_SECONDS );
        return rest_ensure_response( $body );
    }
}
