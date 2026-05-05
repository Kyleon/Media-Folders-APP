<?php
/**
 * Frontend del portal de cliente.
 *
 * Cuando se accede a /g/{token} servimos una página standalone con la galería.
 * Es un template HTML mínimo que carga JS+CSS propios y consume los endpoints
 * REST públicos /yzmf/v1/cp/{token}/...
 */

defined( 'ABSPATH' ) || exit;

class YZMF_CP_Frontend {

    public static function init() {
        // parse_request: muy temprano, antes de que WP decida si es 404.
        // Captamos /g/{token} directamente desde REQUEST_URI sin depender
        // de rewrite rules (que pueden no estar flusheadas).
        add_action( 'parse_request', [ __CLASS__, 'capture_request' ], 1 );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_render' ], 1 );
    }

    /**
     * Detecta /g/{token} en la URL aunque las rewrite rules no estén
     * flusheadas. Esto es el "cinturón de seguridad" para deploys SFTP.
     */
    public static function capture_request( $wp ) {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        // Quitar prefijos de subdirectorio si WP está instalado en uno
        $home_path = parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/';
        if ( $home_path !== '/' && strpos( $uri, $home_path ) === 0 ) {
            $uri = '/' . ltrim( substr( $uri, strlen( $home_path ) ), '/' );
        }
        // Quitar query string
        $path = strtok( $uri, '?' );
        if ( preg_match( '~^/g/([A-Za-z0-9_-]+)/?$~', $path, $m ) ) {
            $wp->query_vars['yzmf_cp_token'] = $m[1];
            // Forzar a que WP no marque esto como 404
            $wp->matched_rule = 'yzmf-cp-fallback';
        }
    }

    public static function maybe_render() {
        // Buscar token en query var (si rewrite OK) o en parse_request fallback
        $token = get_query_var( 'yzmf_cp_token' );
        if ( ! $token && isset( $GLOBALS['wp']->query_vars['yzmf_cp_token'] ) ) {
            $token = $GLOBALS['wp']->query_vars['yzmf_cp_token'];
        }
        if ( ! $token ) return;

        $post = YZMF_CP_CPT::find_by_token( $token );
        if ( ! $post ) {
            status_header( 404 );
            self::render_message( 'Galería no encontrada', 'El enlace que has utilizado no es válido o ha sido eliminado.' );
            exit;
        }
        if ( YZMF_CP_CPT::is_expired( $post ) ) {
            status_header( 410 );
            self::render_message( 'Galería expirada', 'Este enlace ya no está disponible. Contacta con el fotógrafo si necesitas acceso.' );
            exit;
        }

        self::render_gallery( $post, $token );
        exit;
    }

    private static function render_gallery( $post, $token ) {
        $title = esc_html( get_the_title( $post ) );
        $client_name = esc_html( (string) get_post_meta( $post->ID, '_yzmf_cp_client_name', true ) );
        $message = wp_kses_post( (string) get_post_meta( $post->ID, '_yzmf_cp_message', true ) );

        // Inline assets para no depender del enqueue de WP
        $css_url = YZMF_CP_URL . 'assets/portal.css?v=' . YZMF_CP_VERSION;
        $js_url  = YZMF_CP_URL . 'assets/portal.js?v=' . YZMF_CP_VERSION;
        $api_base = home_url( '/wp-json/yzmf/v1/cp/' );

        ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
<meta name="robots" content="noindex,nofollow" />
<title><?php echo $title; ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>" />
</head>
<body>
<div id="yzmf-cp"
    data-token="<?php echo esc_attr( $token ); ?>"
    data-api="<?php echo esc_attr( $api_base ); ?>"
    data-title="<?php echo esc_attr( $title ); ?>"
    data-client="<?php echo esc_attr( $client_name ); ?>"
    data-message="<?php echo esc_attr( $message ); ?>">
    <noscript>Esta galería requiere JavaScript activado.</noscript>
</div>
<script src="<?php echo esc_url( $js_url ); ?>"></script>
</body>
</html><?php
    }

    private static function render_message( $title, $body ) {
        ?><!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8" /><title><?php echo esc_html( $title ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( YZMF_CP_URL . 'assets/portal.css' ); ?>" />
</head><body>
<div class="yzmf-cp-message">
    <h1><?php echo esc_html( $title ); ?></h1>
    <p><?php echo esc_html( $body ); ?></p>
</div>
</body></html><?php
    }
}
