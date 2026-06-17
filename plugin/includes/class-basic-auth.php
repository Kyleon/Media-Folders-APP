<?php
/**
 * Basic Auth para REST API con contraseñas REGULARES de WordPress.
 *
 * Por defecto WordPress core 5.6+ solo autentica Basic Auth si el password es
 * un Application Password. Esto es bueno por seguridad pero rompe casos de uso
 * donde el cliente quiere usar la contraseña habitual del usuario (más simple,
 * lo entiende cualquier dev de frontend).
 *
 * Este módulo replica la lógica del plugin oficial "Basic Auth" de WP-API
 * (https://github.com/WP-API/Basic-Auth) y lo activa SOLO cuando la opción
 * yzmf_enable_basic_auth está a true (por defecto: true).
 *
 * Recomendación: usa Application Passwords cuando puedas. Esta opción se
 * mantiene activa para que el panel funcione con contraseña regular sin
 * fricción para el dueño del sitio.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Basic_Auth {

    public static function init() {
        if ( ! self::is_enabled() ) return;
        add_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );
        add_filter( 'rest_authentication_errors', [ __CLASS__, 'auth_error' ], 99 );
    }

    public static function is_enabled() {
        // Default OFF a partir de 2026-06: solo Application Passwords salvo
        // que el admin lo active explícitamente. Reduce la superficie de
        // credential-stuffing (basta con que se filtre la password regular
        // del usuario para tomar la API entera).
        $opt = get_option( 'yzmf_enable_basic_auth', '0' );
        return $opt === '1' || $opt === 1 || $opt === true;
    }

    /**
     * Determina el usuario actual a partir del header Authorization: Basic.
     * Si ya hay usuario autenticado por otro filtro (ej: cookies o app pass)
     * lo respeta. Sólo entra en juego si hay PHP_AUTH_USER.
     */
    public static function authenticate( $user_id ) {
        // Si ya hay un usuario autenticado por cookies, app password u otro
        // mecanismo, no tocamos nada.
        if ( ! empty( $user_id ) ) return $user_id;

        // Sólo procesamos si llega un Authorization: Basic ...
        list( $username, $password ) = self::extract_credentials();
        if ( ! $username || ! $password ) return $user_id;

        // Evitar recursión: WP::authenticate puede invocar filtros que
        // vuelvan a llamarnos. Sólo lo quitamos durante la comprobación.
        remove_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );

        $user = wp_authenticate( $username, $password );

        add_filter( 'determine_current_user', [ __CLASS__, 'authenticate' ], 20 );

        if ( is_wp_error( $user ) ) {
            // Guardamos el error para devolverlo en rest_authentication_errors,
            // pero solo si la respuesta lo requiere.
            self::set_error( $user );
            return null;
        }

        // Autenticación OK: limpiar cualquier error que el handler core de
        // Application Passwords haya dejado al fallar el match (porque la
        // contraseña no era de tipo app password). Si no, WP core cogería
        // ese error en rest_authentication_errors y devolvería 401.
        $GLOBALS['wp_rest_application_password_status'] = null;

        return $user->ID;
    }

    /**
     * Si autenticamos con éxito (current_user > 0), descartamos cualquier
     * error que WP core haya marcado en filtros previos (típicamente del
     * check de Application Passwords cuando el password no era de ese tipo).
     * Si fallamos en autenticar, devolvemos nuestro propio error.
     */
    public static function auth_error( $error ) {
        if ( get_current_user_id() > 0 ) {
            // Hay un usuario autenticado válido — no hay error
            return null;
        }
        if ( ! empty( $error ) ) return $error;
        $err = self::get_error();
        if ( $err instanceof WP_Error ) return $err;
        return $error;
    }

    /* ─────────── Helpers ─────────── */

    private static function extract_credentials() {
        $user = $_SERVER['PHP_AUTH_USER'] ?? '';
        $pwd  = $_SERVER['PHP_AUTH_PW']   ?? '';

        // Algunos hosts no exponen PHP_AUTH_USER (CGI, FPM); en ese caso,
        // intentamos parsear el header Authorization manualmente.
        if ( ! $user && ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if ( stripos( $auth, 'basic ' ) === 0 ) {
                $decoded = base64_decode( substr( $auth, 6 ) );
                if ( $decoded && strpos( $decoded, ':' ) !== false ) {
                    list( $user, $pwd ) = explode( ':', $decoded, 2 );
                }
            }
        }
        // Fallback con REDIRECT_HTTP_AUTHORIZATION (algunos Apache + .htaccess)
        if ( ! $user && ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if ( stripos( $auth, 'basic ' ) === 0 ) {
                $decoded = base64_decode( substr( $auth, 6 ) );
                if ( $decoded && strpos( $decoded, ':' ) !== false ) {
                    list( $user, $pwd ) = explode( ':', $decoded, 2 );
                }
            }
        }
        return [ $user, $pwd ];
    }

    private static $last_error = null;
    private static function set_error( $err ) { self::$last_error = $err; }
    private static function get_error()        { return self::$last_error; }
}
