<?php
/**
 * Handler que decide si servir el original o una versión con watermark.
 *
 * Flujo:
 *  1. .htaccess redirige requests de /wp-content/uploads/*.{jpg,png} con
 *     Referer externo a yzmf-hotlink.php?img=PATH.
 *  2. Este handler comprueba whitelist y si procede genera/sirve versión
 *     watermarked desde caché.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_HW_Handler {

    public static function init() {
        // Endpoint custom — registrado en init temprano para que pueda capturar
        // requests de /yzmf-hotlink/{path}
        add_action( 'init', [ __CLASS__, 'register_endpoint' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_serve' ] );
    }

    public static function register_endpoint() {
        add_rewrite_rule( '^yzmf-hotlink/(.+)$', 'index.php?yzmf_hotlink=$matches[1]', 'top' );
        add_rewrite_tag( '%yzmf_hotlink%', '(.+)' );
    }

    public static function maybe_serve() {
        $path = get_query_var( 'yzmf_hotlink' );
        if ( ! $path ) return;

        // Limpieza básica del path
        $path = ltrim( $path, '/' );
        if ( strpos( $path, '..' ) !== false ) self::respond_404();

        $upload_dir = wp_get_upload_dir();
        $abs = trailingslashit( $upload_dir['basedir'] ) . $path;
        if ( ! file_exists( $abs ) || ! is_file( $abs ) ) self::respond_404();

        // Solo procesamos imagenes
        $mime = wp_check_filetype( $abs )['type'] ?? '';
        if ( ! preg_match( '~^image/(jpe?g|png|webp)$~i', $mime ) ) {
            self::stream( $abs, $mime );
        }

        // Si NO es hotlink (referer interno o vacío directo), servir original
        if ( ! self::is_hotlink() ) {
            self::stream( $abs, $mime );
        }

        // Es hotlink → generar/servir versión watermarked
        $cached = self::cached_path( $path );
        if ( ! file_exists( $cached ) ) {
            $ok = self::generate_watermark( $abs, $cached, $mime );
            if ( ! $ok ) self::stream( $abs, $mime );  // fallback
        }
        self::stream( $cached, $mime );
    }

    /* ─────────── Hotlink detection ─────────── */

    private static function is_hotlink() {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        // Sin referer: muchos casos legítimos (escribir URL, RSS, app)
        // Configurable: por defecto NO se considera hotlink
        if ( ! $referer ) {
            return (bool) get_option( 'yzmf_hw_block_empty_referer', false );
        }

        $host = parse_url( $referer, PHP_URL_HOST );
        if ( ! $host ) return false;
        $host = strtolower( $host );

        // Whitelist: dominios permitidos (siempre incluye home_url)
        $whitelist = self::get_whitelist();
        foreach ( $whitelist as $allowed ) {
            $allowed = strtolower( ltrim( $allowed, '*.' ) );
            if ( $host === $allowed || self::ends_with( $host, '.' . $allowed ) ) return false;
        }

        // Excepción común: motores de búsqueda y previsualizadores de redes sociales
        $crawlers = [
            'google.', 'bing.', 'yahoo.', 'duckduckgo.',
            'facebook.', 'fbcdn.', 'twitter.', 't.co', 'instagram.',
            'linkedin.', 'pinterest.', 'whatsapp.', 'telegram.',
        ];
        if ( get_option( 'yzmf_hw_allow_search_engines', true ) ) {
            foreach ( $crawlers as $needle ) {
                if ( strpos( $host, $needle ) !== false ) return false;
            }
        }

        return true;
    }

    public static function get_whitelist() {
        $home = parse_url( home_url(), PHP_URL_HOST );
        $extra = (string) get_option( 'yzmf_hw_whitelist', '' );
        $extra = array_filter( array_map( 'trim', preg_split( '/[\s,]+/', $extra ) ) );
        return array_unique( array_merge( [ $home ], $extra ) );
    }

    private static function ends_with( $haystack, $needle ) {
        $len = strlen( $needle );
        return $len === 0 || substr( $haystack, -$len ) === $needle;
    }

    /* ─────────── Watermark generation ─────────── */

    private static function generate_watermark( $src, $dest, $mime ) {
        $dest_dir = dirname( $dest );
        if ( ! file_exists( $dest_dir ) ) wp_mkdir_p( $dest_dir );

        // Imagick es mejor; GD como fallback
        $editor = wp_get_image_editor( $src );
        if ( is_wp_error( $editor ) ) return false;

        $size = $editor->get_size();
        $w = $size['width'];
        $h = $size['height'];

        // Configuración
        $wm_image = (string) get_option( 'yzmf_hw_image' );
        $wm_text  = (string) get_option( 'yzmf_hw_text', '© ' . get_bloginfo( 'name' ) );
        $opacity  = (int) get_option( 'yzmf_hw_opacity', 60 );
        $position = (string) get_option( 'yzmf_hw_position', 'br' ); // tl|tr|bl|br|center|tile

        // Si tenemos Imagick disponible, hacer composición avanzada
        if ( extension_loaded( 'imagick' ) ) {
            try {
                $im = new Imagick( $src );
                self::imagick_apply( $im, $w, $h, $wm_image, $wm_text, $opacity, $position );
                $im->writeImage( $dest );
                $im->destroy();
                return true;
            } catch ( Exception $e ) {
                // fallthrough a GD
            }
        }

        // GD: añadir solo texto (más simple, no requiere PNG transparente)
        return self::gd_apply( $src, $dest, $wm_text, $opacity, $position, $mime );
    }

    private static function imagick_apply( Imagick $im, $w, $h, $wm_path, $text, $opacity, $position ) {
        $alpha = max( 0, min( 100, $opacity ) ) / 100.0;

        if ( $wm_path && file_exists( $wm_path ) ) {
            $wm = new Imagick( $wm_path );
            // Escalar el watermark al 25% del ancho del src
            $target_w = (int) ( $w * 0.25 );
            $wm->scaleImage( $target_w, 0 );
            $wm->evaluateImage( Imagick::EVALUATE_MULTIPLY, $alpha, Imagick::CHANNEL_ALPHA );

            list( $x, $y ) = self::position_xy( $position, $w, $h, $wm->getImageWidth(), $wm->getImageHeight() );

            if ( $position === 'tile' ) {
                for ( $iy = 0; $iy < $h; $iy += $wm->getImageHeight() + 60 ) {
                    for ( $ix = 0; $ix < $w; $ix += $wm->getImageWidth() + 60 ) {
                        $im->compositeImage( $wm, Imagick::COMPOSITE_OVER, $ix, $iy );
                    }
                }
            } else {
                $im->compositeImage( $wm, Imagick::COMPOSITE_OVER, $x, $y );
            }
            $wm->destroy();
        } elseif ( $text ) {
            $draw = new ImagickDraw();
            $draw->setFillColor( new ImagickPixel( 'rgba(255,255,255,' . $alpha . ')' ) );
            $draw->setFontSize( max( 14, intval( $w / 35 ) ) );
            $draw->setStrokeColor( new ImagickPixel( 'rgba(0,0,0,' . ( $alpha * 0.6 ) . ')' ) );
            $draw->setStrokeWidth( 1 );
            $metrics = $im->queryFontMetrics( $draw, $text );
            $tw = $metrics['textWidth'];
            $th = $metrics['textHeight'];
            list( $x, $y ) = self::position_xy( $position, $w, $h, $tw, $th );
            $im->annotateImage( $draw, $x, $y + $th, 0, $text );
        }
    }

    private static function gd_apply( $src, $dest, $text, $opacity, $position, $mime ) {
        if ( ! function_exists( 'imagecreatefromjpeg' ) ) return false;

        switch ( $mime ) {
            case 'image/jpeg':
            case 'image/jpg':
                $im = @imagecreatefromjpeg( $src ); break;
            case 'image/png':
                $im = @imagecreatefrompng( $src ); break;
            case 'image/webp':
                $im = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $src ) : null; break;
            default: return false;
        }
        if ( ! $im ) return false;

        $w = imagesx( $im ); $h = imagesy( $im );
        if ( $text ) {
            $alpha = (int) ( ( 100 - $opacity ) / 100 * 127 );  // GD: 0=opaco, 127=transp
            $white = imagecolorallocatealpha( $im, 255, 255, 255, $alpha );
            $shadow = imagecolorallocatealpha( $im, 0, 0, 0, $alpha );

            // Tamaño del builtin font 5 (~9x16)
            $fw = strlen( $text ) * 9;
            $fh = 16;
            list( $x, $y ) = self::position_xy( $position, $w, $h, $fw, $fh );
            imagestring( $im, 5, $x + 1, $y + 1, $text, $shadow );
            imagestring( $im, 5, $x, $y, $text, $white );
        }

        switch ( $mime ) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg( $im, $dest, 88 ); break;
            case 'image/png':
                imagepng( $im, $dest ); break;
            case 'image/webp':
                imagewebp( $im, $dest, 88 ); break;
        }
        imagedestroy( $im );
        return true;
    }

    private static function position_xy( $pos, $imgW, $imgH, $wmW, $wmH ) {
        $pad = (int) max( 10, $imgW * 0.02 );
        switch ( $pos ) {
            case 'tl':     return [ $pad, $pad ];
            case 'tr':     return [ $imgW - $wmW - $pad, $pad ];
            case 'bl':     return [ $pad, $imgH - $wmH - $pad ];
            case 'center': return [ ( $imgW - $wmW ) / 2, ( $imgH - $wmH ) / 2 ];
            case 'tile':   return [ 0, 0 ]; // se maneja en imagick_apply
            case 'br':
            default:       return [ $imgW - $wmW - $pad, $imgH - $wmH - $pad ];
        }
    }

    /* ─────────── Cache & streaming ─────────── */

    private static function cached_path( $rel ) {
        return YZMF_HW_CACHE_DIR . '/' . ltrim( $rel, '/' );
    }

    public static function clear_cache() {
        if ( ! file_exists( YZMF_HW_CACHE_DIR ) ) return 0;
        $count = 0;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( YZMF_HW_CACHE_DIR, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ( $it as $f ) {
            if ( $f->isFile() ) { @unlink( $f->getRealPath() ); $count++; }
            elseif ( $f->isDir() ) { @rmdir( $f->getRealPath() ); }
        }
        return $count;
    }

    private static function stream( $path, $mime ) {
        if ( ! headers_sent() ) {
            $size = filesize( $path );
            header( 'Content-Type: ' . $mime );
            header( 'Content-Length: ' . $size );
            header( 'Cache-Control: public, max-age=86400' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $path ) ) . ' GMT' );
        }
        readfile( $path );
        exit;
    }

    private static function respond_404() {
        status_header( 404 );
        nocache_headers();
        exit;
    }
}
