<?php
/**
 * Integración con Elementor: registra una categoría propia y los
 * widgets del plugin. Carga condicional, sin error si Elementor
 * no está activo.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Elementor_Integration {

    public static function init() {
        // Si Elementor no está activo, no hacemos nada.
        if ( ! did_action( 'elementor/loaded' ) && ! defined( 'ELEMENTOR_VERSION' ) ) {
            add_action( 'plugins_loaded', [ __CLASS__, 'maybe_register' ], 20 );
            return;
        }
        self::maybe_register();
    }

    public static function maybe_register() {
        if ( ! did_action( 'elementor/loaded' ) ) return;

        add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
        add_action( 'elementor/widgets/register',               [ __CLASS__, 'register_widgets' ] );
    }

    /**
     * Categoría "Yezrael" para agrupar widgets propios del plugin.
     */
    public static function register_category( $elements_manager ) {
        $elements_manager->add_category( 'yzmf', [
            'title' => __( 'Yezrael', 'yz-media-folders' ),
            'icon'  => 'fa fa-camera',
        ] );
    }

    public static function register_widgets( $widgets_manager ) {
        require_once YZMF_PATH . 'includes/elementor/class-widget-slider.php';

        if ( class_exists( 'YZMF_Elementor_Widget_Slider' ) ) {
            $widgets_manager->register( new \YZMF_Elementor_Widget_Slider() );
        }
    }
}
