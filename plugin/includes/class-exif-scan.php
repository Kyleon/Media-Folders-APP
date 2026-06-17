<?php
/**
 * Backfill de geolocalización desde EXIF.
 *
 * Las imágenes que ya estaban en el catálogo antes del fix de extracción
 * automática siguen sin geo aunque su EXIF la contenga. Esta clase recorre
 * el catálogo en background (wp_cron) por lotes de 50 y rellena el meta
 * _yzmf_geo_lat / _yzmf_geo_lng / _yzmf_geo_source = 'exif' donde aplique.
 *
 * Ciclo de vida:
 *   1. start_scan(): resetea offset a 0, programa el primer tick inmediato.
 *   2. process_batch() (cron callback): procesa 50 attachments siguientes.
 *      Si quedan más, se reprograma; si no, se marca done.
 *   3. status: persistido en option 'yzmf_exif_scan_state'.
 *
 * El plugin auto-arranca un scan tras un cambio de versión (clave
 * yzmf_exif_scan_version != YZMF_VERSION).
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Exif_Scan {

    const HOOK         = 'yzmf_exif_scan_tick';
    const STATE_OPTION = 'yzmf_exif_scan_state';
    const VERSION_OPT  = 'yzmf_exif_scan_version';
    const BATCH        = 50;

    public static function init() {
        add_action( self::HOOK, [ __CLASS__, 'process_batch' ] );
        // Migration: si la versión persistida no coincide con la del plugin,
        // arrancamos un scan en background. Idempotente: si ya hay uno
        // corriendo, no se duplica.
        add_action( 'init', [ __CLASS__, 'maybe_auto_start' ], 20 );
    }

    /**
     * Estado del scan. Estructura:
     *   { running: bool, offset: int, total: int, processed: int,
     *     found: int, started: int|null, finished: int|null }
     */
    public static function get_state() {
        $defaults = [
            'running'   => false,
            'offset'    => 0,
            'total'     => 0,
            'processed' => 0,
            'found'     => 0,
            'started'   => null,
            'finished'  => null,
        ];
        $state = get_option( self::STATE_OPTION, [] );
        if ( ! is_array( $state ) ) $state = [];
        return array_merge( $defaults, $state );
    }

    private static function update_state( array $patch ) {
        $state = array_merge( self::get_state(), $patch );
        update_option( self::STATE_OPTION, $state, false );
        return $state;
    }

    /**
     * Cuenta cuántos attachments-imagen del catálogo no tienen _yzmf_geo_lat
     * (los que podrían beneficiarse del scan).
     */
    private static function count_unscanned() {
        $q = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [ 'key' => '_yzmf_geo_lat', 'compare' => 'NOT EXISTS' ],
            ],
        ] );
        return (int) $q->found_posts;
    }

    /**
     * Lanza un nuevo scan. Si ya hay uno corriendo, no hace nada (idempotente).
     * Devuelve el estado actualizado.
     */
    public static function start_scan() {
        $current = self::get_state();
        if ( $current['running'] ) return $current;

        $total = self::count_unscanned();
        $state = self::update_state( [
            'running'   => $total > 0,
            'offset'    => 0,
            'total'     => $total,
            'processed' => 0,
            'found'     => 0,
            'started'   => time(),
            'finished'  => null,
        ] );

        if ( $total > 0 && ! wp_next_scheduled( self::HOOK ) ) {
            // Primer tick inmediato; los siguientes se reprograman al final
            // de cada batch.
            wp_schedule_single_event( time() + 5, self::HOOK );
        }
        return $state;
    }

    /**
     * Auto-start tras subir el plugin a una versión nueva. Solo dispara una
     * vez por versión.
     */
    public static function maybe_auto_start() {
        $stored = get_option( self::VERSION_OPT );
        if ( $stored === YZMF_VERSION ) return;
        update_option( self::VERSION_OPT, YZMF_VERSION, false );
        self::start_scan();
    }

    /**
     * Callback de wp_cron. Procesa BATCH attachments y reprograma si quedan.
     */
    public static function process_batch() {
        $state = self::get_state();
        if ( ! $state['running'] ) return;

        $q = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => self::BATCH,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_yzmf_geo_lat',     'compare' => 'NOT EXISTS' ],
                [ 'key' => '_yzmf_geo_scanned', 'compare' => 'NOT EXISTS' ],
            ],
            'no_found_rows'  => true,
        ] );

        $found = 0;
        foreach ( $q->posts as $id ) {
            $path = get_attached_file( $id );
            if ( ! $path ) continue;
            $gps = class_exists( 'YZMF_Ajax' )
                ? YZMF_Ajax::extract_exif_gps( $path )
                : null;
            if ( $gps ) {
                update_post_meta( $id, '_yzmf_geo_lat',    $gps['lat'] );
                update_post_meta( $id, '_yzmf_geo_lng',    $gps['lng'] );
                update_post_meta( $id, '_yzmf_geo_source', 'exif' );
                $found++;
            } else {
                // Marca de "scaneado sin geo": evita reprocesar en el siguiente
                // batch. Como el meta_query usa NOT EXISTS sobre _yzmf_geo_lat,
                // guardamos una marca distinta que no rompa la condición.
                update_post_meta( $id, '_yzmf_geo_scanned', 1 );
            }
        }

        // Excluir los marcados "scaneados sin geo" del siguiente batch.
        // Repetimos: si no hay más NOT EXISTS scanned=NOT EXISTS, hemos acabado.
        $remaining = self::count_remaining();
        $batch_size = count( $q->posts );
        $processed = $state['processed'] + $batch_size;

        if ( $remaining === 0 || $batch_size === 0 ) {
            self::update_state( [
                'running'   => false,
                'processed' => $processed,
                'found'     => $state['found'] + $found,
                'finished'  => time(),
            ] );
            wp_clear_scheduled_hook( self::HOOK );
            return;
        }

        self::update_state( [
            'offset'    => $state['offset'] + $batch_size,
            'processed' => $processed,
            'found'     => $state['found'] + $found,
        ] );

        // Reprogramar siguiente tick: 30s para no saturar wp_cron.
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_single_event( time() + 30, self::HOOK );
        }
    }

    /**
     * Cuenta attachments sin geo y sin marca de "scaneado sin geo".
     */
    private static function count_remaining() {
        $q = new WP_Query( [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_yzmf_geo_lat',     'compare' => 'NOT EXISTS' ],
                [ 'key' => '_yzmf_geo_scanned', 'compare' => 'NOT EXISTS' ],
            ],
        ] );
        return (int) $q->found_posts;
    }
}
