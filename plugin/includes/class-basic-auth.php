<?php
/**
 * Basic Auth para REST API con contraseñas REGULARES de WordPress.
 *
 * Por defecto WordPress core 5.6+ solo autentica Basic Auth si el password es
 * un Application Password. Es lo correcto: una App Password se puede revocar
 * y no es la contraseña de login del administrador.
 *
 * Este módulo acepta la contraseña regular SOLO en el endpoint de canje
 * POST /yzmf/v1/auth/token, que devuelve una Application Password nueva para
 * que la PWA la guarde en lugar de la contraseña real. El resto de rutas
 * exigen Application Password (o cookie).
 *
 * Modo legacy: si la opción yzmf_enable_basic_auth vale '1', la contraseña
 * regular se acepta en todas las rutas (comportamiento antiguo). Por defecto
 * está desactivado. Útil solo en un WP local sin HTTPS, donde WordPress no
 * permite Application Passwords.
 *
 * DELETE /yzmf/v1/auth/token revoca la Application Password con la que se ha
 * autenticado la petición (logout de la PWA).
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Basic_Auth {

    const NS         = 'yzmf/v1';
    const TOKEN_ROUTE = '/yzmf/v1/auth/token';
    const APP_NAME   = 'YPVA PWA';

    public static function init() {
        add_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );
        add_filter( 'rest_authentication_errors', [ __CLASS__, 'auth_error' ], 99 );
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    /**
     * Modo legacy: contraseña regular aceptada en todas las rutas REST.
     * Default OFF (2026-06): solo Application Passwords salvo que el admin
     * lo active explícitamente.
     */
    public static function is_enabled() {
        $opt = get_option( 'yzmf_enable_basic_auth', '0' );
        return $opt === '1' || $opt === 1 || $opt === true;
    }

    /**
     * ¿La petición actual va al endpoint de canje? Se mira la URI porque
     * determine_current_user puede ejecutarse antes de que WP resuelva la ruta.
     * Soporta /wp-json/... y ?rest_route=...
     */
    private static function is_token_request() {
        $route = '';
        if ( isset( $_GET['rest_route'] ) ) {
            $route = (string) $_GET['rest_route'];
        } else {
            $uri    = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
            $path   = (string) parse_url( $uri, PHP_URL_PATH );
            $prefix = '/' . trim( rest_get_url_prefix(), '/' );
            $pos    = strpos( $path, $prefix . '/' );
            if ( $pos !== false ) $route = substr( $path, $pos + strlen( $prefix ) );
        }
        return untrailingslashit( $route ) === self::TOKEN_ROUTE;
    }

    /**
     * Determina el usuario actual a partir del header Authorization: Basic
     * con contraseña regular (solo en el endpoint de canje o en modo legacy).
     * Si ya hay usuario autenticado por otro filtro (cookies o app password)
     * lo respeta.
     */
    public static function authenticate( $user_id ) {
        if ( ! empty( $user_id ) ) return $user_id;
        if ( ! self::is_enabled() && ! self::is_token_request() ) return $user_id;

        list( $username, $password ) = self::extract_credentials();
        if ( ! $username || ! $password ) return $user_id;

        // Evitar recursión: wp_authenticate puede invocar filtros que
        // vuelvan a llamarnos.
        remove_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );
        $user = wp_authenticate( $username, $password );
        add_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );

        if ( is_wp_error( $user ) ) {
            self::set_error( $user );
            return null;
        }

        // Autenticación OK: limpiar el error que el handler core de
        // Application Passwords deja al fallar el match (la contraseña no era
        // de tipo app password). Si no, WP devolvería 401.
        $GLOBALS['wp_rest_application_password_status'] = null;

        return $user->ID;
    }

    /**
     * Si hay usuario autenticado, descartamos errores previos del check de
     * Application Passwords. Si fallamos, devolvemos nuestro propio error.
     */
    public static function auth_error( $error ) {
        if ( get_current_user_id() > 0 ) return null;
        if ( ! empty( $error ) ) return $error;
        $err = self::get_error();
        if ( $err instanceof WP_Error ) return $err;
        return $error;
    }

    /* ─────────── Endpoint de canje ─────────── */

    public static function register_routes() {
        register_rest_route( self::NS, '/auth/token', [
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'issue_token' ],
                'permission_callback' => function () { return current_user_can( 'upload_files' ); },
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'revoke_token' ],
                'permission_callback' => 'is_user_logged_in',
            ],
        ] );
    }

    /**
     * Crea una Application Password para el usuario autenticado.
     * Body opcional: { device: "iPhone" } → nombre "YPVA PWA · iPhone".
     * Si ya existía una con el mismo nombre se sustituye, para no acumular
     * claves huérfanas cada vez que se inicia sesión desde el mismo equipo.
     */
    public static function issue_token( WP_REST_Request $req ) {
        if ( ! class_exists( 'WP_Application_Passwords' ) || ! wp_is_application_passwords_available() ) {
            return new WP_Error(
                'yzmf_app_passwords_unavailable',
                'Este WordPress no permite Application Passwords (requiere HTTPS). En local, activa yzmf_enable_basic_auth.',
                [ 'status' => 501 ]
            );
        }
        $user = wp_get_current_user();
        if ( ! wp_is_application_passwords_available_for_user( $user ) ) {
            return new WP_Error( 'yzmf_app_passwords_disabled', 'Application Passwords desactivadas para este usuario.', [ 'status' => 403 ] );
        }

        $device = sanitize_text_field( (string) $req->get_param( 'device' ) );
        $device = mb_substr( $device, 0, 40 );
        $name   = self::APP_NAME . ( $device !== '' ? ' · ' . $device : '' );

        foreach ( WP_Application_Passwords::get_user_application_passwords( $user->ID ) as $item ) {
            if ( $item['name'] === $name ) {
                WP_Application_Passwords::delete_application_password( $user->ID, $item['uuid'] );
            }
        }

        $created = WP_Application_Passwords::create_new_application_password( $user->ID, [ 'name' => $name ] );
        if ( is_wp_error( $created ) ) return $created;

        return rest_ensure_response( [
            'username'     => $user->user_login,
            'display_name' => $user->display_name,
            'password'     => $created[0],
            'name'         => $name,
        ] );
    }

    /** Revoca la Application Password usada en esta petición. */
    public static function revoke_token() {
        $uuid = function_exists( 'rest_get_authenticated_app_password' ) ? rest_get_authenticated_app_password() : null;
        if ( ! $uuid ) {
            return new WP_Error( 'yzmf_no_app_password', 'La petición no usa Application Password.', [ 'status' => 400 ] );
        }
        WP_Application_Passwords::delete_application_password( get_current_user_id(), $uuid );
        return rest_ensure_response( [ 'revoked' => true ] );
    }

    /* ─────────── Helpers ─────────── */

    private static function extract_credentials() {
        $user = $_SERVER['PHP_AUTH_USER'] ?? '';
        $pwd  = $_SERVER['PHP_AUTH_PW']   ?? '';

        // Algunos hosts no exponen PHP_AUTH_USER (CGI, FPM); en ese caso,
        // parseamos el header Authorization (o su variante REDIRECT_).
        if ( ! $user ) {
            foreach ( [ 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ] as $key ) {
                $auth = (string) ( $_SERVER[ $key ] ?? '' );
                if ( stripos( $auth, 'basic ' ) !== 0 ) continue;
                $decoded = base64_decode( substr( $auth, 6 ) );
                if ( $decoded && strpos( $decoded, ':' ) !== false ) {
                    list( $user, $pwd ) = explode( ':', $decoded, 2 );
                    break;
                }
            }
        }
        return [ $user, $pwd ];
    }

    private static $last_error = null;
    private static function set_error( $err ) { self::$last_error = $err; }
    private static function get_error()        { return self::$last_error; }
}
