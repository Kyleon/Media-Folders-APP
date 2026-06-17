<?php
/**
 * Bridge entre YZ Media Folders y el sistema de portfolio del tema (kotlis-plugin).
 *
 * Funcionalidades:
 * - Fuerza show_in_rest en el CPT `portfolio` y la taxonomía `portfolio_category`
 *   para poder consumirlos vía REST sin tocar el plugin del tema.
 * - Endpoints custom yzmf/v1/portfolios* que mapean al formato del tema (galerías
 *   por layout st1-st4 en sus meta keys correspondientes).
 * - Endpoint para vincular una carpeta YZMF como galería del portfolio.
 *
 * Meta keys del portfolio (mapeadas por el agente Explorador):
 *   - rnr_wr_port_dt_opt  → layout (st1|st2|st3|st4)
 *   - st1: rnr_portfolio_column_grid_gallery_images
 *   - st2: rnr_th_gallery_imge_st2
 *   - st3: rnr_portfolio_column_fullwidth_gallery_images
 *   - st4: rnr_th_gallery_imge_st4
 *   - _thumbnail_id (imagen destacada)
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Portfolio_Bridge {

    const NS    = 'yzmf/v1';
    const CPT   = 'portfolio';
    const TAX   = 'portfolio_category';
    const META_LINKED_FOLDER   = '_yzmf_linked_folder';
    const META_LINKED_LOCATION = '_yzmf_location_id';

    /** Mapa layout → meta key de galería. */
    public static function gallery_meta_key( $layout ) {
        switch ( $layout ) {
            case 'st2': return 'rnr_th_gallery_imge_st2';
            case 'st3': return 'rnr_portfolio_column_fullwidth_gallery_images';
            case 'st4': return 'rnr_th_gallery_imge_st4';
            case 'st1':
            default:    return 'rnr_portfolio_column_grid_gallery_images';
        }
    }

    /**
     * Escribe una galería respetando el formato de Meta Box `image_advanced`:
     * una fila en wp_postmeta por cada attachment id (no un array serializado).
     * Es lo que el tema kotlis lee con `rwmb_meta()` / `get_post_meta(..., false)`.
     */
    private static function write_gallery_meta( $post_id, $meta_key, $ids ) {
        // Borrar todas las filas previas (sea cual sea el formato anterior)
        delete_post_meta( $post_id, $meta_key );
        foreach ( $ids as $att_id ) {
            $att_id = intval( $att_id );
            if ( $att_id > 0 ) {
                add_post_meta( $post_id, $meta_key, $att_id, false );
            }
        }
    }

    /**
     * Lee la galería siendo tolerante con ambos formatos:
     * - Multi-row (Meta Box image_advanced) → `get_post_meta(..., false)` devuelve array de IDs.
     * - Single serialized (legacy) → `get_post_meta(..., true)` devuelve un array dentro de array.
     */
    private static function read_gallery_meta( $post_id, $meta_key ) {
        $rows = get_post_meta( $post_id, $meta_key, false );
        if ( ! is_array( $rows ) ) return [];

        // Caso legacy: una sola fila que ya contiene un array
        if ( count( $rows ) === 1 && is_array( $rows[0] ) ) {
            $rows = $rows[0];
        }

        $out = [];
        foreach ( $rows as $v ) {
            // Cada fila puede ser un id, un array (legacy con clones) o un string
            if ( is_array( $v ) ) {
                foreach ( $v as $vv ) {
                    $i = intval( $vv );
                    if ( $i > 0 ) $out[] = $i;
                }
            } else {
                $i = intval( $v );
                if ( $i > 0 ) $out[] = $i;
            }
        }
        return array_values( array_unique( $out ) );
    }

    public static function init() {
        // Habilitar REST para el CPT portfolio y su taxonomía sin tocar kotlis-plugin
        add_filter( 'register_post_type_args', [ __CLASS__, 'enable_rest_for_portfolio' ], 10, 2 );
        add_filter( 'register_taxonomy_args',  [ __CLASS__, 'enable_rest_for_portfolio_tax' ], 10, 2 );

        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function enable_rest_for_portfolio( $args, $post_type ) {
        if ( $post_type === self::CPT ) {
            $args['show_in_rest']          = true;
            $args['rest_base']             = 'portfolio';
            $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
        }
        return $args;
    }

    public static function enable_rest_for_portfolio_tax( $args, $taxonomy ) {
        if ( $taxonomy === self::TAX ) {
            $args['show_in_rest']          = true;
            $args['rest_base']             = 'portfolio_category';
            $args['rest_controller_class'] = 'WP_REST_Terms_Controller';
        }
        return $args;
    }

    /* ─────────── ROUTES ─────────── */

    public static function register_routes() {
        register_rest_route( self::NS, '/portfolios', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_portfolios' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_portfolio' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/portfolios/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_portfolio' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_portfolio' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_portfolio' ],
                'permission_callback' => [ 'YZMF_REST', 'can_delete' ],
            ],
        ] );

        register_rest_route( self::NS, '/portfolios/(?P<id>\d+)/gallery', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_portfolio_gallery' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'set_portfolio_gallery' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/portfolios/(?P<id>\d+)/sync-folder', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'sync_folder_to_gallery' ],
            'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/portfolios/(?P<id>\d+)/duplicate', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'duplicate_portfolio' ],
            'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/portfolios/geo/all', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_portfolios_with_geo' ],
            'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
        ] );

        register_rest_route( self::NS, '/portfolios/(?P<id>\d+)/meta', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'get_portfolio_meta' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'set_portfolio_meta' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/portfolio-categories', [
            [
                'methods'             => 'GET',
                'callback'            => [ __CLASS__, 'list_categories' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ __CLASS__, 'create_category' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );

        register_rest_route( self::NS, '/portfolio-categories/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ __CLASS__, 'update_category' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ __CLASS__, 'delete_category' ],
                'permission_callback' => [ 'YZMF_REST', 'can_upload' ],
            ],
        ] );
    }

    /* ─────────── PORTFOLIOS ─────────── */

    public static function list_portfolios( WP_REST_Request $req ) {
        $paged   = max( 1, intval( $req->get_param( 'page' ) ?: 1 ) );
        $perpage = min( 100, max( 1, intval( $req->get_param( 'per_page' ) ?: 20 ) ) );
        $search  = sanitize_text_field( $req->get_param( 'search' ) ?: '' );
        $status  = sanitize_key( $req->get_param( 'status' ) ?: 'any' );
        $cat     = intval( $req->get_param( 'category' ) ?: 0 );

        $args = [
            'post_type'      => self::CPT,
            'post_status'    => $status === 'any' ? [ 'publish', 'draft', 'pending', 'private' ] : $status,
            'posts_per_page' => $perpage,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if ( $search ) $args['s'] = $search;
        if ( $cat ) {
            $args['tax_query'] = [ [ 'taxonomy' => self::TAX, 'field' => 'term_id', 'terms' => $cat ] ];
        }

        $q = new WP_Query( $args );
        return rest_ensure_response( [
            'items' => array_map( [ __CLASS__, 'format_portfolio' ], $q->posts ),
            'total' => $q->found_posts,
            'pages' => (int) $q->max_num_pages,
            'page'  => $paged,
        ] );
    }

    public static function get_portfolio( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        $p  = get_post( $id );
        if ( ! $p || $p->post_type !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        return rest_ensure_response( self::format_portfolio( $p, true ) );
    }

    public static function create_portfolio( WP_REST_Request $req ) {
        $title = sanitize_text_field( $req->get_param( 'title' ) ?: '' );
        if ( ! $title ) return new WP_Error( 'yzmf_missing_title', 'Título requerido', [ 'status' => 400 ] );

        $id = wp_insert_post( [
            'post_type'    => self::CPT,
            'post_title'   => $title,
            'post_content' => wp_kses_post( $req->get_param( 'content' ) ?: '' ),
            'post_excerpt' => sanitize_textarea_field( $req->get_param( 'excerpt' ) ?: '' ),
            'post_status'  => sanitize_key( $req->get_param( 'status' ) ?: 'draft' ),
        ], true );
        if ( is_wp_error( $id ) ) return $id;

        self::apply_portfolio_fields( $id, $req );
        return rest_ensure_response( self::format_portfolio( get_post( $id ), true ) );
    }

    public static function update_portfolio( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        $p  = get_post( $id );
        if ( ! $p || $p->post_type !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }

        $update = [ 'ID' => $id ];
        if ( $req->get_param( 'title' )   !== null ) $update['post_title']   = sanitize_text_field( $req->get_param( 'title' ) );
        if ( $req->get_param( 'content' ) !== null ) $update['post_content'] = wp_kses_post( $req->get_param( 'content' ) );
        if ( $req->get_param( 'excerpt' ) !== null ) $update['post_excerpt'] = sanitize_textarea_field( $req->get_param( 'excerpt' ) );
        if ( $req->get_param( 'status' )  !== null ) $update['post_status']  = sanitize_key( $req->get_param( 'status' ) );
        if ( count( $update ) > 1 ) wp_update_post( $update );

        self::apply_portfolio_fields( $id, $req );
        return rest_ensure_response( self::format_portfolio( get_post( $id ), true ) );
    }

    public static function delete_portfolio( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        $p  = get_post( $id );
        if ( ! $p || $p->post_type !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        $force = (bool) $req->get_param( 'force' );
        wp_delete_post( $id, $force );
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id, 'force' => $force ] );
    }

    private static function apply_portfolio_fields( $id, WP_REST_Request $req ) {
        // Layout
        if ( $req->get_param( 'layout' ) !== null ) {
            $layout = sanitize_key( $req->get_param( 'layout' ) );
            if ( in_array( $layout, [ 'st1', 'st2', 'st3', 'st4' ], true ) ) {
                update_post_meta( $id, 'rnr_wr_port_dt_opt', $layout );
            }
        }
        // Imagen destacada
        $hero = $req->get_param( 'hero_id' );
        if ( $hero !== null ) {
            $hero = intval( $hero );
            if ( $hero > 0 && get_post_type( $hero ) === 'attachment' ) set_post_thumbnail( $id, $hero );
            elseif ( $hero === 0 ) delete_post_thumbnail( $id );
        }
        // Categorías
        $cats = $req->get_param( 'categories' );
        if ( is_array( $cats ) ) {
            wp_set_object_terms( $id, array_map( 'intval', $cats ), self::TAX, false );
        }
        // Galería (si viene, escribirla en el meta del layout actual)
        $gallery = $req->get_param( 'gallery' );
        if ( is_array( $gallery ) ) {
            $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
            $key    = self::gallery_meta_key( $layout );
            self::write_gallery_meta( $id, $key, $gallery );
        }
        // Linked folder (relación YZMF)
        if ( $req->get_param( 'linked_folder' ) !== null ) {
            $f = intval( $req->get_param( 'linked_folder' ) );
            if ( $f > 0 ) update_post_meta( $id, self::META_LINKED_FOLDER, $f );
            else          delete_post_meta( $id, self::META_LINKED_FOLDER );
        }
        // Ubicación vinculada (mapa). 0/'' la quita; valida que sea un yzmf_location existente.
        if ( $req->get_param( 'location_id' ) !== null ) {
            $loc = intval( $req->get_param( 'location_id' ) );
            if ( $loc > 0 && class_exists( 'YZMF_Map' ) && get_post_type( $loc ) === YZMF_Map::CPT ) {
                update_post_meta( $id, self::META_LINKED_LOCATION, $loc );
            } else {
                delete_post_meta( $id, self::META_LINKED_LOCATION );
            }
            if ( class_exists( 'YZMF_Map' ) ) YZMF_Map::invalidate_caches();
        }
    }

    /* ─────────── GALLERY ─────────── */

    public static function get_portfolio_gallery( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $key    = self::gallery_meta_key( $layout );
        $ids    = self::read_gallery_meta( $id, $key );

        $items = [];
        foreach ( $ids as $aid ) {
            $items[] = [
                'id'        => $aid,
                'thumb'     => wp_get_attachment_image_url( $aid, 'thumbnail' ),
                'medium'    => wp_get_attachment_image_url( $aid, 'medium_large' ),
                'url'       => wp_get_attachment_url( $aid ),
                'alt'       => get_post_meta( $aid, '_wp_attachment_image_alt', true ),
                'caption'   => get_post_field( 'post_excerpt', $aid ),
                'title'     => get_the_title( $aid ),
            ];
        }

        return rest_ensure_response( [
            'portfolio_id' => $id,
            'layout'       => $layout,
            'meta_key'     => $key,
            'gallery'      => $items,
        ] );
    }

    public static function set_portfolio_gallery( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        $ids = (array) ( $req->get_param( 'gallery' ) ?: [] );
        $ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

        $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $key    = self::gallery_meta_key( $layout );
        self::write_gallery_meta( $id, $key, $ids );

        return self::get_portfolio_gallery( $req );
    }

    /**
     * Sincroniza una carpeta YZMF como galería del portfolio.
     * Sustituye la galería actual por las imágenes de la carpeta.
     */
    public static function sync_folder_to_gallery( WP_REST_Request $req ) {
        $id        = intval( $req['id'] );
        $folder_id = intval( $req->get_param( 'folder_id' ) );
        $order     = sanitize_key( $req->get_param( 'orderby' ) ?: 'date' );
        $direction = strtoupper( $req->get_param( 'order' ) ?: 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

        if ( get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        if ( ! $folder_id || ! get_term( $folder_id, YZMF_TAXONOMY ) ) {
            return new WP_Error( 'yzmf_invalid_folder', 'Carpeta YZMF inexistente', [ 'status' => 400 ] );
        }

        $allowed_order = [ 'date', 'title', 'menu_order' ];
        if ( ! in_array( $order, $allowed_order, true ) ) $order = 'date';

        // Cap defensivo a 1000 imágenes por galería. Para portfolios con más,
        // hay que usar paginación de visualización en el theme — no tiene
        // sentido cargar 5000 imágenes en una sola página.
        $atts = get_posts( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1000,
            'orderby'        => $order,
            'order'          => $direction,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            'tax_query'      => [ [
                'taxonomy'         => YZMF_TAXONOMY,
                'field'            => 'term_id',
                'terms'            => $folder_id,
                'include_children' => true,
            ] ],
        ] );

        $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $key    = self::gallery_meta_key( $layout );
        self::write_gallery_meta( $id, $key, $atts );
        update_post_meta( $id, self::META_LINKED_FOLDER, $folder_id );

        return rest_ensure_response( [
            'portfolio_id' => $id,
            'folder_id'    => $folder_id,
            'layout'       => $layout,
            'meta_key'     => $key,
            'count'        => count( $atts ),
            'gallery'      => array_map( 'intval', $atts ),
        ] );
    }

    /* ─────────── GEO (mapa) ─────────── */

    /**
     * Devuelve todos los portfolios asociados a una ubicación, con coordenadas
     * resueltas desde la propia ubicación. Para pintar en el mapa.
     * Estructura por item: { id, title, hero_url, location_id, location_name, lat, lng, permalink, status }
     */
    public static function list_portfolios_with_geo( WP_REST_Request $req ) {
        // Cache 15min — se invalida desde set_portfolios_for_location y desde
        // los hooks de save_location (delete_transient('yzmf_map_public_data')
        // ahora también limpia este transient: ver maybe_invalidate_portfolio_geo()).
        $cache_key = 'yzmf_portfolio_geo_list';
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) return rest_ensure_response( $cached );

        $q = new WP_Query( [
            'post_type'      => self::CPT,
            'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
            'posts_per_page' => 500, // cap defensivo; >500 portfolios = case excepcional
            'fields'         => 'ids',
            'meta_query'     => [ [ 'key' => self::META_LINKED_LOCATION, 'compare' => 'EXISTS' ] ],
            'no_found_rows'  => true,
        ] );

        if ( empty( $q->posts ) ) {
            set_transient( $cache_key, [], 15 * MINUTE_IN_SECONDS );
            return rest_ensure_response( [] );
        }

        update_meta_cache( 'post', $q->posts );

        // Cache de coords por location_id para no rehacer el get_post_meta por cada portfolio
        $coord_cache = [];

        $out = [];
        foreach ( $q->posts as $pid ) {
            $loc = (int) get_post_meta( $pid, self::META_LINKED_LOCATION, true );
            if ( ! $loc ) continue;
            if ( ! isset( $coord_cache[ $loc ] ) ) {
                if ( get_post_type( $loc ) !== ( class_exists( 'YZMF_Map' ) ? YZMF_Map::CPT : 'yzmf_location' ) ) {
                    $coord_cache[ $loc ] = false;
                } else {
                    $lat = get_post_meta( $loc, '_yzmf_lat', true );
                    $lng = get_post_meta( $loc, '_yzmf_lng', true );
                    $coord_cache[ $loc ] = ( $lat === '' || $lng === '' ) ? false : [
                        'lat'  => (float) $lat,
                        'lng'  => (float) $lng,
                        'name' => get_the_title( $loc ),
                    ];
                }
            }
            $c = $coord_cache[ $loc ];
            if ( ! $c ) continue;

            $hero_id = (int) get_post_thumbnail_id( $pid );
            $thumb_id = $hero_id;
            if ( ! $thumb_id ) {
                $layout = get_post_meta( $pid, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
                $gal    = self::read_gallery_meta( $pid, self::gallery_meta_key( $layout ) );
                $thumb_id = $gal[0] ?? 0;
            }

            $out[] = [
                'id'            => $pid,
                'title'         => get_the_title( $pid ),
                'permalink'     => get_permalink( $pid ),
                'status'        => get_post_status( $pid ),
                'lat'           => $c['lat'],
                'lng'           => $c['lng'],
                'location_id'   => $loc,
                'location_name' => $c['name'],
                'hero_url'      => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '',
                'thumb'         => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : '',
            ];
        }
        set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
        return rest_ensure_response( $out );
    }

    /**
     * Limpia los transients relacionados con geo de portfolios.
     * Llamado por save_location / delete_location y por set_portfolios_for_location.
     */
    public static function invalidate_geo_cache() {
        if ( class_exists( 'YZMF_Map' ) ) {
            YZMF_Map::invalidate_caches();
        } else {
            delete_transient( 'yzmf_portfolio_geo_list' );
            delete_transient( 'yzmf_map_public_data' );
        }
    }

    /**
     * Lista los IDs de portfolios vinculados a una ubicación dada.
     * Lo usa YZMF_Map para exponer la relación viceversa.
     */
    public static function get_portfolios_by_location( $location_id ) {
        $location_id = intval( $location_id );
        if ( ! $location_id ) return [];
        $q = new WP_Query( [
            'post_type'      => self::CPT,
            'post_status'    => [ 'publish', 'draft', 'private', 'pending' ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [ [
                'key'   => self::META_LINKED_LOCATION,
                'value' => $location_id,
                'type'  => 'NUMERIC',
            ] ],
            'no_found_rows'  => true,
        ] );
        return array_map( 'intval', $q->posts );
    }

    /**
     * Vincula/desvincula una lista de portfolios a una ubicación.
     * - Cada portfolio en $ids quedará apuntando a $location_id (intval).
     * - Los que estaban previamente vinculados a esta ubicación pero ya no
     *   aparecen en $ids, quedan desvinculados (delete del meta).
     */
    public static function set_portfolios_for_location( $location_id, $ids ) {
        $location_id = intval( $location_id );
        if ( ! $location_id ) return;
        $new = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
        $current = self::get_portfolios_by_location( $location_id );

        $changed = false;
        foreach ( array_diff( $current, $new ) as $pid ) {
            delete_post_meta( $pid, self::META_LINKED_LOCATION );
            $changed = true;
        }
        foreach ( $new as $pid ) {
            if ( get_post_type( $pid ) === self::CPT ) {
                update_post_meta( $pid, self::META_LINKED_LOCATION, $location_id );
                $changed = true;
            }
        }
        if ( $changed ) self::invalidate_geo_cache();
    }

    /* ─────────── CATEGORIES ─────────── */

    public static function list_categories( WP_REST_Request $req ) {
        $terms = get_terms( [
            'taxonomy'   => self::TAX,
            'hide_empty' => false,
            'orderby'    => 'name',
        ] );
        if ( is_wp_error( $terms ) ) return $terms;
        $out = [];
        foreach ( $terms as $t ) {
            $out[] = [
                'id'     => $t->term_id,
                'name'   => $t->name,
                'slug'   => $t->slug,
                'parent' => $t->parent,
                'count'  => (int) $t->count,
            ];
        }
        return rest_ensure_response( $out );
    }

    public static function create_category( WP_REST_Request $req ) {
        $name   = sanitize_text_field( $req->get_param( 'name' ) ?: '' );
        $parent = intval( $req->get_param( 'parent' ) ?: 0 );
        if ( ! $name ) return new WP_Error( 'yzmf_missing_name', 'Nombre requerido', [ 'status' => 400 ] );
        $r = wp_insert_term( $name, self::TAX, [ 'parent' => $parent ] );
        if ( is_wp_error( $r ) ) return $r;
        $t = get_term( $r['term_id'], self::TAX );
        return rest_ensure_response( [
            'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'parent' => $t->parent, 'count' => 0,
        ] );
    }

    public static function update_category( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );
        $args = [];
        if ( $req->get_param( 'name' )   !== null ) $args['name']   = sanitize_text_field( $req->get_param( 'name' ) );
        if ( $req->get_param( 'parent' ) !== null ) $args['parent'] = intval( $req->get_param( 'parent' ) );
        $r = wp_update_term( $id, self::TAX, $args );
        if ( is_wp_error( $r ) ) return $r;
        return rest_ensure_response( [ 'id' => $id ] );
    }

    public static function delete_category( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( ! $id ) return new WP_Error( 'yzmf_invalid', 'ID inválido', [ 'status' => 400 ] );
        $r = wp_delete_term( $id, self::TAX );
        if ( is_wp_error( $r ) ) return $r;
        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    /* ─────────── FORMATTING ─────────── */

    public static function format_portfolio( $p, $detailed = false ) {
        $hero_id  = (int) get_post_thumbnail_id( $p->ID );
        $layout   = get_post_meta( $p->ID, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $linked   = (int) get_post_meta( $p->ID, self::META_LINKED_FOLDER, true );
        $loc_id   = (int) get_post_meta( $p->ID, self::META_LINKED_LOCATION, true );

        $cats = wp_get_object_terms( $p->ID, self::TAX, [ 'fields' => 'all' ] );
        $cats_arr = is_wp_error( $cats ) ? [] : array_map( function( $t ) {
            return [ 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug ];
        }, $cats );

        // Galería del layout actual (necesaria para el detalle y para fallback de hero)
        $key         = self::gallery_meta_key( $layout );
        $gallery_ids = self::read_gallery_meta( $p->ID, $key );

        // Fallback de imagen: si no hay imagen destacada, usar la primera de la galería
        $thumb_id = $hero_id ?: ( $gallery_ids[0] ?? 0 );

        $out = [
            'id'           => $p->ID,
            'title'        => $p->post_title,
            'slug'         => $p->post_name,
            'status'       => $p->post_status,
            'date'         => mysql2date( 'c', $p->post_date ),
            'excerpt'      => $p->post_excerpt,
            'permalink'    => get_permalink( $p->ID ),
            'edit_url'     => get_edit_post_link( $p->ID, 'raw' ),
            'layout'       => $layout,
            'hero_id'      => $hero_id,
            'hero_url'     => $thumb_id ? wp_get_attachment_url( $thumb_id ) : '',
            'thumb_source' => $hero_id ? 'featured' : ( $thumb_id ? 'gallery' : 'none' ),
            'categories'   => $cats_arr,
            'linked_folder' => $linked ?: null,
            'location_id'   => $loc_id ?: null,
        ];

        if ( $detailed ) {
            $out['content']  = $p->post_content;
            $out['meta_key'] = $key;
            $out['gallery']  = $gallery_ids;
        }

        return $out;
    }

    /* ─────────── META AVANZADA (campos del tema kotlis) ─────────── */

    /**
     * Devuelve el schema de meta editables para un layout.
     * El frontend usa este schema para construir la UI dinámicamente.
     * Por ahora solo definimos el más rico (st1). Para st2-st4 podemos ampliar después.
     */
    public static function meta_schema( $layout ) {
        // Layouts que comparten el schema más rico (Column Grid Sidebar):
        //  - vacío / no definido
        //  - st0 (Select — el default antes de elegir layout)
        //  - st1 (Column Grid Sidebar)
        if ( ! $layout || in_array( $layout, [ 'st0', 'st1' ], true ) ) {
            return [
                // ── Sidebar ─────────────────────────
                [ 'section' => 'sidebar', 'section_label' => 'Sidebar', 'key' => 'rnr_portfolio_column_grid_details_sidebar_image',    'type' => 'image',    'multi' => false, 'label' => 'Imagen de fondo del sidebar' ],
                [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_grid_details_sidebar_title',    'type' => 'textarea', 'label' => 'Título del sidebar', 'rows' => 2 ],
                [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_grid_details_sidebar_subtitle', 'type' => 'textarea', 'label' => 'Subtítulo del sidebar', 'rows' => 3 ],
                [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_grid_details_scroll_swipe',    'type' => 'toggle',   'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar "scroll down"' ],
                [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_grid_details_translet_scroll', 'type' => 'text',     'label' => 'Texto del scroll down', 'placeholder' => 'Ej: Scroll' ],

                // ── Galería: cabecera ─────────────────
                [ 'section' => 'gallery', 'section_label' => 'Galería',  'key' => 'rnr_portfolio_column_grid_gallery_title_section', 'type' => 'toggle',   'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar título de galería' ],
                [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_grid_gallery_title',          'type' => 'text',     'label' => 'Título de galería' ],
                [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_grid_gallery_subtitle',       'type' => 'textarea', 'label' => 'Subtítulo de galería', 'rows' => 2 ],
                [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_grid_gallery_number',         'type' => 'text',     'label' => 'Número de sección', 'placeholder' => 'Ej: 01.' ],
                [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_grid_gallery_images_section', 'type' => 'toggle',   'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar las imágenes de la galería' ],

                // ── Contenido: cabecera ───────────────
                [ 'section' => 'content', 'section_label' => 'Sección de contenido', 'key' => 'rnr_portfolio_column_grid_content_title_section', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar título de contenido' ],
                [ 'section' => 'content', 'key' => 'rnr_portfolio_column_grid_content_title',          'type' => 'text',     'label' => 'Título de contenido' ],
                [ 'section' => 'content', 'key' => 'rnr_portfolio_column_grid_content_subtitle',       'type' => 'textarea', 'label' => 'Subtítulo', 'rows' => 2 ],
                [ 'section' => 'content', 'key' => 'rnr_portfolio_column_grid_content_number',        'type' => 'text',     'label' => 'Número de sección' ],

                // ── Project Information ───────────────
                [ 'section' => 'info', 'section_label' => 'Información del proyecto', 'key' => 'rnr_portfolio_column_grid_project_info', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar información del proyecto' ],
                [ 'section' => 'info', 'key' => 'rnr_portfolio_column_grid_project_info_main', 'type' => 'repeater', 'label' => 'Datos del proyecto', 'item_label' => 'Dato',
                    'fields' => [
                        [ 'key' => 'rnr_port_column_grid_dt_in_title',              'type' => 'text', 'label' => 'Etiqueta',       'placeholder' => 'Ej: Location' ],
                        [ 'key' => 'rnr_port_column_grid_dt_in_subtitle',           'type' => 'text', 'label' => 'Valor',          'placeholder' => 'Ej: NY, USA' ],
                        [ 'key' => 'rnr_port_column_grid_dt_in_subtitle_url',       'type' => 'url',  'label' => 'URL (opcional)' ],
                        [ 'key' => 'rnr_port_column_grid_dt_in_subtitle_url_target','type' => 'select', 'label' => 'Abrir en', 'default' => '_self',
                            'options' => [
                                [ 'value' => '_self',  'label' => 'Misma pestaña' ],
                                [ 'value' => '_blank', 'label' => 'Nueva pestaña' ],
                            ],
                        ],
                    ],
                ],

                // ── Botón ─────────────────────────────
                [ 'section' => 'button', 'section_label' => 'Botón del proyecto', 'key' => 'rnr_portfolio_column_grid_details_info_button_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar botón' ],
                [ 'section' => 'button', 'key' => 'rnr_port_column_grid_dt_in_button_text',        'type' => 'text', 'label' => 'Texto del botón', 'placeholder' => 'Ex: View Project' ],
                [ 'section' => 'button', 'key' => 'rnr_port_column_grid_dt_in_button_url',         'type' => 'url',  'label' => 'URL del botón' ],
                [ 'section' => 'button', 'key' => 'rnr_port_column_grid_dt_in_button_link_target', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Abrir en nueva pestaña' ],

                // ── Navegación ────────────────────────
                [ 'section' => 'navigation', 'section_label' => 'Navegación', 'key' => 'rnr_portfolio_column_grid_project_prev_next', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar Prev / Next post' ],

                // ── Globales ──────────────────────────
                [ 'section' => 'global', 'section_label' => 'Globales', 'key' => 'rnr_video_portpost_vid_url',        'type' => 'url',    'label' => 'URL Video Popup (Youtube/Vimeo)' ],
                [ 'section' => 'global', 'key' => 'rnr_port_carousel_info_description_opt', 'type' => 'toggle', 'on' => 'st2', 'off' => 'st1', 'default' => 'st1', 'label' => 'Mostrar título y caption en imágenes' ],
            ];
        }

        if ( $layout === 'st2' ) return self::schema_st2();
        if ( $layout === 'st3' ) return self::schema_st3();
        if ( $layout === 'st4' ) return self::schema_st4();

        return [];
    }

    /** Schema para st2 — Carousel */
    private static function schema_st2() {
        return [
            // ── Thumbnails ────────────────────────
            [ 'section' => 'thumbnails', 'section_label' => 'Thumbnails', 'key' => 'rnr_portfolio_carousel_details_thumb',       'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar thumbnails' ],
            [ 'section' => 'thumbnails', 'key' => 'rnr_portfolio_carousel_details_thumb_title', 'type' => 'text', 'label' => 'Texto del botón thumbnails', 'placeholder' => 'Ej: Mostrar todas' ],

            // ── Details info ──────────────────────
            [ 'section' => 'details_info', 'section_label' => 'Detalles del proyecto', 'key' => 'rnr_portfolio_carousel_details_view',       'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar info de detalles' ],
            [ 'section' => 'details_info', 'key' => 'rnr_portfolio_carousel_details_view_title', 'type' => 'text', 'label' => 'Texto "View Details"', 'placeholder' => 'Ej: Mostrar detalles' ],

            // ── Title de la sección de info ───────
            [ 'section' => 'project_title', 'section_label' => 'Título de la información', 'key' => 'rnr_portfolio_carousel_details_info_title_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar título' ],
            [ 'section' => 'project_title', 'key' => 'rnr_portfolio_carousel_details_info_title', 'type' => 'text', 'label' => 'Texto del título', 'placeholder' => 'Ej: Detalles' ],

            // ── Project information (repeater) ────
            [ 'section' => 'info', 'section_label' => 'Información del proyecto', 'key' => 'rnr_portfolio_carousel_project_info', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar información del proyecto' ],
            [ 'section' => 'info', 'key' => 'rnr_portfolio_carousel_project_info_main', 'type' => 'repeater', 'label' => 'Datos del proyecto', 'item_label' => 'Dato',
                'fields' => [
                    [ 'key' => 'rnr_port_car_dt_in_title',              'type' => 'text', 'label' => 'Etiqueta',       'placeholder' => 'Ej: Location' ],
                    [ 'key' => 'rnr_port_car_dt_in_subtitle',           'type' => 'text', 'label' => 'Valor',          'placeholder' => 'Ej: NY, USA' ],
                    [ 'key' => 'rnr_port_car_dt_in_subtitle_url',       'type' => 'url',  'label' => 'URL (opcional)' ],
                    [ 'key' => 'rnr_port_car_dt_in_subtitle_url_target','type' => 'select', 'label' => 'Abrir en', 'default' => '_self',
                        'options' => [
                            [ 'value' => '_self',  'label' => 'Misma pestaña' ],
                            [ 'value' => '_blank', 'label' => 'Nueva pestaña' ],
                        ],
                    ],
                ],
            ],

            // ── Botón ─────────────────────────────
            [ 'section' => 'button', 'section_label' => 'Botón del proyecto', 'key' => 'rnr_portfolio_carousel_details_info_button_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_car_dt_in_button_text',        'type' => 'text', 'label' => 'Texto del botón', 'placeholder' => 'Ex: View Project' ],
            [ 'section' => 'button', 'key' => 'rnr_port_car_dt_in_button_url',         'type' => 'url',  'label' => 'URL del botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_car_dt_in_button_link_target', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Abrir en nueva pestaña' ],

            // ── Navegación ────────────────────────
            [ 'section' => 'navigation', 'section_label' => 'Navegación', 'key' => 'rnr_portfolio_carousel_project_prev_next', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar Prev / Next post' ],

            // ── Globales ──────────────────────────
            [ 'section' => 'global', 'section_label' => 'Globales', 'key' => 'rnr_video_portpost_vid_url', 'type' => 'url', 'label' => 'URL Video Popup (Youtube/Vimeo)' ],
            [ 'section' => 'global', 'key' => 'rnr_port_carousel_info_description_opt', 'type' => 'toggle', 'on' => 'st2', 'off' => 'st1', 'default' => 'st1', 'label' => 'Mostrar título y caption en imágenes' ],
        ];
    }

    /** Schema para st3 — Column Full Width */
    private static function schema_st3() {
        return [
            // ── Sidebar ───────────────────────────
            [ 'section' => 'sidebar', 'section_label' => 'Sidebar', 'key' => 'rnr_portfolio_column_fullwidth_details_sidebar_image',    'type' => 'image', 'multi' => false, 'label' => 'Imagen de fondo del sidebar' ],
            [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_fullwidth_details_sidebar_title',    'type' => 'textarea', 'label' => 'Título del sidebar', 'rows' => 2 ],
            [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_fullwidth_details_sidebar_subtitle', 'type' => 'textarea', 'label' => 'Subtítulo del sidebar', 'rows' => 3 ],
            [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_fullwidth_details_scroll_swipe',     'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar "scroll down"' ],
            [ 'section' => 'sidebar', 'key' => 'rnr_portfolio_column_fullwidth_details_translet_scroll', 'type' => 'text', 'label' => 'Texto del scroll down' ],

            // ── Galería ───────────────────────────
            [ 'section' => 'gallery', 'section_label' => 'Galería',  'key' => 'rnr_portfolio_column_fullwidth_gallery_title_section', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar título de galería' ],
            [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_fullwidth_gallery_title',          'type' => 'text',     'label' => 'Título de galería' ],
            [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_fullwidth_gallery_subtitle',       'type' => 'textarea', 'label' => 'Subtítulo de galería', 'rows' => 2 ],
            [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_fullwidth_gallery_number',         'type' => 'text',     'label' => 'Número de sección' ],
            [ 'section' => 'gallery', 'key' => 'rnr_portfolio_column_fullwidth_gallery_images_section', 'type' => 'toggle',   'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar las imágenes de la galería' ],

            // ── Contenido ─────────────────────────
            [ 'section' => 'content', 'section_label' => 'Sección de contenido', 'key' => 'rnr_portfolio_column_fullwidth_content_title_section', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar título de contenido' ],
            [ 'section' => 'content', 'key' => 'rnr_portfolio_column_fullwidth_content_title',          'type' => 'text',     'label' => 'Título de contenido' ],
            [ 'section' => 'content', 'key' => 'rnr_portfolio_column_fullwidth_content_subtitle',       'type' => 'textarea', 'label' => 'Subtítulo', 'rows' => 2 ],
            [ 'section' => 'content', 'key' => 'rnr_portfolio_column_fullwidth_content_number',         'type' => 'text',     'label' => 'Número de sección' ],

            // ── Project information ───────────────
            [ 'section' => 'info', 'section_label' => 'Información del proyecto', 'key' => 'rnr_portfolio_column_fullwidth_project_info', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar información del proyecto' ],
            [ 'section' => 'info', 'key' => 'rnr_portfolio_column_fullwidth_project_info_main', 'type' => 'repeater', 'label' => 'Datos del proyecto', 'item_label' => 'Dato',
                'fields' => [
                    [ 'key' => 'rnr_port_column_fullwidth_dt_in_title',              'type' => 'text', 'label' => 'Etiqueta',       'placeholder' => 'Ej: Location' ],
                    [ 'key' => 'rnr_port_column_fullwidth_dt_in_subtitle',           'type' => 'text', 'label' => 'Valor',          'placeholder' => 'Ej: NY, USA' ],
                    [ 'key' => 'rnr_port_column_fullwidth_dt_in_subtitle_url',       'type' => 'url',  'label' => 'URL (opcional)' ],
                    [ 'key' => 'rnr_port_column_fullwidth_dt_in_subtitle_url_target','type' => 'select', 'label' => 'Abrir en', 'default' => '_self',
                        'options' => [
                            [ 'value' => '_self',  'label' => 'Misma pestaña' ],
                            [ 'value' => '_blank', 'label' => 'Nueva pestaña' ],
                        ],
                    ],
                ],
            ],

            // ── Botón ─────────────────────────────
            [ 'section' => 'button', 'section_label' => 'Botón del proyecto', 'key' => 'rnr_portfolio_column_fullwidth_details_info_button_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_column_fullwidth_dt_in_button_text',        'type' => 'text', 'label' => 'Texto del botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_column_fullwidth_dt_in_button_url',         'type' => 'url',  'label' => 'URL del botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_column_fullwidth_dt_in_button_link_target', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Abrir en nueva pestaña' ],

            // ── Navegación ────────────────────────
            [ 'section' => 'navigation', 'section_label' => 'Navegación', 'key' => 'rnr_portfolio_column_fullwidth_project_prev_next', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar Prev / Next post' ],

            // ── Globales ──────────────────────────
            [ 'section' => 'global', 'section_label' => 'Globales', 'key' => 'rnr_video_portpost_vid_url',        'type' => 'url',    'label' => 'URL Video Popup (Youtube/Vimeo)' ],
            [ 'section' => 'global', 'key' => 'rnr_port_carousel_info_description_opt', 'type' => 'toggle', 'on' => 'st2', 'off' => 'st1', 'default' => 'st1', 'label' => 'Mostrar título y caption en imágenes' ],
        ];
    }

    /** Schema para st4 — Fullscreen Slider */
    private static function schema_st4() {
        return [
            // ── Modo fullscreen ───────────────────
            [ 'section' => 'fullscreen', 'section_label' => 'Modo Fullscreen', 'key' => 'rnr_portfolio_full_slider_details_screen',       'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Modo pantalla completa' ],
            [ 'section' => 'fullscreen', 'key' => 'rnr_portfolio_full_slider_details_screen_title', 'type' => 'text', 'label' => 'Texto del botón fullscreen', 'placeholder' => 'Ej: Pantalla completa' ],

            // ── Thumbnails ────────────────────────
            [ 'section' => 'thumbnails', 'section_label' => 'Thumbnails', 'key' => 'rnr_portfolio_full_slider_details_thumb',       'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar thumbnails' ],
            [ 'section' => 'thumbnails', 'key' => 'rnr_portfolio_full_slider_details_thumb_title', 'type' => 'text', 'label' => 'Texto del botón thumbnails' ],

            // ── Details info ──────────────────────
            [ 'section' => 'details_info', 'section_label' => 'Detalles del proyecto', 'key' => 'rnr_portfolio_full_slider_details_view',       'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar info de detalles' ],
            [ 'section' => 'details_info', 'key' => 'rnr_portfolio_full_slider_details_view_title', 'type' => 'text', 'label' => 'Texto "View Details"' ],

            // ── Title de la sección de info ───────
            [ 'section' => 'project_title', 'section_label' => 'Título de la información', 'key' => 'rnr_portfolio_full_slider_details_info_title_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar título' ],
            [ 'section' => 'project_title', 'key' => 'rnr_portfolio_full_slider_details_info_title', 'type' => 'text', 'label' => 'Texto del título' ],

            // ── Project information ───────────────
            [ 'section' => 'info', 'section_label' => 'Información del proyecto', 'key' => 'rnr_portfolio_full_slider_project_info', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar información del proyecto' ],
            [ 'section' => 'info', 'key' => 'rnr_portfolio_full_slider_project_info_main', 'type' => 'repeater', 'label' => 'Datos del proyecto', 'item_label' => 'Dato',
                'fields' => [
                    [ 'key' => 'rnr_port_fl_sl_dt_in_title',             'type' => 'text', 'label' => 'Etiqueta',       'placeholder' => 'Ej: Location' ],
                    [ 'key' => 'rnr_port_fl_sl_dt_in_subtitle',          'type' => 'text', 'label' => 'Valor',          'placeholder' => 'Ej: NY, USA' ],
                    [ 'key' => 'rnr_port_fl_sl_dt_in_subtitle_url',      'type' => 'url',  'label' => 'URL (opcional)' ],
                    [ 'key' => 'rnr_port_fl_sl_dt_in_subtitle_urltarget','type' => 'select', 'label' => 'Abrir en', 'default' => '_self',
                        'options' => [
                            [ 'value' => '_self',  'label' => 'Misma pestaña' ],
                            [ 'value' => '_blank', 'label' => 'Nueva pestaña' ],
                        ],
                    ],
                ],
            ],

            // ── Botón ─────────────────────────────
            [ 'section' => 'button', 'section_label' => 'Botón del proyecto', 'key' => 'rnr_portfolio_full_slider_details_info_button_show', 'type' => 'toggle', 'on' => 'st1', 'off' => 'st2', 'default' => 'st1', 'label' => 'Mostrar botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_fl_sl_dt_in_button_text',        'type' => 'text', 'label' => 'Texto del botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_fl_sl_dt_in_button_url',         'type' => 'url',  'label' => 'URL del botón' ],
            [ 'section' => 'button', 'key' => 'rnr_port_fl_sl_dt_in_button_link_target', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Abrir en nueva pestaña' ],

            // ── Navegación ────────────────────────
            [ 'section' => 'navigation', 'section_label' => 'Navegación', 'key' => 'rnr_portfolio_full_slider_project_prev_next', 'type' => 'toggle', 'on' => 'yes', 'off' => 'no', 'default' => 'yes', 'label' => 'Mostrar Prev / Next post' ],

            // ── Globales ──────────────────────────
            [ 'section' => 'global', 'section_label' => 'Globales', 'key' => 'rnr_video_portpost_vid_url',        'type' => 'url',    'label' => 'URL Video Popup (Youtube/Vimeo)' ],
            [ 'section' => 'global', 'key' => 'rnr_port_carousel_info_description_opt', 'type' => 'toggle', 'on' => 'st2', 'off' => 'st1', 'default' => 'st1', 'label' => 'Mostrar título y caption en imágenes' ],
        ];
    }

    public static function get_portfolio_meta( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $schema = self::meta_schema( $layout );
        $values = self::read_meta_values( $id, $schema );

        return rest_ensure_response( [
            'id'     => $id,
            'layout' => $layout,
            'schema' => $schema,
            'values' => $values,
        ] );
    }

    public static function set_portfolio_meta( WP_REST_Request $req ) {
        $id = intval( $req['id'] );
        if ( get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }
        $layout = get_post_meta( $id, 'rnr_wr_port_dt_opt', true ) ?: 'st1';
        $schema = self::meta_schema( $layout );
        $values = (array) $req->get_param( 'values' );

        foreach ( $schema as $field ) {
            if ( ! array_key_exists( $field['key'], $values ) ) continue;
            $val = $values[ $field['key'] ];

            if ( $field['type'] === 'repeater' ) {
                $clean = [];
                foreach ( (array) $val as $row ) {
                    if ( ! is_array( $row ) ) continue;
                    $crow = [];
                    $hasContent = false;
                    foreach ( $field['fields'] as $sub ) {
                        $sv = $row[ $sub['key'] ] ?? '';
                        $crow[ $sub['key'] ] = self::sanitize_field( $sub, $sv );
                        if ( $crow[ $sub['key'] ] !== '' ) $hasContent = true;
                    }
                    if ( $hasContent ) $clean[] = $crow;
                }
                update_post_meta( $id, $field['key'], $clean );
            }
            elseif ( $field['type'] === 'image' ) {
                // image_advanced: multi-row de IDs
                delete_post_meta( $id, $field['key'] );
                $list = is_array( $val ) ? $val : ( $val ? [ $val ] : [] );
                foreach ( $list as $img_id ) {
                    $img_id = intval( $img_id );
                    if ( $img_id > 0 ) add_post_meta( $id, $field['key'], $img_id, false );
                }
            }
            else {
                update_post_meta( $id, $field['key'], self::sanitize_field( $field, $val ) );
            }
        }

        return self::get_portfolio_meta( $req );
    }

    private static function read_meta_values( $id, $schema ) {
        $out = [];
        foreach ( $schema as $field ) {
            $key = $field['key'];
            if ( $field['type'] === 'repeater' ) {
                $raw = get_post_meta( $id, $key, true );
                $rows = is_array( $raw ) ? $raw : [];
                $out[ $key ] = $rows;
            }
            elseif ( $field['type'] === 'image' ) {
                $rows = get_post_meta( $id, $key, false );
                if ( ! is_array( $rows ) ) $rows = [];
                // Compatibilidad con formato legacy serializado
                if ( count( $rows ) === 1 && is_array( $rows[0] ) ) $rows = $rows[0];
                $ids = [];
                foreach ( $rows as $r ) {
                    $i = intval( $r );
                    if ( $i > 0 ) $ids[] = $i;
                }
                $out[ $key ] = empty( $field['multi'] ) ? ( $ids[0] ?? null ) : $ids;
                // También adjuntamos URLs precalculadas para preview
                if ( ! empty( $ids ) ) {
                    $out[ $key . '__preview' ] = wp_get_attachment_image_url( $ids[0], 'medium' );
                }
            }
            else {
                $val = get_post_meta( $id, $key, true );
                $out[ $key ] = $val !== '' ? $val : ( $field['default'] ?? '' );
            }
        }
        return $out;
    }

    private static function sanitize_field( $field, $val ) {
        switch ( $field['type'] ) {
            case 'url':       return esc_url_raw( (string) $val );
            case 'textarea':  return sanitize_textarea_field( (string) $val );
            case 'select':    return sanitize_text_field( (string) $val );
            case 'toggle':    return sanitize_text_field( (string) $val );
            case 'number':    return is_numeric( $val ) ? floatval( $val ) : 0;
            case 'text':
            default:          return sanitize_text_field( (string) $val );
        }
    }

    /**
     * Duplica un portfolio existente como plantilla:
     * copia layout, categorías y todos los meta keys del schema (excepto
     * la galería, que el usuario rellenará después con sync-folder o picker).
     * Status: draft. Título: original + " (copia)".
     *
     * Body params opcionales:
     *   title         string   título del nuevo portfolio (override)
     *   include_gallery bool   true para copiar también la galería
     */
    public static function duplicate_portfolio( WP_REST_Request $req ) {
        $sourceId = intval( $req['id'] );
        $source   = get_post( $sourceId );
        if ( ! $source || $source->post_type !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Portfolio no encontrado', [ 'status' => 404 ] );
        }

        $newTitle = sanitize_text_field( $req->get_param( 'title' ) ?: ( $source->post_title . ' (copia)' ) );
        $includeGallery = (bool) $req->get_param( 'include_gallery' );

        // Crear nuevo post
        $newId = wp_insert_post( [
            'post_type'    => self::CPT,
            'post_title'   => $newTitle,
            'post_content' => $source->post_content,
            'post_excerpt' => $source->post_excerpt,
            'post_status'  => 'draft',
        ], true );
        if ( is_wp_error( $newId ) ) return $newId;

        // Copiar categorías
        $cat_ids = wp_get_object_terms( $sourceId, self::TAX, [ 'fields' => 'ids' ] );
        if ( ! is_wp_error( $cat_ids ) && ! empty( $cat_ids ) ) {
            wp_set_object_terms( $newId, array_map( 'intval', $cat_ids ), self::TAX, false );
        }

        // Copiar layout
        $layout = get_post_meta( $sourceId, 'rnr_wr_port_dt_opt', true );
        if ( $layout ) update_post_meta( $newId, 'rnr_wr_port_dt_opt', $layout );

        // Copiar todos los meta del schema (sidebar, info, button, etc.) excepto galería
        $schema = self::meta_schema( $layout ?: 'st1' );
        $galleryKey = self::gallery_meta_key( $layout ?: 'st1' );
        foreach ( $schema as $field ) {
            if ( $field['type'] === 'image' ) {
                // image_advanced: multi-row
                $rows = get_post_meta( $sourceId, $field['key'], false );
                foreach ( (array) $rows as $r ) {
                    $i = intval( $r );
                    if ( $i > 0 ) add_post_meta( $newId, $field['key'], $i, false );
                }
            } else {
                $val = get_post_meta( $sourceId, $field['key'], true );
                if ( $val !== '' && $val !== null ) {
                    update_post_meta( $newId, $field['key'], $val );
                }
            }
        }

        // Galería: copiar solo si include_gallery=true
        if ( $includeGallery ) {
            $ids = self::read_gallery_meta( $sourceId, $galleryKey );
            self::write_gallery_meta( $newId, $galleryKey, $ids );
        }

        // Imagen destacada — opcional copiar la del original
        $thumb = get_post_thumbnail_id( $sourceId );
        if ( $thumb ) set_post_thumbnail( $newId, $thumb );

        return rest_ensure_response( self::format_portfolio( get_post( $newId ), true ) );
    }
}
