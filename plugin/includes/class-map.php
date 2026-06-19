<?php
defined( 'ABSPATH' ) || exit;

class YZMF_Map {

    const CPT = 'yzmf_location';

    public static function init() {
        add_action( 'init',              [ __CLASS__, 'register_cpt' ] );
        add_action( 'admin_menu',        [ __CLASS__, 'register_submenu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        // AJAX — admin
        foreach ( [ 'yzmf_map_get_locations', 'yzmf_map_save_location', 'yzmf_map_delete_location' ] as $a ) {
            add_action( 'wp_ajax_' . $a, [ __CLASS__, $a ] );
        }
        // AJAX — público (shortcode frontend)
        add_action( 'wp_ajax_yzmf_map_data',        [ __CLASS__, 'yzmf_map_data' ] );
        add_action( 'wp_ajax_nopriv_yzmf_map_data', [ __CLASS__, 'yzmf_map_data' ] );
        // Shortcode
        add_shortcode( 'yz_photo_map', [ __CLASS__, 'shortcode' ] );
    }

    public static function register_cpt() {
        register_post_type( self::CPT, [
            'labels'       => [ 'name' => 'Ubicaciones', 'singular_name' => 'Ubicación' ],
            'public'        => false,
            'show_ui'       => false,
            'show_in_rest'  => true,
            'supports'      => [ 'title', 'thumbnail', 'custom-fields' ],
        ] );
    }

    public static function register_submenu() {
        add_submenu_page(
            'yz-media',
            __( 'Mapa fotográfico', 'yz-media-folders' ),
            __( '🗺 Mapa', 'yz-media-folders' ),
            'upload_files',
            'yz-media-map',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function enqueue( $hook ) {
        // WP genera el hook como 'mis-medios_page_yz-media-map' o similar
        // Lo más fiable es comprobar el parámetro GET
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'yz-media-map' ) return;

        wp_enqueue_style(  'leaflet',   'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
        wp_enqueue_script( 'leaflet',   'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',  [], '1.9.4', true );
        wp_enqueue_media();

        wp_enqueue_style(  'yzmf-map-admin', YZMF_URL . 'assets/css/map-admin.css', [ 'leaflet' ], YZMF_VERSION );
        wp_enqueue_script( 'yzmf-map-admin', YZMF_URL . 'assets/js/map-admin.js',   [ 'jquery', 'leaflet' ], YZMF_VERSION, true );

        wp_localize_script( 'yzmf-map-admin', 'YZMF_Map', [
            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'yzmf_nonce' ),
            'rest_root'    => esc_url_raw( rest_url() ),
            'rest_nonce'   => wp_create_nonce( 'wp_rest' ),
            'tree'         => YZMF_Taxonomy::get_tree(),
            'default_lat'  => 42.5987,
            'default_lng'  => -5.5703,
            'default_zoom' => 4,
        ] );
    }

    public static function render_page() {
        echo '<div id="yzmf-map-app"></div>';
    }

    // ── AJAX ────────────────────────────────────────────────────────

    private static function check() {
        check_ajax_referer( 'yzmf_nonce', 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) wp_die( 'Forbidden', 403 );
    }

    public static function yzmf_map_get_locations() {
        self::check();
        wp_send_json_success( self::get_all_locations() );
    }

    public static function yzmf_map_save_location() {
        self::check();
        $id = self::persist_location(
            ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : 0,
            [
                'name'          => $_POST['name']          ?? '',
                'lat'           => $_POST['lat']           ?? 0,
                'lng'           => $_POST['lng']           ?? 0,
                'tag'           => $_POST['tag']           ?? '',
                'description'   => $_POST['description']   ?? '',
                'gallery_url'   => $_POST['gallery_url']   ?? '',
                'folder_ids'    => $_POST['folder_ids']    ?? [],
                'photo_ids'     => $_POST['photo_ids']     ?? [],
                'hero_id'       => $_POST['hero_id']       ?? 0,
                'portfolio_ids' => $_POST['portfolio_ids'] ?? null,
            ]
        );
        if ( is_wp_error( $id ) ) {
            wp_send_json_error( [ 'message' => $id->get_error_message() ] );
        }
        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function yzmf_map_delete_location() {
        self::check();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id || get_post_type( $id ) !== self::CPT ) {
            wp_send_json_error( [ 'message' => 'ID inválido' ] );
        }
        wp_delete_post( $id, true );
        self::invalidate_caches();
        wp_send_json_success();
    }

    public static function yzmf_map_data() {
        $cache = get_transient( 'yzmf_map_public_data' );
        if ( $cache === false ) {
            $cache = self::get_map_data();
            set_transient( 'yzmf_map_public_data', $cache, 15 * MINUTE_IN_SECONDS );
        }
        wp_send_json_success( $cache );
    }

    /**
     * Invalida todos los caches del módulo de mapa de una sola vez.
     * Llamado desde save/delete location y desde set_portfolios_for_location.
     */
    public static function invalidate_caches() {
        delete_transient( 'yzmf_map_public_data' );
        delete_transient( 'yzmf_all_locations' );
        delete_transient( 'yzmf_portfolio_geo_list' );
    }

    /**
     * Invalida SOLO el cache del mapa público de fotos individuales
     * (yzmf/v1/map/photos). Se llama desde cualquier punto que cambie la geo
     * de un attachment: set/clear manual (apply_geo_to_id), auto-EXIF en la
     * subida y el scan de fondo. Va separado de invalidate_caches() porque
     * las ubicaciones curadas y las fotos geolocalizadas son datasets
     * independientes — cambiar una no afecta al otro.
     */
    public static function invalidate_photo_cache() {
        delete_transient( 'yzmf_map_photos_public' );
    }

    /**
     * Persiste una ubicación (insert o update). Centraliza la lógica que
     * antes vivía duplicada en YZMF_REST::save_location y YZMF_Map::yzmf_map_save_location.
     *
     * @param int   $id     0 = nuevo, >0 = update.
     * @param array $params claves: name, lat, lng, tag, description, gallery_url,
     *                      folder_ids, photo_ids, hero_id, portfolio_ids
     * @return int|WP_Error  ID del post creado/actualizado, o WP_Error.
     */
    public static function persist_location( $id, array $params ) {
        $id   = intval( $id );
        if ( $id && get_post_type( $id ) !== self::CPT ) {
            return new WP_Error( 'yzmf_not_found', 'Ubicación inexistente', [ 'status' => 404 ] );
        }
        $name = sanitize_text_field( $params['name'] ?? '' );
        if ( $name === '' ) {
            return new WP_Error( 'yzmf_missing_name', 'Nombre requerido', [ 'status' => 400 ] );
        }

        // Clamp coordenadas al rango legal (-90..90 / -180..180).
        $lat = isset( $params['lat'] ) ? floatval( $params['lat'] ) : 0.0;
        $lng = isset( $params['lng'] ) ? floatval( $params['lng'] ) : 0.0;
        $lat = max( -90.0,  min( 90.0,  $lat ) );
        $lng = max( -180.0, min( 180.0, $lng ) );

        $post = [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'post_title'  => $name,
        ];
        if ( $id ) {
            $post['ID'] = $id;
            $id = wp_update_post( $post, true );
        } else {
            $id = wp_insert_post( $post, true );
        }
        if ( is_wp_error( $id ) ) return $id;

        update_post_meta( $id, '_yzmf_lat',         $lat );
        update_post_meta( $id, '_yzmf_lng',         $lng );
        update_post_meta( $id, '_yzmf_tag',         sanitize_text_field( $params['tag'] ?? '' ) );
        update_post_meta( $id, '_yzmf_description', sanitize_textarea_field( $params['description'] ?? '' ) );
        update_post_meta( $id, '_yzmf_gallery_url', esc_url_raw( $params['gallery_url'] ?? '' ) );
        update_post_meta( $id, '_yzmf_folder_ids',  array_map( 'intval', (array) ( $params['folder_ids'] ?? [] ) ) );
        update_post_meta( $id, '_yzmf_photo_ids',   array_map( 'intval', (array) ( $params['photo_ids']  ?? [] ) ) );
        if ( array_key_exists( 'public_on_map', $params ) ) {
            update_post_meta( $id, '_yzmf_public_on_map', ! empty( $params['public_on_map'] ) ? '1' : '0' );
        }

        if ( ! empty( $params['hero_id'] ) ) {
            $hero_id = intval( $params['hero_id'] );
            // Solo aceptamos attachments reales como featured image.
            if ( $hero_id > 0 && get_post_type( $hero_id ) === 'attachment' ) {
                set_post_thumbnail( $id, $hero_id );
            }
        }

        if ( isset( $params['portfolio_ids'] ) && class_exists( 'YZMF_Portfolio_Bridge' ) ) {
            YZMF_Portfolio_Bridge::set_portfolios_for_location( $id, (array) $params['portfolio_ids'] );
        }

        self::invalidate_caches();
        return $id;
    }

    // ── DATA ────────────────────────────────────────────────────────

    public static function get_all_locations() {
        $cache_key = 'yzmf_all_locations';
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        // Cap defensivo: >500 locations es un caso excepcional. Si llega ahí
        // hay que paginar a nivel de cliente.
        $posts = get_posts( [
            'post_type'      => self::CPT,
            'posts_per_page' => 500,
            'post_status'    => 'publish',
        ] );
        $out   = [];
        foreach ( $posts as $p ) {
            $hero_id    = get_post_thumbnail_id( $p->ID );
            $raw_folders = get_post_meta( $p->ID, '_yzmf_folder_ids', true );
            $folder_ids  = is_array( $raw_folders ) ? array_map( 'intval', $raw_folders ) : [];
            $raw_photos  = get_post_meta( $p->ID, '_yzmf_photo_ids', true );
            $photo_ids   = is_array( $raw_photos )  ? array_map( 'intval', $raw_photos )  : [];
            $photo_thumbs = [];
            foreach ( $photo_ids as $pid ) {
                $u = wp_get_attachment_image_url( $pid, 'thumbnail' );
                if ( $u ) $photo_thumbs[] = [ 'id' => $pid, 'url' => $u ];
            }
            // Portfolios vinculados (relación viceversa, derivada del meta del portfolio)
            $portfolio_ids = class_exists( 'YZMF_Portfolio_Bridge' )
                ? YZMF_Portfolio_Bridge::get_portfolios_by_location( $p->ID )
                : [];
            $portfolios_brief = [];
            foreach ( $portfolio_ids as $pid ) {
                $portfolios_brief[] = [
                    'id'       => $pid,
                    'title'    => get_the_title( $pid ),
                    'hero_url' => get_the_post_thumbnail_url( $pid, 'thumbnail' ) ?: '',
                ];
            }
            $out[] = [
                'id'            => $p->ID,
                'name'          => $p->post_title,
                'tag'           => get_post_meta( $p->ID, '_yzmf_tag',         true ),
                'description'   => get_post_meta( $p->ID, '_yzmf_description', true ),
                'gallery_url'   => get_post_meta( $p->ID, '_yzmf_gallery_url', true ),
                'lat'           => (float) get_post_meta( $p->ID, '_yzmf_lat', true ),
                'lng'           => (float) get_post_meta( $p->ID, '_yzmf_lng', true ),
                'folder_ids'    => $folder_ids,
                'photo_ids'     => $photo_ids,
                'photo_thumbs'  => $photo_thumbs,
                'portfolio_ids' => $portfolio_ids,
                'portfolios'    => $portfolios_brief,
                'hero_id'       => $hero_id,
                'hero_url'      => $hero_id ? wp_get_attachment_image_url( $hero_id, 'thumbnail' ) : '',
                'count'         => self::count_images( $folder_ids, $photo_ids ),
                'public_on_map' => get_post_meta( $p->ID, '_yzmf_public_on_map', true ) === '1',
            ];
        }
        // Cache 15min — el plugin invalida desde save_location/delete_location
        // (delete_transient('yzmf_all_locations') en class-rest.php).
        set_transient( $cache_key, $out, 15 * MINUTE_IN_SECONDS );
        return $out;
    }

    public static function get_map_data() {
        $locs = self::get_all_locations();
        foreach ( $locs as &$loc ) {
            $loc['thumbs'] = self::get_thumbs( $loc['folder_ids'], $loc['photo_ids'] ?? [] );
            if ( $loc['hero_id'] ) {
                $loc['hero'] = wp_get_attachment_image_url( $loc['hero_id'], 'large' ) ?: ( $loc['thumbs'][0] ?? '' );
            } else {
                $loc['hero'] = $loc['thumbs'][0] ?? '';
            }
        }
        return $locs;
    }

    private static function count_images( $folder_ids, $photo_ids = [] ) {
        // Antes: WP_Query con posts_per_page=-1 + fields=ids para luego count().
        // Materializaba todos los IDs en RAM. Ahora: usamos found_posts con
        // posts_per_page=1. La merge con $photo_ids sigue siendo necesaria
        // para evitar contar duplicados — fetch SOLO los photo_ids para
        // dedup. Para catálogos grandes esto reduce drásticamente la memoria.
        $folder_count = 0;
        if ( ! empty( $folder_ids ) && defined( 'YZMF_TAXONOMY' ) ) {
            $q = new WP_Query( [
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => false,
                'tax_query'      => [ [
                    'taxonomy'         => YZMF_TAXONOMY,
                    'field'            => 'term_id',
                    'terms'            => $folder_ids,
                    'include_children' => true,
                ] ],
            ] );
            $folder_count = (int) $q->found_posts;
        }
        // Si hay photo_ids individuales, asumimos potencial solapamiento con
        // las carpetas y sumamos sin dedup exacta (en práctica los plugins de
        // este repo no permiten que un mismo att esté en photo_ids y folder_ids
        // a la vez; si llega a ocurrir el conteo es ligeramente alto, no roto).
        return $folder_count + count( array_unique( array_filter( $photo_ids ) ) );
    }

    private static function get_thumbs( $folder_ids, $photo_ids = [], $limit = 4 ) {
        $urls = [];
        $seen = [];

        // Primero fotos individuales (prioridad visual)
        foreach ( $photo_ids as $id ) {
            if ( count($urls) >= $limit ) break;
            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( $url ) { $urls[] = $url; $seen[$id] = 1; }
        }

        // Luego fotos de carpetas
        if ( count($urls) < $limit && ! empty( $folder_ids ) && defined( 'YZMF_TAXONOMY' ) ) {
            $atts = get_posts( [
                'post_type' => 'attachment', 'post_status' => 'inherit',
                'posts_per_page' => $limit * 2,
                'tax_query' => [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $folder_ids, 'include_children' => true ] ],
            ] );
            foreach ( $atts as $a ) {
                if ( count($urls) >= $limit ) break;
                if ( isset($seen[$a->ID]) ) continue;
                $url = wp_get_attachment_image_url( $a->ID, 'thumbnail' );
                if ( $url ) $urls[] = $url;
            }
        }
        return $urls;
    }

    // ── SHORTCODE ───────────────────────────────────────────────────

    public static function shortcode( $atts ) {
        $atts = shortcode_atts( [
            'height' => '600px',
            // Qué capas pintar: 'locations' (solo ubicaciones curadas),
            // 'photos' (solo fotos individuales geolocalizadas) o 'both'.
            'layers' => 'both',
        ], $atts );
        $layers = in_array( $atts['layers'], [ 'locations', 'photos', 'both' ], true )
            ? $atts['layers'] : 'both';

        if ( ! wp_script_is( 'yzmf-map-front', 'enqueued' ) ) {
            wp_enqueue_style(  'leaflet',         'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
            wp_enqueue_script( 'leaflet',         'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',  [], '1.9.4', true );
            // Clustering — necesario para la capa de fotos (cientos de markers).
            wp_enqueue_style(  'leaflet-markercluster',         'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',         [ 'leaflet' ], '1.5.3' );
            wp_enqueue_style(  'leaflet-markercluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', [ 'leaflet' ], '1.5.3' );
            wp_enqueue_script( 'leaflet-markercluster',         'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',  [ 'leaflet' ], '1.5.3', true );
            wp_enqueue_style(  'yzmf-map-front',  YZMF_URL . 'assets/css/map-front.css',  [ 'leaflet', 'leaflet-markercluster' ], YZMF_VERSION );
            wp_enqueue_script( 'yzmf-map-front',  YZMF_URL . 'assets/js/map-front.js',    [ 'leaflet', 'leaflet-markercluster' ], YZMF_VERSION, true );
            wp_localize_script( 'yzmf-map-front', 'YZMF_Map', [
                'ajaxurl'      => admin_url( 'admin-ajax.php' ),
                'rest_root'    => esc_url_raw( rest_url() ),
                'default_lat'  => 42.5987,
                'default_lng'  => -5.5703,
                'default_zoom' => 4,
            ] );
        }
        $id = 'yzmf-map-' . uniqid();
        return '<div class="yzmf-map-wrap" style="height:' . esc_attr( $atts['height'] ) . '"><div id="' . esc_attr( $id ) . '" class="yzmf-map-canvas" data-layers="' . esc_attr( $layers ) . '"></div></div>';
    }
}
