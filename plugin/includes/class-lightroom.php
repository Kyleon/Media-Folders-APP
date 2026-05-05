<?php
/**
 * Endpoint REST para Adobe Lightroom Classic - Publish Service.
 *
 * Permite publicar fotos desde Lightroom directamente a una carpeta YZMF.
 * Authentication: Application Password de WordPress (Basic Auth).
 *
 * Endpoints:
 *   POST /yzmf/v1/lightroom/publish
 *     - Multipart upload, campo "file" + "folder_id" + opcional "title"/"caption"/"alt"
 *     - Devuelve { id, url, edit_url } del attachment creado
 *
 *   GET /yzmf/v1/lightroom/folders
 *     - Lista de carpetas YZMF para que LR pueda elegir destino
 *
 *   GET /yzmf/v1/lightroom/check
 *     - Healthcheck — devuelve { ok: true, version, user } si la auth funciona
 *
 * Para integrar con Lightroom Classic, se necesita un Publish Service Plugin
 * (.lrplugin) que use estos endpoints. Documentación en docs/lightroom.md.
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Lightroom {

    const NS = 'yzmf/v1';

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( self::NS, '/lightroom/check', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'check' ],
            'permission_callback' => [ __CLASS__, 'can_publish' ],
        ] );

        register_rest_route( self::NS, '/lightroom/folders', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'list_folders' ],
            'permission_callback' => [ __CLASS__, 'can_publish' ],
        ] );

        register_rest_route( self::NS, '/lightroom/publish', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'publish' ],
            'permission_callback' => [ __CLASS__, 'can_publish' ],
        ] );
    }

    public static function can_publish() {
        return current_user_can( 'upload_files' );
    }

    /* ─────────── HEALTHCHECK ─────────── */

    public static function check( WP_REST_Request $req ) {
        $user = wp_get_current_user();
        return rest_ensure_response( [
            'ok'      => true,
            'version' => defined( 'YZMF_VERSION' ) ? YZMF_VERSION : '?',
            'user'    => $user ? $user->user_login : null,
            'site'    => home_url(),
        ] );
    }

    /* ─────────── LIST FOLDERS ─────────── */

    public static function list_folders( WP_REST_Request $req ) {
        $terms = get_terms( [
            'taxonomy'   => YZMF_TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
        ] );
        if ( is_wp_error( $terms ) ) return $terms;

        $out = [];
        foreach ( $terms as $t ) {
            $out[] = [
                'id'     => (int) $t->term_id,
                'name'   => $t->name,
                'parent' => (int) $t->parent,
                'count'  => (int) $t->count,
            ];
        }
        return rest_ensure_response( $out );
    }

    /* ─────────── PUBLISH ─────────── */

    public static function publish( WP_REST_Request $req ) {
        if ( empty( $_FILES['file'] ) ) {
            return new WP_Error( 'no_file', 'Falta el archivo "file".', [ 'status' => 400 ] );
        }

        $folder_id = (int) $req->get_param( 'folder_id' );
        $title     = (string) $req->get_param( 'title' );
        $caption   = (string) $req->get_param( 'caption' );
        $alt       = (string) $req->get_param( 'alt' );
        $description = (string) $req->get_param( 'description' );
        // Lightroom envía el "remote_id" si ya publicó esta foto antes (para sobrescribir)
        $remote_id = (int) $req->get_param( 'remote_id' );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Si remote_id existe y aún apunta a un attachment válido, lo eliminamos
        // para que Lightroom pueda re-publicar (mismo flujo "republish")
        if ( $remote_id && get_post_type( $remote_id ) === 'attachment' ) {
            wp_delete_attachment( $remote_id, true );
        }

        $att_id = media_handle_upload( 'file', 0 );
        if ( is_wp_error( $att_id ) ) {
            return new WP_Error( 'upload_failed', $att_id->get_error_message(), [ 'status' => 500 ] );
        }

        // Aplicar metadatos
        $update = [ 'ID' => $att_id ];
        if ( $title )       $update['post_title']   = sanitize_text_field( $title );
        if ( $caption )     $update['post_excerpt'] = wp_kses_post( $caption );
        if ( $description ) $update['post_content'] = wp_kses_post( $description );
        if ( count( $update ) > 1 ) wp_update_post( $update );

        if ( $alt ) update_post_meta( $att_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );

        // Asignar carpeta
        if ( $folder_id > 0 ) {
            wp_set_object_terms( $att_id, [ $folder_id ], YZMF_TAXONOMY, false );
        }

        // Disparar el flujo normal de yz-media-folders para que extraiga GPS/EXIF
        // (la clase YZMF_Ajax engancha 'add_attachment' para esto, pero
        // media_handle_upload ya lo dispara)
        do_action( 'yzmf_after_lightroom_publish', $att_id, $req );

        return rest_ensure_response( [
            'id'        => $att_id,
            'remote_id' => $att_id,
            'url'       => wp_get_attachment_url( $att_id ),
            'edit_url'  => get_edit_post_link( $att_id, 'raw' ),
            'title'     => get_the_title( $att_id ),
        ] );
    }
}
