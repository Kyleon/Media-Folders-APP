<?php
/**
 * CPT yzmf_slider y helpers de gestión del JSON de slides.
 *
 * Almacena cada slider como un post del CPT con un meta JSON único
 * (_yzmf_slider_data) que contiene settings + array de slides. La
 * gestión externa (PWA, shortcode, widget) usa los helpers estáticos
 * de esta clase para leer y escribir.
 *
 * Decisiones de diseño en docs/SLIDER_DESIGN.md.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Slider {

    const POST_TYPE  = 'yzmf_slider';
    const META_DATA  = '_yzmf_slider_data';
    const META_THUMB = '_yzmf_slider_thumbnail';
    const VERSION    = 1;

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels'          => [
                'name'          => __( 'Sliders', 'yz-media-folders' ),
                'singular_name' => __( 'Slider',  'yz-media-folders' ),
            ],
            'public'          => false,    // privado, solo embed
            'show_ui'         => false,    // gestionado desde la PWA
            'show_in_menu'    => false,
            'show_in_rest'    => false,    // REST propio en yzmf/v1
            'has_archive'     => false,
            'capability_type' => 'post',
            'map_meta_cap'    => true,
            'supports'        => [ 'title' ],
        ] );
    }

    /* ─────────── Helpers de datos ─────────── */

    /**
     * Devuelve el JSON parseado del slider con defaults aplicados.
     * Si el slider no existe o no tiene datos, devuelve la estructura
     * por defecto (settings + slides vacío).
     */
    public static function get_data( $id ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return null;
        }

        $raw = get_post_meta( $id, self::META_DATA, true );
        if ( empty( $raw ) ) {
            return self::default_data();
        }

        $data = is_array( $raw ) ? $raw : json_decode( $raw, true );
        if ( ! is_array( $data ) ) {
            return self::default_data();
        }

        return self::merge_with_defaults( $data );
    }

    /**
     * Guarda el JSON validado y normalizado.
     * Devuelve true en éxito, WP_Error si la validación falla.
     */
    public static function save_data( $id, $data ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return new WP_Error( 'yzmf_slider_not_found', 'Slider no encontrado', [ 'status' => 404 ] );
        }

        $validated = self::validate( $data );
        if ( is_wp_error( $validated ) ) {
            return $validated;
        }

        update_post_meta( $id, self::META_DATA, wp_slash( wp_json_encode( $validated ) ) );

        // Cachea el primer slide image_id como thumbnail para listados rápidos
        $first_image = self::find_first_image_id( $validated );
        if ( $first_image ) {
            update_post_meta( $id, self::META_THUMB, $first_image );
        } else {
            delete_post_meta( $id, self::META_THUMB );
        }

        return true;
    }

    /**
     * Valida y normaliza el JSON entrante. Tolera campos faltantes,
     * rellena con defaults. Falla si el formato base es incorrecto.
     */
    public static function validate( $data ) {
        if ( ! is_array( $data ) ) {
            return new WP_Error( 'yzmf_slider_invalid', 'data debe ser un objeto', [ 'status' => 400 ] );
        }

        $out = [
            'version'  => self::VERSION,
            'settings' => self::merge_settings( $data['settings'] ?? [] ),
            'slides'   => [],
        ];

        $slides = $data['slides'] ?? [];
        if ( ! is_array( $slides ) ) {
            return new WP_Error( 'yzmf_slider_invalid_slides', 'slides debe ser un array', [ 'status' => 400 ] );
        }

        foreach ( $slides as $slide ) {
            if ( ! is_array( $slide ) ) continue;
            $out['slides'][] = self::normalize_slide( $slide );
        }

        return $out;
    }

    /* ─────────── Defaults y normalización ─────────── */

    public static function default_data() {
        return [
            'version'  => self::VERSION,
            'settings' => self::default_settings(),
            'slides'   => [],
        ];
    }

    public static function default_settings() {
        return [
            'autoplay'   => true,
            'speed'      => 6000,
            'transition' => 'slide',     // slide | fade
            'navigation' => true,
            'pagination' => 'bullets',   // bullets | progress | none
            'loop'       => true,
            'kenburns'   => true,
            'height'     => '100vh',
        ];
    }

    public static function default_slide_style() {
        return [
            'overlay_color'     => '#000000',
            'overlay_opacity'   => 0.3,
            'text_color'        => '#ffffff',
            'text_alignment'    => 'center',  // start | center | end
            'vertical_position' => 'center',  // top | center | bottom
            'kenburns'          => true,
        ];
    }

    public static function default_slide() {
        return [
            'id'              => self::new_slide_id(),
            'type'            => 'image',     // image | video_file | video_embed
            'image_id'        => 0,
            'video_id'        => 0,
            'video_embed_url' => '',
            'title'           => '',
            'subtitle'        => '',
            'text'            => '',
            'location'        => '',
            'lat'             => null,
            'lng'             => null,
            'button_text'     => '',
            'button_url'      => '',
            'style'           => self::default_slide_style(),
        ];
    }

    public static function new_slide_id() {
        return 'slide_' . substr( md5( uniqid( '', true ) ), 0, 8 );
    }

    private static function merge_with_defaults( $data ) {
        $data['version']  = (int) ( $data['version']  ?? self::VERSION );
        $data['settings'] = self::merge_settings( $data['settings'] ?? [] );
        $data['slides']   = array_map(
            [ __CLASS__, 'normalize_slide' ],
            is_array( $data['slides'] ?? null ) ? $data['slides'] : []
        );
        return $data;
    }

    private static function merge_settings( $settings ) {
        if ( ! is_array( $settings ) ) $settings = [];
        return array_merge( self::default_settings(), $settings );
    }

    private static function normalize_slide( $slide ) {
        $base = self::default_slide();
        // Conserva el id si viene; si no, genera uno nuevo
        if ( empty( $slide['id'] ) ) {
            $slide['id'] = self::new_slide_id();
        }
        // Tipo válido
        $valid_types = [ 'image', 'video_file', 'video_embed' ];
        if ( empty( $slide['type'] ) || ! in_array( $slide['type'], $valid_types, true ) ) {
            $slide['type'] = 'image';
        }
        // Casts numéricos
        $slide['image_id'] = isset( $slide['image_id'] ) ? (int) $slide['image_id'] : 0;
        $slide['video_id'] = isset( $slide['video_id'] ) ? (int) $slide['video_id'] : 0;
        $slide['lat'] = isset( $slide['lat'] ) && $slide['lat'] !== '' ? (float) $slide['lat'] : null;
        $slide['lng'] = isset( $slide['lng'] ) && $slide['lng'] !== '' ? (float) $slide['lng'] : null;
        // Estilo: merge con defaults
        $slide['style'] = is_array( $slide['style'] ?? null )
            ? array_merge( self::default_slide_style(), $slide['style'] )
            : self::default_slide_style();
        // Mezcla final con base para garantizar todos los campos
        return array_merge( $base, $slide );
    }

    private static function find_first_image_id( $data ) {
        foreach ( $data['slides'] as $slide ) {
            if ( $slide['type'] === 'image' && ! empty( $slide['image_id'] ) ) {
                return (int) $slide['image_id'];
            }
        }
        return 0;
    }

    /* ─────────── Operaciones de alto nivel ─────────── */

    /**
     * Duplica un slider con su título "(copia)" y los mismos slides.
     * Retorna el ID del nuevo post o WP_Error.
     */
    public static function duplicate( $id ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return new WP_Error( 'yzmf_slider_not_found', 'Slider no encontrado', [ 'status' => 404 ] );
        }

        $new_id = wp_insert_post( [
            'post_type'   => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $post->post_title . ' (copia)',
        ], true );

        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        $data = self::get_data( $id );
        // Regenera IDs de slides para que sean únicos en el clon
        foreach ( $data['slides'] as &$slide ) {
            $slide['id'] = self::new_slide_id();
        }
        unset( $slide );

        self::save_data( $new_id, $data );

        return $new_id;
    }

    /**
     * Resumen para listados (sin slides completos).
     */
    public static function summary( $id ) {
        $post = get_post( $id );
        if ( ! $post || $post->post_type !== self::POST_TYPE ) return null;

        $data       = self::get_data( $id );
        $slide_cnt  = count( $data['slides'] );
        $thumb_id   = (int) get_post_meta( $id, self::META_THUMB, true );
        $thumb_url  = $thumb_id ? wp_get_attachment_url( $thumb_id ) : null;

        return [
            'id'           => $id,
            'title'        => $post->post_title,
            'status'       => $post->post_status,
            'slides_count' => $slide_cnt,
            'thumbnail'    => $thumb_url,
            'modified'     => mysql_to_rfc3339( $post->post_modified_gmt ),
        ];
    }
}
