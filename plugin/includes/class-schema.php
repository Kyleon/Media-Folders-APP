<?php
/**
 * Schema.org JSON-LD enrichment para portfolios y attachments.
 *
 * Inyecta marcado estructurado rico en wp_head:
 *  - Singular portfolio: ImageGallery con array de ImageObject
 *  - Singular attachment: ImageObject + Photograph con geo, exif, keywords (AI tags) y color principal
 *
 * Aprovecha los meta del plugin:
 *   _yzmf_geo_lat, _yzmf_geo_lng, _yzmf_geo_place
 *   _yzmf_ai_tags
 *   _yzmf_color_palette
 *   _wp_attachment_metadata (EXIF nativo de WP)
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Schema {

    public static function init() {
        add_action( 'wp_head', [ __CLASS__, 'output' ], 5 );
    }

    public static function output() {
        if ( is_singular( 'portfolio' ) ) {
            self::print_jsonld( self::build_portfolio_schema( get_post() ) );
            return;
        }
        if ( is_singular( 'attachment' ) ) {
            self::print_jsonld( self::build_attachment_schema( get_post() ) );
            return;
        }
        if ( is_front_page() || is_home() ) {
            self::print_jsonld( self::build_person_schema() );
        }
    }

    /* ─────────── PORTFOLIO ─────────── */

    private static function build_portfolio_schema( $post ) {
        if ( ! $post ) return null;

        // Buscar la galería del portfolio (a través del bridge si existe)
        $image_ids = self::collect_portfolio_images( $post->ID );
        if ( empty( $image_ids ) ) {
            // Mínimo: usar la imagen destacada
            $thumb = (int) get_post_thumbnail_id( $post->ID );
            if ( $thumb ) $image_ids = [ $thumb ];
        }

        $images = [];
        foreach ( $image_ids as $id ) {
            $img = self::image_object( $id );
            if ( $img ) $images[] = $img;
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'ImageGallery',
            '@id'         => get_permalink( $post ) . '#gallery',
            'name'        => wp_strip_all_tags( get_the_title( $post ) ),
            'url'         => get_permalink( $post ),
            'datePublished' => get_the_date( 'c', $post ),
            'dateModified'  => get_the_modified_date( 'c', $post ),
            'author'      => self::author_ref(),
        ];

        $excerpt = wp_strip_all_tags( get_the_excerpt( $post ) );
        if ( $excerpt ) $schema['description'] = $excerpt;

        if ( $images ) {
            $schema['numberOfItems']   = count( $images );
            $schema['associatedMedia'] = $images;
        }

        // Categorías → keywords
        $cats = wp_get_post_terms( $post->ID, 'portfolio_category', [ 'fields' => 'names' ] );
        if ( ! is_wp_error( $cats ) && $cats ) $schema['keywords'] = implode( ', ', $cats );

        return $schema;
    }

    private static function collect_portfolio_images( $post_id ) {
        // Intenta usar el bridge: el plugin del tema guarda la galería en
        // distintas meta keys según el layout (st1..st4)
        $layout_meta_keys = [
            'rnr_portfolio_column_grid_gallery_images',
            'rnr_portfolio_column_fullwidth_gallery_images',
            'rnr_th_gallery_imge_st2',
            'rnr_th_gallery_imge_st4',
        ];
        $ids = [];
        foreach ( $layout_meta_keys as $key ) {
            $vals = get_post_meta( $post_id, $key );
            foreach ( $vals as $v ) {
                if ( is_array( $v ) ) {
                    foreach ( $v as $item ) {
                        if ( is_array( $item ) && ! empty( $item['ID'] ) ) $ids[] = (int) $item['ID'];
                        elseif ( is_numeric( $item ) ) $ids[] = (int) $item;
                    }
                } elseif ( is_numeric( $v ) ) {
                    $ids[] = (int) $v;
                }
            }
        }
        return array_values( array_unique( array_filter( $ids ) ) );
    }

    /* ─────────── ATTACHMENT ─────────── */

    private static function build_attachment_schema( $post ) {
        if ( ! $post ) return null;
        return self::image_object( $post->ID, true );
    }

    /**
     * Construye un ImageObject (Photograph cuando es atómico) con todos los
     * datos disponibles del attachment. Cuando $standalone es true incluye
     * @context y URL canónica.
     */
    private static function image_object( $att_id, $standalone = false ) {
        $url = wp_get_attachment_url( $att_id );
        if ( ! $url ) return null;

        $meta = wp_get_attachment_metadata( $att_id ) ?: [];
        $alt  = trim( (string) get_post_meta( $att_id, '_wp_attachment_image_alt', true ) );
        $cap  = wp_get_attachment_caption( $att_id );
        $title = get_the_title( $att_id );

        $obj = [
            '@type'        => 'ImageObject',
            'contentUrl'   => $url,
            'url'          => $url,
            'name'         => $title ?: ( $alt ?: '' ),
        ];
        if ( $standalone ) {
            $obj['@context'] = 'https://schema.org';
            $obj['@id']      = get_permalink( $att_id ) . '#image';
        }

        if ( $alt )  $obj['description']  = $alt;
        if ( $cap )  $obj['caption']      = $cap;

        // Dimensiones
        if ( ! empty( $meta['width'] ) )  $obj['width']  = (int) $meta['width'];
        if ( ! empty( $meta['height'] ) ) $obj['height'] = (int) $meta['height'];

        // Thumbnail
        $thumb = wp_get_attachment_image_src( $att_id, 'medium' );
        if ( $thumb ) $obj['thumbnailUrl'] = $thumb[0];

        // Fechas
        $obj['datePublished'] = get_the_date( 'c', $att_id );
        if ( ! empty( $meta['image_meta']['created_timestamp'] ) ) {
            $obj['dateCreated'] = gmdate( 'c', (int) $meta['image_meta']['created_timestamp'] );
        }

        // Autor
        $obj['author']    = self::author_ref();
        $obj['copyrightHolder'] = self::author_ref();

        // Geo
        $lat = get_post_meta( $att_id, '_yzmf_geo_lat', true );
        $lng = get_post_meta( $att_id, '_yzmf_geo_lng', true );
        if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
            $obj['contentLocation'] = [
                '@type' => 'Place',
                'name'  => (string) get_post_meta( $att_id, '_yzmf_geo_place', true ) ?: 'Photo location',
                'geo'   => [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => (float) $lat,
                    'longitude' => (float) $lng,
                ],
            ];
        }

        // Keywords (AI tags)
        $tags = get_post_meta( $att_id, '_yzmf_ai_tags', true );
        if ( is_array( $tags ) && $tags ) {
            $obj['keywords'] = implode( ', ', array_map( 'sanitize_text_field', $tags ) );
        }

        // Cámara / EXIF — Photograph extension
        $im = $meta['image_meta'] ?? [];
        $exif = [];
        if ( ! empty( $im['camera'] ) )       $exif['cameraModel']  = (string) $im['camera'];
        if ( ! empty( $im['focal_length'] ) ) $exif['focalLength']  = (string) $im['focal_length'] . 'mm';
        if ( ! empty( $im['aperture'] ) )     $exif['fNumber']      = 'f/' . $im['aperture'];
        if ( ! empty( $im['iso'] ) )          $exif['isoSpeedRatings'] = (int) $im['iso'];
        if ( ! empty( $im['shutter_speed'] ) ) $exif['exposureTime']  = (string) $im['shutter_speed'] . 's';
        if ( $exif ) $obj['exifData'] = $exif;

        // Color dominante
        $palette = get_post_meta( $att_id, '_yzmf_color_palette', true );
        if ( is_array( $palette ) && ! empty( $palette[0] ) ) {
            $obj['color'] = (string) $palette[0];
        }

        // Licencia (placeholder — se puede configurar)
        $license = get_option( 'yzmf_default_license' );
        if ( $license ) {
            $obj['license'] = $license;
            $obj['acquireLicensePage'] = home_url( '/contacto/' );
            $obj['creditText'] = self::author_name();
            $obj['copyrightNotice'] = '© ' . self::author_name() . ' ' . date( 'Y' );
        }

        return $obj;
    }

    /* ─────────── PERSON / AUTHOR ─────────── */

    private static function build_person_schema() {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            '@id'      => home_url( '/#person' ),
            'name'     => self::author_name(),
            'jobTitle' => 'Fotógrafo',
            'url'      => home_url( '/' ),
            'sameAs'   => array_filter( [
                get_option( 'yzmf_social_instagram' ),
                get_option( 'yzmf_social_facebook' ),
                get_option( 'yzmf_social_twitter' ),
                get_option( 'yzmf_social_500px' ),
            ] ),
        ];
    }

    private static function author_ref() {
        return [
            '@type' => 'Person',
            '@id'   => home_url( '/#person' ),
            'name'  => self::author_name(),
        ];
    }

    private static function author_name() {
        return (string) get_option( 'yzmf_author_name', get_bloginfo( 'name' ) );
    }

    /* ─────────── OUTPUT ─────────── */

    private static function print_jsonld( $schema ) {
        if ( ! $schema ) return;
        echo "\n<script type=\"application/ld+json\">\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo "\n</script>\n";
    }
}
