<?php
/**
 * Servicio compartido para listado de media.
 *
 * Antes esta lógica estaba duplicada entre YZMF_REST::list_media y
 * YZMF_Ajax::yzmf_get_images (~150 LOC cada una, prácticamente idénticas).
 * Cualquier cambio había que aplicarlo dos veces, con riesgo de drift.
 *
 * Ahora ambas llaman a build_query_args() + run() y reciben el mismo
 * resultado. format_image sigue viviendo en YZMF_Ajax::format_image
 * (la fuente única ya estaba ahí).
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Media_Service {

    /**
     * Construye el array de WP_Query a partir de los params crudos del
     * cliente (REST o AJAX legacy).
     *
     * @param array $p {
     *   @type int    $folder   -1 = todos, 0 = sin carpeta, >0 = id
     *   @type int    $page     1-based
     *   @type int    $per_page 1..100
     *   @type string $search   '' o '__NO_ALT__' o texto
     *   @type string $orderby  date|title|size
     *   @type string $order    ASC|DESC
     *   @type string $mime     ''|image|video|pdf|audio
     *   @type string $tag      filtro por tag IA
     *   @type string $color    filtro hex por color dominante
     * }
     */
    public static function build_query_args( array $p ) {
        $folder   = isset( $p['folder'] ) && $p['folder'] !== null ? intval( $p['folder'] ) : -1;
        $paged    = max( 1, intval( $p['page'] ?? 1 ) );
        $perpage  = min( 100, max( 1, intval( $p['per_page'] ?? 40 ) ) );
        $search   = sanitize_text_field( $p['search'] ?? '' );
        $orderby  = sanitize_key( $p['orderby'] ?? 'date' );
        $order    = strtoupper( $p['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        $mime_in  = sanitize_text_field( $p['mime'] ?? '' );
        $tag      = sanitize_text_field( $p['tag']  ?? '' );
        $color    = sanitize_text_field( $p['color'] ?? '' );

        if ( ! in_array( $orderby, [ 'date', 'title', 'size' ], true ) ) $orderby = 'date';

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $perpage,
            'paged'          => $paged,
            'order'          => $order,
        ];
        if ( $orderby === 'size' ) {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_yzmf_filesize';
        } else {
            $args['orderby'] = $orderby;
        }

        $meta_q = [];
        if ( $search === '__NO_ALT__' ) {
            $meta_q[] = [
                'relation' => 'OR',
                [ 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_wp_attachment_image_alt', 'value' => '', 'compare' => '=' ],
            ];
            $search = '';
        }
        if ( $search ) $args['s'] = $search;

        // Si se filtra por mime, aplica ese tipo. Si no, pasamos la lista
        // explícita de tipos soportados para evitar la rama de WP_Query
        // (post_type=attachment + tax_query + sin post_mime_type) que en
        // algunos setups devuelve 0 filas.
        $mime_map = [
            'image' => 'image/',
            'video' => 'video/',
            'pdf'   => 'application/pdf',
            'audio' => 'audio/',
        ];
        if ( $mime_in && isset( $mime_map[ $mime_in ] ) ) {
            $args['post_mime_type'] = $mime_map[ $mime_in ];
        } else {
            $args['post_mime_type'] = array_values( $mime_map );
        }

        if ( $tag ) {
            $meta_q[] = [ 'key' => '_yzmf_ai_tags', 'value' => '"' . $tag . '"', 'compare' => 'LIKE' ];
        }
        if ( $color && preg_match( '/^#?[0-9A-Fa-f]{6}$/', $color ) ) {
            $hex = strtoupper( ltrim( $color, '#' ) );
            $meta_q[] = [ 'key' => '_yzmf_color_palette', 'value' => $hex, 'compare' => 'LIKE' ];
        }
        if ( ! empty( $meta_q ) ) {
            $meta_q['relation'] = 'AND';
            $args['meta_query'] = $meta_q;
        }

        if ( $folder === 0 ) {
            $args['tax_query'] = [ [
                'taxonomy' => YZMF_TAXONOMY,
                'operator' => 'NOT EXISTS',
            ] ];
        } elseif ( $folder > 0 ) {
            $args['tax_query'] = [ [
                'taxonomy'         => YZMF_TAXONOMY,
                'field'            => 'term_id',
                'terms'            => $folder,
                'include_children' => true,
            ] ];
        }

        return [ 'args' => $args, 'paged' => $paged ];
    }

    /**
     * Ejecuta el WP_Query y devuelve el payload listo para REST/AJAX.
     */
    public static function run( array $params ) {
        $built = self::build_query_args( $params );
        $q = new WP_Query( $built['args'] );

        $ids = wp_list_pluck( $q->posts, 'ID' );
        if ( ! empty( $ids ) ) {
            update_meta_cache( 'post', $ids );
            update_object_term_cache( $ids, 'attachment' );
        }
        return [
            'images'  => array_map( [ 'YZMF_Ajax', 'format_image' ], $q->posts ),
            'total'   => (int) $q->found_posts,
            'pages'   => (int) $q->max_num_pages,
            'current' => $built['paged'],
        ];
    }
}
