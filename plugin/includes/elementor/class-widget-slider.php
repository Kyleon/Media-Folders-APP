<?php
/**
 * Widget Elementor: YZMF Slider.
 * Permite insertar un slider yzmf_slider en cualquier página Elementor
 * con UI nativa. Internamente delega al shortcode [yzmf_slider] para
 * mantener una sola fuente de renderizado.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
    return;
}

class YZMF_Elementor_Widget_Slider extends \Elementor\Widget_Base {

    public function get_name() {
        return 'yzmf_slider';
    }

    public function get_title() {
        return __( 'YZMF Slider', 'yz-media-folders' );
    }

    public function get_icon() {
        return 'eicon-slider-push';
    }

    public function get_categories() {
        return [ 'yzmf' ];
    }

    public function get_keywords() {
        return [ 'slider', 'yzmf', 'carousel', 'galeria' ];
    }

    /**
     * Lista los sliders disponibles para poblar el select.
     */
    private function get_slider_options() {
        $opts = [ 0 => __( '— Selecciona un slider —', 'yz-media-folders' ) ];

        if ( ! class_exists( 'YZMF_Slider' ) ) return $opts;

        $posts = get_posts( [
            'post_type'      => YZMF_Slider::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ] );

        foreach ( $posts as $p ) {
            $opts[ $p->ID ] = $p->post_title . ' (#' . $p->ID . ')';
        }

        return $opts;
    }

    protected function register_controls() {

        /* ─────────── Sección principal ─────────── */
        $this->start_controls_section( 'section_slider', [
            'label' => __( 'Slider', 'yz-media-folders' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'slider_id', [
            'label'       => __( 'Slider', 'yz-media-folders' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => $this->get_slider_options(),
            'default'     => 0,
            'description' => __( 'Crea o edita los sliders desde la PWA (app.yezraelperez.es) o desde la API REST.', 'yz-media-folders' ),
        ] );

        $this->add_control( 'manage_link', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => '<a href="https://app.yezraelperez.es/sliders" target="_blank" rel="noopener" class="elementor-button elementor-button-default" style="display:inline-block;margin-top:6px">⊞ Gestionar sliders →</a>',
            'content_classes' => 'elementor-descriptor',
        ] );

        $this->end_controls_section();

        /* ─────────── Overrides de configuración ─────────── */
        $this->start_controls_section( 'section_overrides', [
            'label' => __( 'Sobreescribir ajustes', 'yz-media-folders' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'overrides_help', [
            'type' => \Elementor\Controls_Manager::RAW_HTML,
            'raw'  => '<small>Deja en blanco para usar el valor configurado en el slider. Solo rellena lo que quieras forzar aquí.</small>',
            'content_classes' => 'elementor-descriptor',
        ] );

        $this->add_control( 'height', [
            'label'       => __( 'Altura', 'yz-media-folders' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '100vh',
            'description' => __( 'Acepta cualquier valor CSS válido: 100vh, 600px, 80vh, etc.', 'yz-media-folders' ),
        ] );

        $this->add_control( 'autoplay', [
            'label'        => __( 'Autoplay', 'yz-media-folders' ),
            'type'         => \Elementor\Controls_Manager::SELECT,
            'options'      => [
                ''       => __( '— Usar el del slider —', 'yz-media-folders' ),
                'true'   => __( 'Sí', 'yz-media-folders' ),
                'false'  => __( 'No', 'yz-media-folders' ),
            ],
            'default'      => '',
        ] );

        $this->add_control( 'speed', [
            'label'       => __( 'Velocidad (ms)', 'yz-media-folders' ),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 500,
            'step'        => 500,
            'placeholder' => '6000',
        ] );

        $this->add_control( 'loop', [
            'label'   => __( 'Loop infinito', 'yz-media-folders' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''      => __( '— Usar el del slider —', 'yz-media-folders' ),
                'true'  => __( 'Sí', 'yz-media-folders' ),
                'false' => __( 'No', 'yz-media-folders' ),
            ],
            'default' => '',
        ] );

        $this->add_control( 'navigation', [
            'label'   => __( 'Flechas de navegación', 'yz-media-folders' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''      => __( '— Usar el del slider —', 'yz-media-folders' ),
                'true'  => __( 'Mostrar', 'yz-media-folders' ),
                'false' => __( 'Ocultar', 'yz-media-folders' ),
            ],
            'default' => '',
        ] );

        $this->add_control( 'pagination', [
            'label'   => __( 'Paginación', 'yz-media-folders' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''         => __( '— Usar el del slider —', 'yz-media-folders' ),
                'bullets'  => __( 'Bullets', 'yz-media-folders' ),
                'progress' => __( 'Barra de progreso', 'yz-media-folders' ),
                'none'     => __( 'Sin paginación', 'yz-media-folders' ),
            ],
            'default' => '',
        ] );

        $this->add_control( 'transition', [
            'label'   => __( 'Transición', 'yz-media-folders' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''      => __( '— Usar el del slider —', 'yz-media-folders' ),
                'slide' => __( 'Deslizar', 'yz-media-folders' ),
                'fade'  => __( 'Fundido', 'yz-media-folders' ),
            ],
            'default' => '',
        ] );

        $this->end_controls_section();
    }

    /**
     * Render del widget en frontend.
     * Construye el shortcode con los atributos no vacíos y deja que
     * YZMF_Slider_Shortcode haga el render real (incluyendo enqueue
     * condicional de assets).
     */
    protected function render() {
        $s = $this->get_settings_for_display();
        $id = (int) ( $s['slider_id'] ?? 0 );

        if ( ! $id ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="padding:40px;background:#1a1a1a;color:#999;text-align:center;border:2px dashed #444;">'
                   . esc_html__( 'YZMF Slider: selecciona un slider en la pestaña Contenido →', 'yz-media-folders' )
                   . '</div>';
            }
            return;
        }

        $atts = [ 'id="' . $id . '"' ];

        $maybe_text  = [ 'height', 'speed', 'autoplay', 'loop', 'navigation', 'pagination', 'transition' ];
        foreach ( $maybe_text as $key ) {
            $val = trim( (string) ( $s[ $key ] ?? '' ) );
            if ( $val !== '' ) {
                $atts[] = $key . '="' . esc_attr( $val ) . '"';
            }
        }

        echo do_shortcode( '[yzmf_slider ' . implode( ' ', $atts ) . ']' );
    }

    /**
     * Render en el editor (en preview JS). No lo implementamos para
     * forzar render server-side y mantener una única fuente de verdad.
     */
    protected function content_template() {
        // Vacío a propósito: Elementor caerá al render() PHP.
    }
}
