<?php
/**
 * Configuración de marca: logo + nombre que aparecen en la cabecera de la PWA.
 *
 * Endpoints:
 *   GET  /yzmf/v1/brand           Devuelve logo_id, logo_url, name, primary_color
 *   PUT  /yzmf/v1/brand           Actualiza la marca (auth: manage_options)
 *
 * Opciones WP utilizadas:
 *   yzmf_brand_name          string    Nombre de la marca (vacío = bloginfo('name'))
 *   yzmf_brand_logo_id       int       ID del attachment del logo
 *   yzmf_brand_primary_color string    Color principal de acento (#RRGGBB)
 *   yzmf_brand_initials      string    Iniciales como fallback cuando no hay logo
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Brand {

    const NS = 'yzmf/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( self::NS, '/brand', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_brand' ],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'put_brand' ],
                'permission_callback' => function () { return current_user_can( 'manage_options' ); },
            ],
        ] );
    }

    public static function get_brand() {
        return rest_ensure_response( self::current() );
    }

    public static function put_brand( WP_REST_Request $req ) {
        $name = $req->get_param( 'name' );
        if ( $name !== null ) {
            update_option( 'yzmf_brand_name', sanitize_text_field( (string) $name ) );
        }

        $logo_id = $req->get_param( 'logo_id' );
        if ( $logo_id !== null ) {
            $logo_id = (int) $logo_id;
            if ( $logo_id <= 0 ) {
                delete_option( 'yzmf_brand_logo_id' );
            } else {
                if ( get_post_type( $logo_id ) !== 'attachment' ) {
                    return new WP_Error( 'invalid_logo', 'logo_id no es un attachment', [ 'status' => 400 ] );
                }
                update_option( 'yzmf_brand_logo_id', $logo_id );
            }
        }

        $color = $req->get_param( 'primary_color' );
        if ( $color !== null ) {
            $hex = self::sanitize_hex( (string) $color );
            if ( $hex ) update_option( 'yzmf_brand_primary_color', $hex );
            else delete_option( 'yzmf_brand_primary_color' );
        }

        $initials = $req->get_param( 'initials' );
        if ( $initials !== null ) {
            $val = strtoupper( substr( preg_replace( '/[^A-Za-z0-9]/u', '', (string) $initials ), 0, 3 ) );
            update_option( 'yzmf_brand_initials', $val );
        }

        return rest_ensure_response( self::current() );
    }

    public static function current() {
        $name     = (string) get_option( 'yzmf_brand_name', '' );
        $logo_id  = (int) get_option( 'yzmf_brand_logo_id', 0 );
        $color    = (string) get_option( 'yzmf_brand_primary_color', '' );
        $initials = (string) get_option( 'yzmf_brand_initials', '' );

        if ( ! $name ) $name = (string) get_bloginfo( 'name' );

        $logo_url = '';
        $logo_mime = '';
        if ( $logo_id ) {
            $logo_url  = wp_get_attachment_url( $logo_id );
            $logo_mime = (string) get_post_mime_type( $logo_id );
        }

        if ( ! $initials ) {
            // Calcular iniciales a partir del nombre
            $words = preg_split( '/\s+/', trim( $name ) );
            $ini = '';
            foreach ( array_slice( $words, 0, 2 ) as $w ) {
                if ( $w !== '' ) $ini .= mb_strtoupper( mb_substr( $w, 0, 1 ) );
            }
            $initials = $ini ?: 'YZ';
        }

        return [
            'name'           => $name,
            'logo_id'        => $logo_id,
            'logo_url'       => $logo_url,
            'logo_mime'      => $logo_mime,
            'primary_color'  => $color,
            'initials'       => $initials,
        ];
    }

    private static function sanitize_hex( $value ) {
        $v = trim( $value );
        if ( $v === '' ) return '';
        if ( $v[0] !== '#' ) $v = '#' . $v;
        if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $v ) ) return strtoupper( $v );
        return null;
    }
}
