<?php
/**
 * Shortcode [yzmf_slider id="N"] y enqueue condicional de assets.
 *
 * Usa Swiper 11 cargado como módulo ES desde CDN. El JS del plugin se
 * sirve como <script type="module"> para evitar colisión con la
 * versión de Swiper que cargue el tema en window.Swiper.
 *
 * Atributos del shortcode (sobrescriben settings del JSON):
 *   id          ID del slider (obligatorio)
 *   height      altura CSS (ej: "80vh", "600px")
 *   autoplay    "true" | "false"
 *   speed       milisegundos
 *   loop        "true" | "false"
 *   navigation  "true" | "false"
 *   pagination  "bullets" | "progress" | "none"
 *   transition  "slide" | "fade"
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Slider_Shortcode {

    const HANDLE_CSS_LIB = 'yzmf-swiper';
    const HANDLE_CSS     = 'yzmf-slider';
    const HANDLE_JS      = 'yzmf-slider';

    const SWIPER_VERSION = '11';
    const SWIPER_CSS_URL = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
        // Marca el JS como module en el <script> tag
        add_filter( 'script_loader_tag', [ __CLASS__, 'add_type_module' ], 10, 3 );

        // En el preview de Elementor (iframe del editor) los widgets se
        // insertan por AJAX, así que un wp_enqueue dentro del callback del
        // shortcode llega tarde y los assets no se aplican. Forzamos el
        // enqueue cuando estamos en preview o en el editor.
        add_action( 'elementor/preview/enqueue_styles',  [ __CLASS__, 'force_enqueue_assets' ] );
        add_action( 'elementor/preview/enqueue_scripts', [ __CLASS__, 'force_enqueue_assets' ] );
        add_action( 'elementor/editor/before_enqueue_scripts', [ __CLASS__, 'force_enqueue_assets' ] );
    }

    /** Encola los assets sin condiciones; útil en el preview de Elementor. */
    public static function force_enqueue_assets() {
        wp_enqueue_style( self::HANDLE_CSS );
        wp_enqueue_script( self::HANDLE_JS );
    }

    public static function register_shortcode() {
        add_shortcode( 'yzmf_slider', [ __CLASS__, 'render_shortcode' ] );
    }

    /**
     * Registra los assets sin enqueuear. Se enquean condicionalmente
     * dentro del callback del shortcode (cuando se usa de verdad).
     */
    public static function register_assets() {
        wp_register_style(
            self::HANDLE_CSS_LIB,
            self::SWIPER_CSS_URL,
            [],
            self::SWIPER_VERSION
        );
        wp_register_style(
            self::HANDLE_CSS,
            YZMF_URL . 'assets/css/yzmf-slider.css',
            [ self::HANDLE_CSS_LIB ],
            YZMF_VERSION
        );
        wp_register_script(
            self::HANDLE_JS,
            YZMF_URL . 'assets/js/yzmf-slider.js',
            [],
            YZMF_VERSION,
            true
        );
    }

    /**
     * Convierte el <script> del slider en <script type="module">
     * para poder usar `import` desde un CDN ESM. Reemplaza cualquier
     * type previo (WP suele añadir type="text/javascript") en lugar
     * de añadir un segundo, lo que generaría HTML inválido.
     */
    public static function add_type_module( $tag, $handle, $src ) {
        if ( $handle !== self::HANDLE_JS ) return $tag;
        // Quita cualquier type="..." existente
        $tag = preg_replace( '/\stype=(["\'])[^"\']*\1/', '', $tag );
        return str_replace( '<script ', '<script type="module" ', $tag );
    }

    /**
     * Callback del shortcode.
     */
    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts( [
            'id'         => 0,
            'height'     => '',
            'autoplay'   => '',
            'speed'      => '',
            'loop'       => '',
            'navigation' => '',
            'pagination' => '',
            'transition' => '',
        ], $atts, 'yzmf_slider' );

        $id   = (int) $atts['id'];
        $data = YZMF_Slider::get_data( $id );

        if ( ! $data ) {
            return '<!-- yzmf_slider: id ' . esc_attr( $id ) . ' no encontrado -->';
        }
        if ( empty( $data['slides'] ) ) {
            return '<!-- yzmf_slider: el slider ' . esc_attr( $id ) . ' no tiene slides -->';
        }

        // Override settings desde atributos del shortcode
        $settings = self::merge_shortcode_atts( $data['settings'], $atts );

        // Enqueue condicional
        wp_enqueue_style( self::HANDLE_CSS );
        wp_enqueue_script( self::HANDLE_JS );

        return self::render_html( $id, $settings, $data['slides'] );
    }

    private static function merge_shortcode_atts( $settings, $atts ) {
        if ( $atts['height']     !== '' ) $settings['height']     = $atts['height'];
        if ( $atts['autoplay']   !== '' ) $settings['autoplay']   = self::to_bool( $atts['autoplay'] );
        if ( $atts['speed']      !== '' ) $settings['speed']      = (int) $atts['speed'];
        if ( $atts['loop']       !== '' ) $settings['loop']       = self::to_bool( $atts['loop'] );
        if ( $atts['navigation'] !== '' ) $settings['navigation'] = self::to_bool( $atts['navigation'] );
        if ( $atts['pagination'] !== '' ) $settings['pagination'] = $atts['pagination'];
        if ( $atts['transition'] !== '' ) $settings['transition'] = $atts['transition'];
        return $settings;
    }

    private static function to_bool( $v ) {
        return in_array( strtolower( (string) $v ), [ '1', 'true', 'yes', 'on' ], true );
    }

    /* ─────────── HTML render ─────────── */

    private static function render_html( $slider_id, $settings, $slides ) {
        $wrap_id    = 'yzmf-slider-' . $slider_id;
        $height     = esc_attr( $settings['height'] );
        $settings_j = wp_json_encode( $settings );

        $slides_html = '';
        foreach ( $slides as $slide ) {
            $slides_html .= self::render_slide( $slide );
        }

        $nav_html = $settings['navigation']
            ? '<button class="swiper-button-prev yzmf-nav-prev" aria-label="Anterior"></button>'
            . '<button class="swiper-button-next yzmf-nav-next" aria-label="Siguiente"></button>'
            : '';

        $pag_html = $settings['pagination'] !== 'none'
            ? '<div class="swiper-pagination yzmf-pagination"></div>'
            : '';

        return sprintf(
            '<div class="yzmf-slider" id="%s" data-yzmf-slider data-settings="%s" style="--yzmf-slider-height:%s;">'
          . '<div class="swiper yzmf-swiper">'
          . '<div class="swiper-wrapper">%s</div>'
          . '%s%s'
          . '</div></div>',
            esc_attr( $wrap_id ),
            esc_attr( $settings_j ),
            $height,
            $slides_html,
            $pag_html,
            $nav_html
        );
    }

    private static function render_slide( $slide ) {
        $style    = $slide['style'];
        $align    = self::sanitize_alignment( $style['text_alignment'] );
        $vpos     = self::sanitize_vpos( $style['vertical_position'] );
        $kenburns = ! empty( $style['kenburns'] ) ? ' yzmf-kenburns' : '';

        $bg = self::render_slide_bg( $slide );
        $overlay = sprintf(
            '<div class="yzmf-slide-overlay" style="background-color:%s; opacity:%.2f;"></div>',
            esc_attr( $style['overlay_color'] ),
            (float) $style['overlay_opacity']
        );

        $content = self::render_slide_content( $slide, $style );

        return sprintf(
            '<div class="swiper-slide yzmf-slide yzmf-align-%s yzmf-vpos-%s%s" data-slide-id="%s">%s%s%s</div>',
            esc_attr( $align ),
            esc_attr( $vpos ),
            $kenburns,
            esc_attr( $slide['id'] ),
            $bg,
            $overlay,
            $content
        );
    }

    private static function render_slide_bg( $slide ) {
        if ( $slide['type'] === 'video_file' && ! empty( $slide['video_id'] ) ) {
            $url = wp_get_attachment_url( $slide['video_id'] );
            if ( ! $url ) return '';
            return sprintf(
                '<video class="yzmf-slide-video" autoplay loop muted playsinline preload="metadata"><source src="%s" type="video/mp4"></video>',
                esc_url( $url )
            );
        }

        if ( $slide['type'] === 'video_embed' && ! empty( $slide['video_embed_url'] ) ) {
            return sprintf(
                '<iframe class="yzmf-slide-iframe" src="%s" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>',
                esc_url( $slide['video_embed_url'] )
            );
        }

        // type=image (o fallback)
        if ( ! empty( $slide['image_id'] ) ) {
            $url = wp_get_attachment_image_url( $slide['image_id'], 'full' );
            if ( ! $url ) return '';
            $alt = esc_attr( get_post_meta( $slide['image_id'], '_wp_attachment_image_alt', true ) ?: $slide['title'] );
            return sprintf(
                '<div class="yzmf-slide-bg" role="img" aria-label="%s" data-bg="%s" style="background-image:url(%s);"></div>',
                $alt,
                esc_url( $url ),
                esc_url( $url )
            );
        }

        return '';
    }

    private static function render_slide_content( $slide, $style ) {
        $has_any = $slide['title'] !== '' || $slide['subtitle'] !== '' || $slide['text'] !== ''
                || $slide['location'] !== '' || $slide['button_text'] !== '';
        if ( ! $has_any ) return '';

        $color = esc_attr( $style['text_color'] );
        $parts = [];

        if ( $slide['subtitle'] !== '' ) {
            $parts[] = sprintf( '<span class="yzmf-slide-subtitle">%s</span>', esc_html( $slide['subtitle'] ) );
        }
        if ( $slide['title'] !== '' ) {
            $parts[] = sprintf( '<h2 class="yzmf-slide-title">%s</h2>', esc_html( $slide['title'] ) );
        }
        if ( $slide['text'] !== '' ) {
            $parts[] = sprintf( '<p class="yzmf-slide-text">%s</p>', esc_html( $slide['text'] ) );
        }
        if ( $slide['location'] !== '' ) {
            $parts[] = sprintf( '<span class="yzmf-slide-location">%s %s</span>', self::pin_icon(), esc_html( $slide['location'] ) );
        }
        if ( $slide['button_text'] !== '' && $slide['button_url'] !== '' ) {
            $parts[] = sprintf(
                '<a class="yzmf-slide-button" href="%s">%s</a>',
                esc_url( $slide['button_url'] ),
                esc_html( $slide['button_text'] )
            );
        }

        return sprintf(
            '<div class="yzmf-slide-content" style="color:%s;">%s</div>',
            $color,
            implode( '', $parts )
        );
    }

    private static function sanitize_alignment( $v ) {
        return in_array( $v, [ 'start', 'center', 'end' ], true ) ? $v : 'center';
    }

    private static function sanitize_vpos( $v ) {
        return in_array( $v, [ 'top', 'center', 'bottom' ], true ) ? $v : 'center';
    }

    private static function pin_icon() {
        return '<svg class="yzmf-pin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    }
}
