<?php
/**
 * Inserta reglas en .htaccess para que requests directos a /wp-content/uploads/
 * con Referer externo se enruten al handler PHP.
 *
 * Estrategia: añadir un bloque entre marcadores en .htaccess raíz que
 * mod_rewrite interpreta antes que las reglas estándar de WP.
 *
 * Para servidores Nginx hay que configurar manualmente — ver docs/hotlink-nginx.md.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_HW_Rewrite {

    const MARKER = 'YZMF Hotlink Watermark';

    public static function init() {
        // Reinstalar reglas si se actualiza la whitelist
        add_action( 'update_option_yzmf_hw_whitelist', [ __CLASS__, 'install_rules' ] );
        add_action( 'update_option_yzmf_hw_block_empty_referer', [ __CLASS__, 'install_rules' ] );
    }

    public static function rules_block() {
        $home_host = parse_url( home_url(), PHP_URL_HOST );
        $whitelist = YZMF_HW_Handler::get_whitelist();
        $crawlers  = [
            'google\.', 'bing\.', 'yahoo\.', 'duckduckgo\.',
            'facebook\.', 'fbcdn\.', 'twitter\.', 't\.co', 'instagram\.',
            'linkedin\.', 'pinterest\.', 'whatsapp\.', 'telegram\.',
        ];

        $allow_empty = ! get_option( 'yzmf_hw_block_empty_referer', false );

        $lines = [
            '<IfModule mod_rewrite.c>',
            'RewriteEngine On',
            'RewriteBase /',
        ];

        // Permitir referer vacío (configurable)
        if ( $allow_empty ) {
            $lines[] = 'RewriteCond %{HTTP_REFERER} !^$';
        }

        // Permitir whitelist (incluye el propio dominio)
        foreach ( $whitelist as $w ) {
            $w = preg_quote( $w, '#' );
            $lines[] = 'RewriteCond %{HTTP_REFERER} !^https?://(.+\.)?' . $w . '/ [NC]';
        }

        // Permitir crawlers
        if ( get_option( 'yzmf_hw_allow_search_engines', true ) ) {
            foreach ( $crawlers as $c ) {
                $lines[] = 'RewriteCond %{HTTP_REFERER} !' . $c . ' [NC]';
            }
        }

        // Reescribir solo imágenes
        $lines[] = 'RewriteCond %{REQUEST_URI} ^/wp-content/uploads/(.+\.(?:jpe?g|png|webp))$ [NC]';
        $lines[] = 'RewriteRule ^wp-content/uploads/(.+)$ /yzmf-hotlink/$1 [L]';

        $lines[] = '</IfModule>';

        return $lines;
    }

    public static function install_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        $htaccess = trailingslashit( ABSPATH ) . '.htaccess';
        if ( ! is_writable( $htaccess ) && ! is_writable( ABSPATH ) ) {
            return new WP_Error( 'htaccess_unwritable', '.htaccess no es escribible' );
        }
        return insert_with_markers( $htaccess, self::MARKER, self::rules_block() );
    }

    public static function remove_rules() {
        if ( ! function_exists( 'insert_with_markers' ) ) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }
        $htaccess = trailingslashit( ABSPATH ) . '.htaccess';
        if ( file_exists( $htaccess ) ) {
            insert_with_markers( $htaccess, self::MARKER, [] );
        }
    }
}
