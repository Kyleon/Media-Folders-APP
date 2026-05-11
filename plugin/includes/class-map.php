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
        $d = $_POST;
        $post = [
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field( $d['name'] ?? '' ),
        ];
        $id = ! empty( $d['id'] ) ? intval( $d['id'] ) : 0;
        if ( $id ) {
            if ( get_post_type( $id ) !== self::CPT ) {
                wp_send_json_error( [ 'message' => 'ID inválido para este recurso.' ] );
            }
            $post['ID'] = $id;
            $id = wp_update_post( $post );
        } else {
            $id = wp_insert_post( $post );
        }
        if ( is_wp_error( $id ) ) wp_send_json_error( [ 'message' => $id->get_error_message() ] );

        update_post_meta( $id, '_yzmf_lat',         floatval( $d['lat']         ?? 0 ) );
        update_post_meta( $id, '_yzmf_lng',         floatval( $d['lng']         ?? 0 ) );
        update_post_meta( $id, '_yzmf_tag',         sanitize_text_field( $d['tag']         ?? '' ) );
        update_post_meta( $id, '_yzmf_description', sanitize_textarea_field( $d['description'] ?? '' ) );
        update_post_meta( $id, '_yzmf_gallery_url', esc_url_raw( $d['gallery_url'] ?? '' ) );
        update_post_meta( $id, '_yzmf_folder_ids', array_map( 'intval', (array)( $d['folder_ids']  ?? [] ) ) );
        update_post_meta( $id, '_yzmf_photo_ids',  array_map( 'intval', (array)( $d['photo_ids']   ?? [] ) ) );
        if ( ! empty( $d['hero_id'] ) ) set_post_thumbnail( $id, intval( $d['hero_id'] ) );

        delete_transient( 'yzmf_map_public_data' );
        wp_send_json_success( [ 'id' => $id ] );
    }

    public static function yzmf_map_delete_location() {
        self::check();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id || get_post_type( $id ) !== self::CPT ) {
            wp_send_json_error( [ 'message' => 'ID inválido' ] );
        }
        wp_delete_post( $id, true );
        delete_transient( 'yzmf_map_public_data' );
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

    // ── DATA ────────────────────────────────────────────────────────

    public static function get_all_locations() {
        $posts = get_posts( [ 'post_type' => self::CPT, 'posts_per_page' => -1, 'post_status' => 'publish' ] );
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
            $out[] = [
                'id'           => $p->ID,
                'name'         => $p->post_title,
                'tag'          => get_post_meta( $p->ID, '_yzmf_tag',         true ),
                'description'  => get_post_meta( $p->ID, '_yzmf_description', true ),
                'gallery_url'  => get_post_meta( $p->ID, '_yzmf_gallery_url', true ),
                'lat'          => (float) get_post_meta( $p->ID, '_yzmf_lat', true ),
                'lng'          => (float) get_post_meta( $p->ID, '_yzmf_lng', true ),
                'folder_ids'   => $folder_ids,
                'photo_ids'    => $photo_ids,
                'photo_thumbs' => $photo_thumbs,
                'hero_id'      => $hero_id,
                'hero_url'     => $hero_id ? wp_get_attachment_image_url( $hero_id, 'thumbnail' ) : '',
                'count'        => self::count_images( $folder_ids, $photo_ids ),
            ];
        }
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
        $seen = [];
        // Fotos de carpetas
        if ( ! empty( $folder_ids ) && defined( 'YZMF_TAXONOMY' ) ) {
            $q = new WP_Query( [
                'post_type' => 'attachment', 'post_status' => 'inherit',
                'posts_per_page' => -1, 'fields' => 'ids',
                'tax_query' => [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $folder_ids, 'include_children' => true ] ],
            ] );
            foreach ( $q->posts as $id ) $seen[$id] = 1;
        }
        // Fotos individuales (sin duplicados)
        foreach ( $photo_ids as $id ) $seen[$id] = 1;
        return count( $seen );
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
        $atts = shortcode_atts( [ 'height' => '600px' ], $atts );
        if ( ! wp_script_is( 'yzmf-map-front', 'enqueued' ) ) {
            wp_enqueue_style(  'leaflet',         'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
            wp_enqueue_script( 'leaflet',         'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',  [], '1.9.4', true );
            wp_enqueue_style(  'yzmf-map-front',  YZMF_URL . 'assets/css/map-front.css',  [ 'leaflet' ], YZMF_VERSION );
            wp_enqueue_script( 'yzmf-map-front',  YZMF_URL . 'assets/js/map-front.js',    [ 'leaflet' ], YZMF_VERSION, true );
            wp_localize_script( 'yzmf-map-front', 'YZMF_Map', [
                'ajaxurl'      => admin_url( 'admin-ajax.php' ),
                'rest_root'    => esc_url_raw( rest_url() ),
                'default_lat'  => 42.5987,
                'default_lng'  => -5.5703,
                'default_zoom' => 4,
            ] );
        }
        $id = 'yzmf-map-' . uniqid();
        return '<div class="yzmf-map-wrap" style="height:' . esc_attr( $atts['height'] ) . '"><div id="' . esc_attr( $id ) . '" class="yzmf-map-canvas"></div></div>';
    }
}
