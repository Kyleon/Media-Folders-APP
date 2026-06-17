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
            // Por defecto WP usa _update_post_term_count que solo cuenta posts
            // con status 'publish'. Los attachments tienen status 'inherit', así
            // que el conteo siempre era 0. _update_generic_term_count cuenta
            // todos los objetos sin importar el status.
            'update_count_callback' => '_update_generic_term_count',
        ] );

        // Backfill: si la opción no está marcada, recontamos todos los términos
        // una vez para que el count refleje la realidad tras este fix.
        if ( get_option( 'yzmf_term_count_recounted' ) !== 'yes' ) {
            $tt_ids = get_terms( [
                'taxonomy'   => YZMF_TAXONOMY,
                'fields'     => 'tt_ids',
                'hide_empty' => false,
            ] );
            if ( ! is_wp_error( $tt_ids ) && ! empty( $tt_ids ) ) {
                wp_update_term_count_now( $tt_ids, YZMF_TAXONOMY );
            }
            update_option( 'yzmf_term_count_recounted', 'yes', false );
        }
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
