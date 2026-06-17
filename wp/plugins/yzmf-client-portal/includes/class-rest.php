<?php
/**
 * REST API del portal de cliente.
 *
 * Namespace: yzmf/v1/cp
 *
 * El cliente NO necesita autenticación de WordPress. La auth se basa en:
 *  - El token de la URL (público)
 *  - Una cookie firmada de "session" cuando hay password (se obtiene tras /login)
 *
 * Endpoints:
 *  GET  /cp/{token}                    Info de la galería (sin imágenes si tiene password)
 *  POST /cp/{token}/login              { password } → set cookie sesión
 *  GET  /cp/{token}/images             Lista de imágenes de la galería
 *  POST /cp/{token}/favorite           { att_id, on } toggle favorita
 *  POST /cp/{token}/comment            { att_id, text, name? }
 *  GET  /cp/{token}/zip                Descarga ZIP (si allow_download)
 *
 * Endpoints admin (auth WP):
 *  GET  /cp/admin/galleries            Lista galerías
 *  POST /cp/admin/galleries            Crear galería
 *  PUT  /cp/admin/galleries/{id}       Actualizar
 *  GET  /cp/admin/galleries/{id}/actions  Acciones del cliente
 */

defined( 'ABSPATH' ) || exit;

class YZMF_CP_REST {

    const NS = 'yzmf/v1';
    const COOKIE_NAME = 'yzmf_cp_session';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        // Cliente (público)
        register_rest_route( self::NS, '/cp/(?P<token>[A-Za-z0-9_-]+)', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_gallery' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( self::NS, '/cp/(?P<token>[A-Za-z0-9_-]+)/login', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'login' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( self::NS, '/cp/(?P<token>[A-Za-z0-9_-]+)/images', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'list_images' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( self::NS, '/cp/(?P<token>[A-Za-z0-9_-]+)/favorite', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'favorite' ],
            'permission_callback' => '__return_true',
        ] );
        register_rest_route( self::NS, '/cp/(?P<token>[A-Za-z0-9_-]+)/comment', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'comment' ],
            'permission_callback' => '__return_true',
        ] );

        // Admin
        register_rest_route( self::NS, '/cp/admin/galleries', [
            [
                'methods'  => 'GET',
                'callback' => [ __CLASS__, 'admin_list' ],
                'permission_callback' => [ __CLASS__, 'can_manage' ],
            ],
            [
                'methods'  => 'POST',
                'callback' => [ __CLASS__, 'admin_create' ],
                'permission_callback' => [ __CLASS__, 'can_manage' ],
            ],
        ] );
        register_rest_route( self::NS, '/cp/admin/galleries/(?P<id>\d+)', [
            [
                'methods'  => 'GET',
                'callback' => [ __CLASS__, 'admin_get' ],
                'permission_callback' => [ __CLASS__, 'can_manage' ],
            ],
            [
                'methods'  => 'PUT',
                'callback' => [ __CLASS__, 'admin_update' ],
                'permission_callback' => [ __CLASS__, 'can_manage' ],
            ],
            [
                'methods'  => 'DELETE',
                'callback' => [ __CLASS__, 'admin_delete' ],
                'permission_callback' => [ __CLASS__, 'can_manage' ],
            ],
        ] );
        register_rest_route( self::NS, '/cp/admin/galleries/(?P<id>\d+)/actions', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'admin_actions' ],
            'permission_callback' => [ __CLASS__, 'can_manage' ],
        ] );
    }

    public static function can_manage() {
        return current_user_can( 'edit_posts' );
    }

    /* ─────────── Helpers ─────────── */

    /**
     * Resuelve el post de galería desde el token. Devuelve WP_Error si no existe
     * o expirada.
     */
    private static function require_gallery( $token, $require_unlocked = true ) {
        $post = YZMF_CP_CPT::find_by_token( $token );
        if ( ! $post ) return new WP_Error( 'not_found', 'Galería no encontrada', [ 'status' => 404 ] );
        if ( YZMF_CP_CPT::is_expired( $post ) ) {
            return new WP_Error( 'expired', 'La galería ha expirado', [ 'status' => 410 ] );
        }
        if ( $require_unlocked && ! self::is_unlocked( $post ) ) {
            return new WP_Error( 'locked', 'Galería protegida con contraseña', [ 'status' => 401 ] );
        }
        return $post;
    }

    private static function is_unlocked( $post ) {
        $hash = (string) get_post_meta( $post->ID, '_yzmf_cp_password', true );
        if ( ! $hash ) return true;
        $expected = self::session_token( $post->ID );
        // 1. Cookie httponly (preferida)
        $cookie = $_COOKIE[ self::COOKIE_NAME ] ?? '';
        if ( $cookie && hash_equals( $expected, $cookie ) ) return true;
        // 2. Header X-YZMF-CP-Session (fallback robusto si la cookie no llega
        //    por LiteSpeed/CloudFlare/políticas SameSite, etc.)
        $hdr = $_SERVER['HTTP_X_YZMF_CP_SESSION'] ?? '';
        if ( $hdr && hash_equals( $expected, $hdr ) ) return true;
        return false;
    }

    private static function session_token( $post_id ) {
        // Cookie firmada con NONCE_SALT
        $hash = (string) get_post_meta( $post_id, '_yzmf_cp_password', true );
        return hash_hmac( 'sha256', $post_id . '|' . $hash, wp_salt( 'auth' ) );
    }

    private static function set_session_cookie( $post ) {
        $value = self::session_token( $post->ID );
        $expires = time() + DAY_IN_SECONDS * 7;
        setcookie(
            self::COOKIE_NAME,
            $value,
            [
                'expires'  => $expires,
                'path'     => '/',
                'httponly' => true,
                'secure'   => is_ssl(),
                'samesite' => 'Lax',
            ]
        );
        $_COOKIE[ self::COOKIE_NAME ] = $value;
    }

    private static function format_gallery_meta( $post ) {
        return [
            'id'             => $post->ID,
            'title'          => get_the_title( $post ),
            'token'          => get_post_meta( $post->ID, '_yzmf_cp_token', true ),
            'has_password'   => (bool) get_post_meta( $post->ID, '_yzmf_cp_password', true ),
            'expires'        => (int) get_post_meta( $post->ID, '_yzmf_cp_expires', true ),
            'allow_download' => (bool) get_post_meta( $post->ID, '_yzmf_cp_allow_download', true ),
            'allow_comments' => (bool) get_post_meta( $post->ID, '_yzmf_cp_allow_comments', true ),
            'client_name'    => (string) get_post_meta( $post->ID, '_yzmf_cp_client_name', true ),
            'message'        => (string) get_post_meta( $post->ID, '_yzmf_cp_message', true ),
        ];
    }

    private static function format_image( $att_id ) {
        $full = wp_get_attachment_url( $att_id );
        return [
            'id'      => (int) $att_id,
            'title'   => get_the_title( $att_id ),
            'alt'     => (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ),
            'thumb'   => wp_get_attachment_image_url( $att_id, 'thumbnail' ) ?: $full,  // mosaicos
            'medium'  => wp_get_attachment_image_url( $att_id, 'medium' )    ?: $full,
            'large'   => wp_get_attachment_image_url( $att_id, 'large' )     ?: $full,
            'full'    => $full,
            'url'     => $full,                                                          // alias
        ];
    }

    private static function get_favorites( $post ) {
        $actions = YZMF_CP_CPT::get_actions( $post->ID, 'favorite' );
        $favs = [];
        foreach ( $actions as $a ) {
            $aid = (int) get_post_meta( $a->ID, '_att_id', true );
            $favs[ $aid ] = true;
        }
        return $favs;
    }

    /* ─────────── Rate-limit y anti-spam ─────────── */

    /**
     * Rate-limit por (token + ip + endpoint). Usa transient + sliding-window
     * simple. Si se excede, devuelve WP_Error 429; si no, registra y devuelve null.
     *
     * Defaults conservadores: login 10/5min, favorite 60/min, comment 20/min.
     */
    private static function rate_limit( $token, $endpoint, $max, $window_seconds ) {
        $ip  = self::client_ip();
        $key = 'yzmf_cp_rl_' . md5( $endpoint . '|' . $token . '|' . $ip );
        $bucket = get_transient( $key );
        if ( ! is_array( $bucket ) ) $bucket = [ 'count' => 0, 'started' => time() ];
        if ( time() - $bucket['started'] > $window_seconds ) {
            $bucket = [ 'count' => 0, 'started' => time() ];
        }
        $bucket['count']++;
        set_transient( $key, $bucket, $window_seconds );
        if ( $bucket['count'] > $max ) {
            return new WP_Error( 'rate_limited',
                'Demasiadas peticiones. Espera un momento.',
                [ 'status' => 429 ]
            );
        }
        return null;
    }

    private static function client_ip() {
        // El portal cliente es público: no aceptamos X-Forwarded-For aquí
        // (el atacante elegiría su IP para eludir el rate-limit). Si el host
        // está detrás de proxy, los buckets serán compartidos — preferible a
        // que un atacante los esquive.
        return (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
    }

    /* ─────────── Endpoints cliente ─────────── */

    public static function get_gallery( WP_REST_Request $req ) {
        $token = $req['token'];
        $post = YZMF_CP_CPT::find_by_token( $token );
        if ( ! $post ) return new WP_Error( 'not_found', 'No encontrada', [ 'status' => 404 ] );
        if ( YZMF_CP_CPT::is_expired( $post ) ) return new WP_Error( 'expired', 'Expirada', [ 'status' => 410 ] );

        // Contador de visitas (1 por sesión)
        $views = (int) get_post_meta( $post->ID, '_yzmf_cp_views', true );
        update_post_meta( $post->ID, '_yzmf_cp_views', $views + 1 );

        $meta = self::format_gallery_meta( $post );
        $meta['locked'] = ! self::is_unlocked( $post );
        return rest_ensure_response( $meta );
    }

    public static function login( WP_REST_Request $req ) {
        $token = $req['token'];
        if ( $err = self::rate_limit( $token, 'login', 10, 5 * MINUTE_IN_SECONDS ) ) return $err;

        $pwd   = (string) $req->get_param( 'password' );
        $post  = YZMF_CP_CPT::find_by_token( $token );
        if ( ! $post ) return new WP_Error( 'not_found', 'No encontrada', [ 'status' => 404 ] );

        if ( ! YZMF_CP_CPT::check_password( $post, $pwd ) ) {
            return new WP_Error( 'bad_password', 'Contraseña incorrecta', [ 'status' => 401 ] );
        }
        self::set_session_cookie( $post );
        // Devolver también el session token en el body para usarlo como header
        // (más fiable que cookies cuando hay LiteSpeed/CloudFlare/SameSite raro)
        return rest_ensure_response( [
            'ok'      => true,
            'session' => self::session_token( $post->ID ),
        ] );
    }

    public static function list_images( WP_REST_Request $req ) {
        $post = self::require_gallery( $req['token'] );
        if ( is_wp_error( $post ) ) return $post;

        $ids = YZMF_CP_CPT::get_images( $post );
        $favs = self::get_favorites( $post );

        $out = [];
        foreach ( $ids as $id ) {
            $img = self::format_image( $id );
            $img['favorited'] = isset( $favs[ $id ] );
            $out[] = $img;
        }
        return rest_ensure_response( $out );
    }

    public static function favorite( WP_REST_Request $req ) {
        if ( $err = self::rate_limit( $req['token'], 'favorite', 60, MINUTE_IN_SECONDS ) ) return $err;
        $post = self::require_gallery( $req['token'] );
        if ( is_wp_error( $post ) ) return $post;
        $att_id = (int) $req->get_param( 'att_id' );
        $on     = (bool) $req->get_param( 'on' );

        $ids = YZMF_CP_CPT::get_images( $post );
        if ( ! in_array( $att_id, $ids, true ) ) {
            return new WP_Error( 'invalid_image', 'Imagen no pertenece a esta galería', [ 'status' => 400 ] );
        }

        // Buscar acción existente
        $existing = get_posts( [
            'post_type'      => YZMF_CP_CPT::ACTION_TYPE,
            'post_parent'    => $post->ID,
            'meta_query'     => [
                [ 'key' => '_att_id',      'value' => $att_id ],
                [ 'key' => '_action_type', 'value' => 'favorite' ],
            ],
            'posts_per_page' => 1,
        ] );

        if ( $on && ! $existing ) {
            YZMF_CP_CPT::record_action( $post->ID, $att_id, 'favorite' );
            self::notify_owner( $post, 'favorite', $att_id );
        } elseif ( ! $on && $existing ) {
            wp_delete_post( $existing[0]->ID, true );
        }

        return rest_ensure_response( [ 'ok' => true, 'favorited' => $on ] );
    }

    public static function comment( WP_REST_Request $req ) {
        if ( $err = self::rate_limit( $req['token'], 'comment', 20, MINUTE_IN_SECONDS ) ) return $err;
        $post = self::require_gallery( $req['token'] );
        if ( is_wp_error( $post ) ) return $post;
        $allow = (bool) get_post_meta( $post->ID, '_yzmf_cp_allow_comments', true );
        if ( ! $allow ) return new WP_Error( 'comments_disabled', 'Comentarios deshabilitados', [ 'status' => 403 ] );

        $att_id = (int) $req->get_param( 'att_id' );
        $text   = trim( (string) $req->get_param( 'text' ) );
        $name   = sanitize_text_field( (string) $req->get_param( 'name' ) );
        if ( ! $text ) return new WP_Error( 'empty', 'Comentario vacío', [ 'status' => 400 ] );
        if ( strlen( $text ) > 2000 ) $text = substr( $text, 0, 2000 );

        YZMF_CP_CPT::record_action( $post->ID, $att_id, 'comment', [
            'text' => wp_kses_post( $text ),
            'name' => $name,
        ] );
        self::notify_owner( $post, 'comment', $att_id, $text );

        return rest_ensure_response( [ 'ok' => true ] );
    }

    /* ─────────── Notificaciones ─────────── */

    private static function notify_owner( $post, $type, $att_id, $extra = '' ) {
        $email = get_option( 'yzmf_cp_owner_email', get_option( 'admin_email' ) );
        if ( ! $email ) return;

        // Debounce por (galería, tipo): 1 email cada 5 min como mucho. Evita
        // que un cliente (o atacante con token) bombardee el buzón pulsando
        // favorita en 200 fotos.
        $debounce_key = 'yzmf_cp_notify_' . $post->ID . '_' . $type;
        if ( get_transient( $debounce_key ) ) return;
        set_transient( $debounce_key, 1, 5 * MINUTE_IN_SECONDS );

        $title = get_the_title( $post );
        $client = get_post_meta( $post->ID, '_yzmf_cp_client_name', true );

        if ( $type === 'favorite' ) {
            $subject = sprintf( '[%s] %s marcó una favorita', $title, $client ?: 'Cliente' );
            $body = "El cliente marcó como favorita la imagen #$att_id.\n\n";
            $body .= "(Notificación agrupada — quizá hay más favoritas posteriores en los próximos 5 min)\n\n";
        } else {
            $subject = sprintf( '[%s] %s comentó', $title, $client ?: 'Cliente' );
            $body = "El cliente comentó la imagen #$att_id:\n\n$extra\n\n";
        }
        $body .= "Editar galería: " . get_edit_post_link( $post->ID, 'raw' ) . "\n";
        wp_mail( $email, $subject, $body );
    }

    /* ─────────── Endpoints admin ─────────── */

    public static function admin_list() {
        $q = new WP_Query( [
            'post_type'      => YZMF_CP_CPT::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        $out = [];
        foreach ( $q->posts as $p ) {
            $meta = self::format_gallery_meta( $p );
            $meta['views']      = (int) get_post_meta( $p->ID, '_yzmf_cp_views', true );
            $meta['image_count'] = count( YZMF_CP_CPT::get_images( $p ) );
            $meta['url']        = home_url( '/g/' . $meta['token'] );
            $out[] = $meta;
        }
        return rest_ensure_response( $out );
    }

    public static function admin_create( WP_REST_Request $req ) {
        $title = sanitize_text_field( (string) $req->get_param( 'title' ) ) ?: 'Galería sin título';
        $post_id = wp_insert_post( [
            'post_type'   => YZMF_CP_CPT::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_author' => get_current_user_id(),
        ] );
        if ( is_wp_error( $post_id ) ) return $post_id;
        return self::admin_update_internal( $post_id, $req );
    }

    public static function admin_update( WP_REST_Request $req ) {
        return self::admin_update_internal( (int) $req['id'], $req );
    }

    private static function admin_update_internal( $post_id, $req ) {
        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== YZMF_CP_CPT::POST_TYPE ) {
            return new WP_Error( 'not_found', 'Galería no encontrada', [ 'status' => 404 ] );
        }

        $title = $req->get_param( 'title' );
        if ( $title !== null ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => sanitize_text_field( (string) $title ) ] );
        }

        $images = $req->get_param( 'images' );
        if ( is_array( $images ) ) YZMF_CP_CPT::set_images( $post_id, $images );

        $pwd = $req->get_param( 'password' );
        if ( $pwd !== null ) {
            if ( $pwd === '' ) delete_post_meta( $post_id, '_yzmf_cp_password' );
            else update_post_meta( $post_id, '_yzmf_cp_password', wp_hash_password( (string) $pwd ) );
        }

        foreach ( [ 'expires', 'allow_download', 'allow_comments' ] as $k ) {
            $v = $req->get_param( $k );
            if ( $v !== null ) update_post_meta( $post_id, '_yzmf_cp_' . $k, $v );
        }
        foreach ( [ 'client_name', 'client_email', 'message' ] as $k ) {
            $v = $req->get_param( $k );
            if ( $v !== null ) update_post_meta( $post_id, '_yzmf_cp_' . $k, sanitize_text_field( (string) $v ) );
        }

        $meta = self::format_gallery_meta( get_post( $post_id ) );
        $meta['url'] = home_url( '/g/' . $meta['token'] );
        return rest_ensure_response( $meta );
    }

    public static function admin_get( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== YZMF_CP_CPT::POST_TYPE ) {
            return new WP_Error( 'not_found', 'Galería no encontrada', [ 'status' => 404 ] );
        }
        $meta = self::format_gallery_meta( $post );
        $meta['url'] = home_url( '/g/' . $meta['token'] );
        $meta['views'] = (int) get_post_meta( $id, '_yzmf_cp_views', true );
        $meta['client_email'] = (string) get_post_meta( $id, '_yzmf_cp_client_email', true );

        // Devolver imágenes con thumbs (no solo IDs)
        $img_ids = YZMF_CP_CPT::get_images( $post );
        $imgs = [];
        foreach ( $img_ids as $aid ) {
            if ( get_post_type( $aid ) !== 'attachment' ) continue;
            $imgs[] = self::format_image( $aid );
        }
        $meta['images'] = $imgs;
        return rest_ensure_response( $meta );
    }

    public static function admin_delete( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== YZMF_CP_CPT::POST_TYPE ) {
            return new WP_Error( 'not_found', 'Galería no encontrada', [ 'status' => 404 ] );
        }
        // Borrar también todas las acciones registradas (favoritas/comentarios)
        $actions = YZMF_CP_CPT::get_actions( $id );
        foreach ( $actions as $a ) wp_delete_post( $a->ID, true );
        wp_delete_post( $id, true );
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    public static function admin_actions( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        $actions = YZMF_CP_CPT::get_actions( $id );
        $out = [];
        foreach ( $actions as $a ) {
            $att_id = (int) get_post_meta( $a->ID, '_att_id', true );
            $type   = (string) get_post_meta( $a->ID, '_action_type', true );
            $payload = $a->post_content;
            $decoded = $payload ? @json_decode( $payload, true ) : null;
            $att_path = $att_id ? get_attached_file( $att_id ) : '';
            $att_full = $att_id ? wp_get_attachment_url( $att_id ) : '';
            $out[] = [
                'id'        => $a->ID,
                'date'      => get_the_date( 'c', $a ),
                'type'      => $type,
                'att_id'    => $att_id,
                'thumb'     => wp_get_attachment_image_url( $att_id, 'thumbnail' ) ?: $att_full,
                'full'      => $att_full,
                'url'       => $att_full,
                'filename'  => $att_path ? basename( $att_path ) : '',
                'att_title' => get_the_title( $att_id ),
                'payload'   => is_array( $decoded ) ? $decoded : $payload,
            ];
        }
        return rest_ensure_response( $out );
    }
}
