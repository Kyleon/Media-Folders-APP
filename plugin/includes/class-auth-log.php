<?php
/**
 * Registro de actividad de autenticación.
 *
 * - Hooks 'wp_login' y 'wp_login_failed' para guardar cada intento.
 * - Almacenamiento: option 'yzmf_auth_log' como array circular (últimos 200).
 * - Lockout: tras N intentos fallidos desde la misma IP en M minutos, bloquear
 *   logins durante L minutos (configurable por opciones).
 * - Endpoint REST GET /yzmf/v1/auth/activity → últimos 50 eventos visibles.
 *
 * Opciones:
 *   yzmf_auth_max_attempts    int   default 5
 *   yzmf_auth_window_minutes  int   default 15
 *   yzmf_auth_lockout_minutes int   default 30
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Auth_Log {

    const NS         = 'yzmf/v1';
    const OPT_LOG    = 'yzmf_auth_log';
    const OPT_LOCKS  = 'yzmf_auth_locks';     // [ ip => unlock_at_ts ]
    const MAX_LOG    = 200;
    const DEFAULT_ATTEMPTS = 5;
    const DEFAULT_WINDOW   = 15; // minutos
    const DEFAULT_LOCKOUT  = 30; // minutos

    public static function init() {
        add_action( 'wp_login',          [ __CLASS__, 'on_login_success' ], 10, 2 );
        add_action( 'wp_login_failed',   [ __CLASS__, 'on_login_failed' ], 10, 1 );
        // Bloquear authentication si IP está locked (interceptamos antes de comprobar password)
        add_filter( 'authenticate',      [ __CLASS__, 'maybe_block' ], 30, 3 );

        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    /* ─────────── Hooks ─────────── */

    public static function on_login_success( $user_login, $user ) {
        self::push( [
            'ts'     => time(),
            'type'   => 'login',
            'user'   => $user_login,
            'user_id'=> $user instanceof WP_User ? $user->ID : 0,
            'ip'     => self::ip(),
            'ua'     => self::ua(),
        ] );
        // Resetear locks de esa IP en login exitoso
        $locks = get_option( self::OPT_LOCKS, [] );
        if ( is_array( $locks ) && isset( $locks[ self::ip() ] ) ) {
            unset( $locks[ self::ip() ] );
            update_option( self::OPT_LOCKS, $locks );
        }
    }

    public static function on_login_failed( $username ) {
        self::push( [
            'ts'   => time(),
            'type' => 'failed',
            'user' => (string) $username,
            'ip'   => self::ip(),
            'ua'   => self::ua(),
        ] );
        self::maybe_lock_ip();
    }

    public static function maybe_block( $user, $username = '', $password = '' ) {
        if ( $user instanceof WP_User ) return $user;  // ya autenticado por otro filtro
        if ( empty( $username ) ) return $user;        // formulario vacío

        $locks = get_option( self::OPT_LOCKS, [] );
        $ip = self::ip();
        if ( is_array( $locks ) && ! empty( $locks[ $ip ] ) && $locks[ $ip ] > time() ) {
            $remaining = (int) ceil( ( $locks[ $ip ] - time() ) / 60 );
            return new WP_Error(
                'yzmf_locked',
                sprintf( '🔒 Demasiados intentos. Inténtalo de nuevo en %d minutos.', $remaining )
            );
        }
        return $user;
    }

    /* ─────────── Lockout logic ─────────── */

    private static function maybe_lock_ip() {
        $max    = (int) get_option( 'yzmf_auth_max_attempts',    self::DEFAULT_ATTEMPTS );
        $window = (int) get_option( 'yzmf_auth_window_minutes',  self::DEFAULT_WINDOW );
        $lock   = (int) get_option( 'yzmf_auth_lockout_minutes', self::DEFAULT_LOCKOUT );
        if ( $max <= 0 ) return;

        $log = self::log();
        $ip  = self::ip();
        $cutoff = time() - $window * MINUTE_IN_SECONDS;
        $count = 0;
        foreach ( array_reverse( $log ) as $entry ) {
            if ( $entry['ts'] < $cutoff ) break;
            if ( ( $entry['type'] ?? '' ) === 'failed' && ( $entry['ip'] ?? '' ) === $ip ) {
                $count++;
            }
        }
        if ( $count >= $max ) {
            $locks = get_option( self::OPT_LOCKS, [] );
            if ( ! is_array( $locks ) ) $locks = [];
            $locks[ $ip ] = time() + $lock * MINUTE_IN_SECONDS;
            update_option( self::OPT_LOCKS, $locks );
            self::push( [
                'ts'   => time(),
                'type' => 'lockout',
                'user' => '',
                'ip'   => $ip,
                'ua'   => self::ua(),
                'unlock_at' => $locks[ $ip ],
            ] );
        }
    }

    /* ─────────── Storage ─────────── */

    private static function push( $entry ) {
        $log = self::log();
        $log[] = $entry;
        if ( count( $log ) > self::MAX_LOG ) {
            $log = array_slice( $log, -self::MAX_LOG );
        }
        update_option( self::OPT_LOG, $log );
    }

    public static function log() {
        $log = get_option( self::OPT_LOG, [] );
        return is_array( $log ) ? $log : [];
    }

    /* ─────────── REST ─────────── */

    public static function register_routes() {
        register_rest_route( self::NS, '/auth/activity', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'rest_activity' ],
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ] );

        register_rest_route( self::NS, '/auth/clear-locks', [
            'methods'             => 'POST',
            'callback'            => function () {
                update_option( self::OPT_LOCKS, [] );
                return rest_ensure_response( [ 'ok' => true ] );
            },
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ] );

        register_rest_route( self::NS, '/auth/settings', [
            [
                'methods'  => 'GET',
                'callback' => function () {
                    return rest_ensure_response( [
                        'max_attempts'    => (int) get_option( 'yzmf_auth_max_attempts',    self::DEFAULT_ATTEMPTS ),
                        'window_minutes'  => (int) get_option( 'yzmf_auth_window_minutes',  self::DEFAULT_WINDOW ),
                        'lockout_minutes' => (int) get_option( 'yzmf_auth_lockout_minutes', self::DEFAULT_LOCKOUT ),
                    ] );
                },
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
            ],
            [
                'methods'  => 'PUT',
                'callback' => function ( WP_REST_Request $req ) {
                    foreach ( [ 'max_attempts', 'window_minutes', 'lockout_minutes' ] as $k ) {
                        $v = $req->get_param( $k );
                        if ( $v !== null ) update_option( 'yzmf_auth_' . $k, max( 0, (int) $v ) );
                    }
                    return rest_ensure_response( [ 'ok' => true ] );
                },
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
            ],
        ] );
    }

    public static function rest_activity( WP_REST_Request $req ) {
        $log = self::log();
        $log = array_reverse( $log );  // últimos primero
        $log = array_slice( $log, 0, 50 );
        // Decorar con datos amigables
        foreach ( $log as &$e ) {
            $e['ts_iso'] = gmdate( 'c', (int) $e['ts'] );
            if ( ! empty( $e['unlock_at'] ) ) $e['unlock_iso'] = gmdate( 'c', (int) $e['unlock_at'] );
        }
        unset( $e );

        $locks = get_option( self::OPT_LOCKS, [] );
        $active_locks = [];
        if ( is_array( $locks ) ) {
            $now = time();
            foreach ( $locks as $ip => $until ) {
                if ( $until > $now ) {
                    $active_locks[] = [
                        'ip'         => $ip,
                        'unlock_at'  => (int) $until,
                        'unlock_iso' => gmdate( 'c', (int) $until ),
                    ];
                }
            }
        }

        return rest_ensure_response( [
            'log'   => $log,
            'locks' => $active_locks,
        ] );
    }

    /* ─────────── Helpers ─────────── */

    /**
     * IP del cliente. Por defecto solo REMOTE_ADDR — confiar en
     * X-Forwarded-For sin un proxy de confianza permite spoofing trivial
     * (un atacante elige su IP para eludir el lockout, o bloquea a otros
     * usuarios). Solo se usan headers forwarded si REMOTE_ADDR está dentro
     * de la lista de proxies de confianza configurada en
     * `yzmf_trusted_proxies` (CSV de IPs).
     */
    private static function ip() {
        $remote = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( $_SERVER['REMOTE_ADDR'] ) : '';
        if ( ! self::is_trusted_proxy( $remote ) ) {
            return filter_var( $remote, FILTER_VALIDATE_IP ) ? $remote : '0.0.0.0';
        }
        // Detrás de proxy de confianza: aceptamos los headers forwarded.
        $candidates = [];
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            foreach ( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) as $part ) $candidates[] = trim( $part );
        }
        $candidates[] = $remote;
        foreach ( $candidates as $ip ) {
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }
        return '0.0.0.0';
    }

    private static function is_trusted_proxy( $ip ) {
        $list = (string) get_option( 'yzmf_trusted_proxies', '' );
        if ( ! $list ) return false;
        $proxies = array_filter( array_map( 'trim', explode( ',', $list ) ) );
        return in_array( $ip, $proxies, true );
    }

    private static function ua() {
        return substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 200 );
    }
}
