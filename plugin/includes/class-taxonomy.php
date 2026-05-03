<?php
defined( 'ABSPATH' ) || exit;

class YZMF_Taxonomy {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
    }

    public static function register() {
        register_taxonomy( YZMF_TAXONOMY, 'attachment', [
            'labels'       => [
                'name'          => __( 'Carpetas', 'yz-media-folders' ),
                'singular_name' => __( 'Carpeta',  'yz-media-folders' ),
            ],
            'hierarchical'  => true,
            'public'        => false,
            'show_ui'       => false,
            'show_in_rest'  => true,
            'rewrite'       => false,
        ] );
    }

    /** Árbol anidado de carpetas */
    public static function get_tree( $parent = 0 ) {
        $terms = get_terms( [
            'taxonomy'   => YZMF_TAXONOMY,
            'hide_empty' => false,
            'parent'     => $parent,
            'orderby'    => 'name',
        ] );
        if ( is_wp_error( $terms ) ) return [];
        $tree = [];
        foreach ( $terms as $t ) {
            $tree[] = [
                'id'       => $t->term_id,
                'name'     => $t->name,
                'slug'     => $t->slug,
                'parent'   => $t->parent,
                'count'    => (int) $t->count,
                'children' => self::get_tree( $t->term_id ),
            ];
        }
        return $tree;
    }

    /** Lista plana */
    public static function get_flat() {
        $terms = get_terms( [
            'taxonomy'   => YZMF_TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
        ] );
        return is_wp_error( $terms ) ? [] : $terms;
    }
}
