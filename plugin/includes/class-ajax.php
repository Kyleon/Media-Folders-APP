<?php
defined( 'ABSPATH' ) || exit;

class YZMF_Ajax {

    public static function init() {
        $actions = [
            'yzmf_get_tree',
            'yzmf_create_folder',
            'yzmf_rename_folder',
            'yzmf_delete_folder',
            'yzmf_get_images',
            'yzmf_assign_images',
            'yzmf_copy_images',
            'yzmf_remove_from_folder',
            'yzmf_delete_images',
            'yzmf_get_image_detail',
            'yzmf_save_image_meta',
            'yzmf_get_used_in',
            'yzmf_regen_thumbnails',
            'yzmf_generate_ai_meta',
            'yzmf_backfill_filesizes',
        ];
        foreach ( $actions as $a ) {
            add_action( 'wp_ajax_' . $a, [ __CLASS__, $a ] );
        }
        add_action( 'add_attachment', [ __CLASS__, 'assign_on_upload' ] );
        add_filter( 'wp_generate_attachment_metadata', [ __CLASS__, 'store_filesize_meta' ], 10, 2 );
    }

    public static function store_filesize_meta( $metadata, $attachment_id ) {
        $path = get_attached_file( $attachment_id );
        if ( $path && file_exists( $path ) ) {
            update_post_meta( $attachment_id, '_yzmf_filesize', filesize( $path ) );
            // Auto-detectar GPS del EXIF (sólo si la imagen aún no tiene geo asignada)
            if ( get_post_meta( $attachment_id, '_yzmf_geo_lat', true ) === '' ) {
                $gps = self::extract_exif_gps( $path );
                if ( $gps ) {
                    update_post_meta( $attachment_id, '_yzmf_geo_lat',    $gps['lat'] );
                    update_post_meta( $attachment_id, '_yzmf_geo_lng',    $gps['lng'] );
                    update_post_meta( $attachment_id, '_yzmf_geo_source', 'exif' );
                }
            }
        }
        return $metadata;
    }

    /**
     * Extrae coordenadas GPS del EXIF de una imagen JPG.
     * Devuelve [ 'lat' => float, 'lng' => float ] o null.
     */
    public static function extract_exif_gps( $path ) {
        if ( ! function_exists( 'exif_read_data' ) ) return null;
        if ( ! is_readable( $path ) ) return null;
        $exif = @exif_read_data( $path );
        if ( ! $exif || empty( $exif['GPSLatitude'] ) || empty( $exif['GPSLongitude'] ) ) return null;

        $lat = self::dms_to_decimal( $exif['GPSLatitude'],  $exif['GPSLatitudeRef']  ?? 'N' );
        $lng = self::dms_to_decimal( $exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'E' );
        if ( $lat === null || $lng === null ) return null;
        if ( $lat === 0.0 && $lng === 0.0 )    return null; // ignorar geo "vacía"
        return [ 'lat' => $lat, 'lng' => $lng ];
    }

    private static function dms_to_decimal( $dms, $ref ) {
        if ( ! is_array( $dms ) || count( $dms ) < 3 ) return null;
        $deg = self::frac_to_float( $dms[0] );
        $min = self::frac_to_float( $dms[1] );
        $sec = self::frac_to_float( $dms[2] );
        if ( $deg === null || $min === null || $sec === null ) return null;
        $dec = $deg + ( $min / 60 ) + ( $sec / 3600 );
        if ( in_array( strtoupper( (string) $ref ), [ 'S', 'W' ], true ) ) $dec = -$dec;
        return round( $dec, 6 );
    }

    private static function frac_to_float( $frac ) {
        if ( is_numeric( $frac ) ) return floatval( $frac );
        if ( is_string( $frac ) && strpos( $frac, '/' ) !== false ) {
            list( $a, $b ) = explode( '/', $frac );
            if ( floatval( $b ) == 0 ) return 0.0;
            return floatval( $a ) / floatval( $b );
        }
        return null;
    }

    private static function check() {
        check_ajax_referer( 'yzmf_nonce', 'nonce' );
        if ( ! current_user_can( 'upload_files' ) ) wp_die( 'Forbidden', 403 );
    }

    // ── FOLDER CRUD ───────────────────────────────────────────────

    public static function yzmf_get_tree() {
        self::check();
        wp_send_json_success( YZMF_Taxonomy::get_tree() );
    }

    public static function yzmf_create_folder() {
        self::check();
        $name   = sanitize_text_field( $_POST['name']   ?? '' );
        $parent = intval( $_POST['parent'] ?? 0 );
        if ( ! $name ) wp_send_json_error( [ 'message' => 'Nombre requerido' ] );
        $r = wp_insert_term( $name, YZMF_TAXONOMY, [ 'parent' => $parent ] );
        if ( is_wp_error( $r ) ) wp_send_json_error( [ 'message' => $r->get_error_message() ] );
        $t = get_term( $r['term_id'], YZMF_TAXONOMY );
        wp_send_json_success( [
            'id' => $t->term_id, 'name' => $t->name,
            'parent' => $t->parent, 'count' => 0, 'children' => [],
        ] );
    }

    public static function yzmf_rename_folder() {
        self::check();
        $id   = intval( $_POST['id']   ?? 0 );
        $name = sanitize_text_field( $_POST['name'] ?? '' );
        if ( ! $id || ! $name ) wp_send_json_error();
        $r = wp_update_term( $id, YZMF_TAXONOMY, [ 'name' => $name ] );
        is_wp_error( $r )
            ? wp_send_json_error( [ 'message' => $r->get_error_message() ] )
            : wp_send_json_success( [ 'id' => $id, 'name' => $name ] );
    }

    public static function yzmf_delete_folder() {
        self::check();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        // Quitar carpeta de todos sus adjuntos
        $atts = get_posts( [
            'post_type' => 'attachment', 'posts_per_page' => -1, 'fields' => 'ids',
            'tax_query' => [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $id ] ],
        ] );
        foreach ( $atts as $att ) wp_remove_object_terms( $att, $id, YZMF_TAXONOMY );
        $r = wp_delete_term( $id, YZMF_TAXONOMY );
        is_wp_error( $r ) ? wp_send_json_error() : wp_send_json_success();
    }

    // ── IMAGES ────────────────────────────────────────────────────

    public static function yzmf_get_images() {
        self::check();
        $folder  = intval( $_POST['folder']   ?? -1 );
        $paged   = max( 1, intval( $_POST['paged']    ?? 1 ) );
        $perpage = min( 100, max( 1, intval( $_POST['per_page'] ?? 40 ) ) );
        $search  = sanitize_text_field( $_POST['search']  ?? '' );
        $orderby = sanitize_key( $_POST['orderby'] ?? 'date' );
        $order   = strtoupper( $_POST['order']   ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        $mime    = sanitize_text_field( $_POST['mime'] ?? '' );
        $tag     = sanitize_text_field( $_POST['tag'] ?? '' );  // filtro por tag IA
        $color   = sanitize_text_field( $_POST['color'] ?? '' ); // filtro por color hex (#RRGGBB)

        $allowed = [ 'date', 'title', 'size' ];
        if ( ! in_array( $orderby, $allowed ) ) $orderby = 'date';

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
        // Filtros especiales en search
        $meta_q = [];
        if ( $search === '__NO_ALT__' ) {
            // Imágenes sin alt text
            $meta_q[] = [
                'relation' => 'OR',
                [ 'key' => '_wp_attachment_image_alt', 'compare' => 'NOT EXISTS' ],
                [ 'key' => '_wp_attachment_image_alt', 'value' => '', 'compare' => '=' ],
            ];
            $search = '';
        }
        if ( $search ) $args['s'] = $search;
        // Si se filtra por mime, aplica ese tipo. Si no, pasamos la lista
        // explícita de tipos soportados para forzar la cláusula WHERE
        // post_mime_type — ver nota en YZMF_REST::list_media().
        $mime_map = [ 'image' => 'image/', 'video' => 'video/', 'pdf' => 'application/pdf', 'audio' => 'audio/' ];
        if ( $mime && isset( $mime_map[ $mime ] ) ) {
            $args['post_mime_type'] = $mime_map[ $mime ];
        } else {
            $args['post_mime_type'] = array_values( $mime_map );
        }

        // Filtro por tag IA (LIKE sobre el meta serializado)
        if ( $tag ) {
            $meta_q[] = [ 'key' => '_yzmf_ai_tags', 'value' => '"' . $tag . '"', 'compare' => 'LIKE' ];
        }

        // Filtro por color dominante (LIKE sobre meta de paleta)
        if ( $color && preg_match( '/^#?[0-9A-Fa-f]{6}$/', $color ) ) {
            $hex = strtoupper( ltrim( $color, '#' ) );
            $meta_q[] = [ 'key' => '_yzmf_color_palette', 'value' => $hex, 'compare' => 'LIKE' ];
        }

        if ( ! empty( $meta_q ) ) {
            $meta_q['relation'] = 'AND';
            $args['meta_query'] = isset( $args['meta_query'] )
                ? array_merge( $args['meta_query'], $meta_q )
                : $meta_q;
        }

        if ( $folder === 0 ) {
            $args['tax_query'] = [ [ 'taxonomy' => YZMF_TAXONOMY, 'operator' => 'NOT EXISTS' ] ];
        } elseif ( $folder > 0 ) {
            $args['tax_query'] = [ [ 'taxonomy' => YZMF_TAXONOMY, 'field' => 'term_id', 'terms' => $folder, 'include_children' => true ] ];
        }

        $q = new WP_Query( $args );
        $ids = wp_list_pluck( $q->posts, 'ID' );
        if ( ! empty( $ids ) ) {
            update_meta_cache( 'post', $ids );
            update_object_term_cache( $ids, 'attachment' );
        }
        wp_send_json_success( [
            'images'  => array_map( [ __CLASS__, 'format_image' ], $q->posts ),
            'total'   => $q->found_posts,
            'pages'   => (int) $q->max_num_pages,
            'current' => $paged,
        ] );
    }

    public static function format_image( $att ) {
        if ( is_int( $att ) ) $att = get_post( $att );
        $thumb = wp_get_attachment_image_src( $att->ID, 'thumbnail' );
        $med   = wp_get_attachment_image_src( $att->ID, 'medium_large' );
        // 'large' (1024x1024 WP por defecto) para pantallas grandes en la PWA.
        // Si la imagen es más pequeña que 'large', WP devuelve el original.
        $large = wp_get_attachment_image_src( $att->ID, 'large' );
        $full  = wp_get_attachment_image_src( $att->ID, 'full' );
        $meta  = wp_get_attachment_metadata( $att->ID ) ?: [];
        $path  = get_attached_file( $att->ID );
        $size  = ( $path && file_exists( $path ) ) ? filesize( $path ) : 0;
        $terms = wp_get_object_terms( $att->ID, YZMF_TAXONOMY, [ 'fields' => 'ids' ] );

        $exif = [];
        if ( ! empty( $meta['image_meta'] ) ) {
            $im = $meta['image_meta'];
            if ( ! empty( $im['camera'] ) )        $exif['Cámara']    = $im['camera'];
            if ( ! empty( $im['focal_length'] ) )  $exif['Focal']     = $im['focal_length'] . 'mm';
            if ( ! empty( $im['aperture'] ) )      $exif['Apertura']  = 'ƒ/' . $im['aperture'];
            if ( ! empty( $im['shutter_speed'] ) ) {
                $s = floatval( $im['shutter_speed'] );
                $exif['Velocidad'] = $s < 1 ? '1/' . round( 1 / $s ) . 's' : round( $s, 1 ) . 's';
            }
            if ( ! empty( $im['iso'] ) )           $exif['ISO']       = 'ISO ' . $im['iso'];
        }

        // Geo (lat/lng asignada manualmente o desde EXIF)
        $geo_lat = get_post_meta( $att->ID, '_yzmf_geo_lat', true );
        $geo_lng = get_post_meta( $att->ID, '_yzmf_geo_lng', true );
        $geo     = ( $geo_lat !== '' && $geo_lng !== '' ) ? [
            'lat'    => (float) $geo_lat,
            'lng'    => (float) $geo_lng,
            'place'  => get_post_meta( $att->ID, '_yzmf_geo_place', true ) ?: '',
            'source' => get_post_meta( $att->ID, '_yzmf_geo_source', true ) ?: 'manual',
        ] : null;

        $ai_tags = get_post_meta( $att->ID, '_yzmf_ai_tags', true );
        if ( ! is_array( $ai_tags ) ) $ai_tags = [];

        return [
            'id'         => $att->ID,
            'title'      => $att->post_title,
            'filename'   => basename( $path ?: '' ),
            'mime'       => $att->post_mime_type,
            'thumb'      => $thumb  ? $thumb[0]  : '',           // 150x150
            'medium'     => $med    ? $med[0]    : '',           // ~768
            'large'      => $large  ? $large[0]  : '',           // ~1024
            'full'       => $full   ? $full[0]   : wp_get_attachment_url( $att->ID ), // tamaño original
            'url'        => wp_get_attachment_url( $att->ID ),   // tamaño original (alias)
            'width'      => (int) ( $meta['width']  ?? 0 ),
            'height'     => (int) ( $meta['height'] ?? 0 ),
            'filesize'   => $size,
            'filesize_h' => $size ? size_format( $size ) : '—',
            'date'       => get_the_date( 'd/m/Y', $att ),
            'alt'        => get_post_meta( $att->ID, '_wp_attachment_image_alt', true ) ?: '',
            'seo_title'  => get_post_meta( $att->ID, '_yzmf_seo_title', true ) ?: '',
            'caption'    => $att->post_excerpt,
            'description'=> $att->post_content,
            'folder_ids' => is_wp_error( $terms ) ? [] : $terms,
            'exif'       => $exif,
            'geo'        => $geo,
            'tags'       => $ai_tags,
            'edit_url'   => get_edit_post_link( $att->ID, 'raw' ),
        ];
    }

    public static function yzmf_assign_images() {
        self::check();
        $folder_id = intval( $_POST['folder_id'] ?? 0 );
        $image_ids = array_map( 'intval', (array) ( $_POST['image_ids'] ?? [] ) );
        if ( empty( $image_ids ) ) wp_send_json_error();
        foreach ( $image_ids as $id ) {
            $folder_id > 0
                ? wp_set_object_terms( $id, [ $folder_id ], YZMF_TAXONOMY )
                : wp_delete_object_term_relationships( $id, YZMF_TAXONOMY );
        }
        wp_send_json_success( [ 'assigned' => count( $image_ids ) ] );
    }

    public static function yzmf_copy_images() {
        self::check();
        $folder_id = intval( $_POST['folder_id'] ?? 0 );
        $image_ids = array_map( 'intval', (array) ( $_POST['image_ids'] ?? [] ) );
        if ( ! $folder_id || empty( $image_ids ) ) wp_send_json_error();
        foreach ( $image_ids as $id ) {
            wp_set_object_terms( $id, [ $folder_id ], YZMF_TAXONOMY, true );
        }
        wp_send_json_success( [ 'copied' => count( $image_ids ) ] );
    }

    public static function yzmf_remove_from_folder() {
        self::check();
        $folder_id = intval( $_POST['folder_id'] ?? 0 );
        $image_id  = intval( $_POST['image_id']  ?? 0 );
        if ( ! $folder_id || ! $image_id ) wp_send_json_error();
        wp_remove_object_terms( $image_id, $folder_id, YZMF_TAXONOMY );
        wp_send_json_success();
    }

    public static function yzmf_delete_images() {
        self::check();
        if ( ! current_user_can( 'delete_posts' ) ) wp_send_json_error( [ 'message' => 'Sin permisos' ] );
        $ids     = array_map( 'intval', (array) ( $_POST['image_ids'] ?? [] ) );
        $deleted = 0;
        foreach ( $ids as $id ) { if ( wp_delete_attachment( $id, false ) ) $deleted++; }
        wp_send_json_success( [ 'deleted' => $deleted ] );
    }

    public static function yzmf_get_image_detail() {
        self::check();
        $id  = intval( $_POST['id'] ?? 0 );
        $att = get_post( $id );
        if ( ! $att || $att->post_type !== 'attachment' ) wp_send_json_error();
        wp_send_json_success( self::format_image( $att ) );
    }

    public static function yzmf_save_image_meta() {
        self::check();
        $id = intval( $_POST['id'] ?? 0 );
        if ( ! $id ) wp_send_json_error();
        if ( isset( $_POST['alt'] ) )         update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $_POST['alt'] ) );
        if ( isset( $_POST['seo_title'] ) )   update_post_meta( $id, '_yzmf_seo_title',          sanitize_text_field( $_POST['seo_title'] ) );
        $pd = [ 'ID' => $id ];
        if ( isset( $_POST['title'] ) )       $pd['post_title']   = sanitize_text_field( $_POST['title'] );
        if ( isset( $_POST['caption'] ) )     $pd['post_excerpt'] = sanitize_textarea_field( $_POST['caption'] );
        if ( isset( $_POST['description'] ) ) $pd['post_content'] = wp_kses_post( $_POST['description'] );
        if ( count( $pd ) > 1 )               wp_update_post( $pd );
        wp_send_json_success();
    }

    public static function yzmf_get_used_in() {
        self::check();
        $id      = intval( $_POST['id'] ?? 0 );
        $results = [];
        $seen    = [];

        $public_types = array_values( array_diff(
            array_keys( get_post_types( [ 'public' => true ], 'names' ) ),
            [ 'attachment' ]
        ) );

        $featured = get_posts( [
            'post_type'   => $public_types,
            'numberposts' => 10,
            'post_status' => 'publish',
            'meta_query'  => [ [ 'key' => '_thumbnail_id', 'value' => $id ] ],
        ] );
        foreach ( $featured as $p ) {
            $seen[ $p->ID ] = 1;
            $results[] = [ 'id' => $p->ID, 'title' => $p->post_title, 'type' => $p->post_type, 'url' => get_permalink( $p->ID ), 'edit' => get_edit_post_link( $p->ID, 'raw' ), 'via' => 'Imagen destacada' ];
        }

        $url = wp_get_attachment_url( $id );
        if ( $url ) {
            global $wpdb;
            $like        = '%' . $wpdb->esc_like( basename( $url ) ) . '%';
            $types_in    = "'" . implode( "','", array_map( 'esc_sql', $public_types ) ) . "'";
            $posts = $wpdb->get_results( $wpdb->prepare(
                "SELECT ID, post_title, post_type FROM {$wpdb->posts}
                 WHERE post_content LIKE %s
                   AND post_status='publish'
                   AND post_type IN ($types_in)
                 LIMIT 15",
                $like
            ) );
            foreach ( $posts as $p ) {
                if ( isset( $seen[ $p->ID ] ) ) continue;
                $seen[ $p->ID ] = 1;
                $results[] = [ 'id' => $p->ID, 'title' => $p->post_title, 'type' => $p->post_type, 'url' => get_permalink( $p->ID ), 'edit' => get_edit_post_link( $p->ID, 'raw' ), 'via' => 'Contenido' ];
            }
        }
        wp_send_json_success( $results );
    }

    public static function yzmf_regen_thumbnails() {
        self::check();
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Sin permisos' ] );
        $id   = intval( $_POST['id'] ?? 0 );
        $path = get_attached_file( $id );
        if ( ! $id || ! $path || ! file_exists( $path ) ) wp_send_json_error( [ 'message' => 'Archivo no encontrado' ] );
        $meta = wp_generate_attachment_metadata( $id, $path );
        if ( is_wp_error( $meta ) ) wp_send_json_error( [ 'message' => $meta->get_error_message() ] );
        wp_update_attachment_metadata( $id, $meta );
        wp_send_json_success( [ 'thumb' => wp_get_attachment_image_url( $id, 'thumbnail' ) ] );
    }

    public static function assign_on_upload( $attachment_id ) {
        if ( ! current_user_can( 'upload_files' ) ) return;
        $folder_id = intval( $_POST['yzmf_folder'] ?? 0 );
        if ( $folder_id <= 0 ) return;
        if ( ! get_term( $folder_id, YZMF_TAXONOMY ) ) return;
        wp_set_object_terms( $attachment_id, [ $folder_id ], YZMF_TAXONOMY );
    }

    // ── GENERAR ALT + CAPTION CON IA ─────────────────────────────
    public static function yzmf_generate_ai_meta() {
        self::check();
        $image_id = intval( $_POST['image_id'] ?? 0 );
        $r = self::generate_ai_for_image( $image_id );
        if ( $r['success'] ) wp_send_json_success( $r['data'] );
        wp_send_json_error( $r['data'] );
    }

    /**
     * Lógica central de generación de alt+caption con Claude.
     * Reusable desde AJAX y REST. Devuelve [ 'success' => bool, 'data' => array ].
     */
    public static function generate_ai_for_image( $image_id ) {
        $image_id = intval( $image_id );
        if ( ! $image_id ) return [ 'success' => false, 'data' => [ 'message' => 'ID de imagen requerido' ] ];

        $api_key = get_option( 'yzmf_claude_api_key', '' );
        if ( empty( $api_key ) ) {
            return [ 'success' => false, 'data' => [ 'message' => 'API key de Claude no configurada. Ve a Ajustes del plugin.' ] ];
        }

        $send_mode = get_option( 'yzmf_ai_send_mode', 'url' );
        $img_url   = wp_get_attachment_image_url( $image_id, 'medium_large' );
        if ( ! $img_url ) $img_url = wp_get_attachment_url( $image_id );
        if ( ! $img_url ) return [ 'success' => false, 'data' => [ 'message' => 'No se pudo obtener la URL de la imagen' ] ];

        if ( $send_mode === 'base64' ) {
            $img_source = self::build_base64_source( $image_id );
            if ( ! $img_source ) return [ 'success' => false, 'data' => [ 'message' => 'No se pudo leer el archivo en disco para enviarlo a la IA.' ] ];
        } else {
            $img_source = [ 'type' => 'url', 'url' => $img_url ];
        }

        $title    = get_the_title( $image_id );
        $meta     = wp_get_attachment_metadata( $image_id ) ?: [];
        $exif_ctx = '';
        if ( ! empty( $meta['image_meta'] ) ) {
            $im   = $meta['image_meta'];
            $bits = [];
            if ( ! empty( $im['camera'] ) )        $bits[] = 'Cámara: ' . $im['camera'];
            if ( ! empty( $im['focal_length'] ) )  $bits[] = 'Focal: ' . $im['focal_length'] . 'mm';
            if ( ! empty( $im['aperture'] ) )      $bits[] = 'Apertura: f/' . $im['aperture'];
            if ( ! empty( $im['shutter_speed'] ) ) {
                $s = floatval( $im['shutter_speed'] );
                $bits[] = 'Velocidad: ' . ( $s < 1 ? '1/' . round( 1 / $s ) . 's' : $s . 's' );
            }
            if ( ! empty( $im['iso'] ) ) $bits[] = 'ISO: ' . $im['iso'];
            if ( $bits ) $exif_ctx = ' Datos técnicos: ' . implode( ', ', $bits ) . '.';
        }

        $prompt = "Eres un experto en SEO fotográfico y accesibilidad web. Analiza esta fotografía y genera en español:\n\n"
            . "1. ALT TEXT: Descripción concisa (máx 125 caracteres) para el atributo alt de HTML. "
            . "Debe describir objetivamente lo que se ve, ser específico y útil para lectores de pantalla. "
            . "No empieces con \"Imagen de\" o \"Foto de\".\n\n"
            . "2. CAPTION: Pie de foto breve y evocador (máx 160 caracteres) con tono fotográfico. "
            . "Puede incluir contexto, lugar o sensación.\n\n"
            . "3. TAGS: 3 a 6 etiquetas en español, en minúsculas, una palabra cada una, "
            . "del repertorio: paisaje, retrato, urbano, naturaleza, arquitectura, calle, viaje, "
            . "interior, monocromo, color, atardecer, noche, día, agua, montaña, bosque, ciudad, "
            . "evento, abstracto, macro, animal, persona, objeto, vehículo, comida, deporte, arte. "
            . "Si aplica, añade tu propia etiqueta breve (1-2 palabras).\n\n"
            . "Título del archivo: " . $title . '.' . $exif_ctx . "\n\n"
            . "Responde ÚNICAMENTE en este formato JSON exacto, sin texto adicional:\n"
            . '{"alt":"...","caption":"...","tags":["...","..."]}';

        $model = get_option( 'yzmf_ai_model', 'claude-haiku-4-5-20251001' );

        $call_claude = function( $source ) use ( $api_key, $model, $prompt ) {
            return wp_remote_post( 'https://api.anthropic.com/v1/messages', [
                'timeout' => 30,
                'headers' => [
                    'Content-Type'      => 'application/json',
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                ],
                'body' => wp_json_encode( [
                    'model'      => $model,
                    'max_tokens' => 350,  // ampliado para incluir tags
                    'messages'   => [ [
                        'role'    => 'user',
                        'content' => [
                            [ 'type' => 'image', 'source' => $source ],
                            [ 'type' => 'text',  'text'   => $prompt ],
                        ],
                    ] ],
                ] ),
            ] );
        };

        $response = $call_claude( $img_source );
        if ( is_wp_error( $response ) ) return [ 'success' => false, 'data' => [ 'message' => 'Error de conexión: ' . $response->get_error_message() ] ];

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        // Fallback automático url → base64 si Claude no pudo descargar la imagen
        if ( $code !== 200 && $send_mode === 'url' ) {
            $err_msg = strtolower( $body['error']['message'] ?? '' );
            if ( strpos( $err_msg, 'fetch' ) !== false || strpos( $err_msg, 'download' ) !== false || strpos( $err_msg, 'access' ) !== false ) {
                $b64 = self::build_base64_source( $image_id );
                if ( $b64 ) {
                    $response = $call_claude( $b64 );
                    if ( is_wp_error( $response ) ) return [ 'success' => false, 'data' => [ 'message' => 'Error de conexión: ' . $response->get_error_message() ] ];
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    $code = wp_remote_retrieve_response_code( $response );
                }
            }
        }

        if ( $code !== 200 ) {
            return [ 'success' => false, 'data' => [ 'message' => $body['error']['message'] ?? ( 'Error de la API (código ' . $code . ')' ) ] ];
        }

        $text = $body['content'][0]['text'] ?? '';
        $text = trim( preg_replace( '/^```json|```$/m', '', $text ) );

        $parsed = json_decode( $text, true );
        if ( ! $parsed || empty( $parsed['alt'] ) ) {
            return [ 'success' => false, 'data' => [ 'message' => 'La IA no devolvió un formato válido. Respuesta: ' . substr( $text, 0, 200 ) ] ];
        }

        $alt     = sanitize_text_field( $parsed['alt']     ?? '' );
        $caption = sanitize_text_field( $parsed['caption'] ?? '' );
        $tags    = is_array( $parsed['tags'] ?? null ) ? $parsed['tags'] : [];
        $tags    = array_values( array_filter( array_map( function( $t ) {
            return sanitize_text_field( strtolower( trim( (string) $t ) ) );
        }, $tags ), function( $t ) { return $t !== ''; } ) );

        if ( $alt )     update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );
        if ( $caption ) wp_update_post( [ 'ID' => $image_id, 'post_excerpt' => $caption ] );
        if ( $tags )    update_post_meta( $image_id, '_yzmf_ai_tags', $tags );

        return [ 'success' => true, 'data' => [
            'image_id' => $image_id,
            'alt'      => $alt,
            'caption'  => $caption,
            'tags'     => $tags,
        ] ];
    }

    /**
     * Backfill: rellena `_yzmf_filesize` para attachments antiguos.
     * Procesa por lotes para evitar timeouts. El cliente lo llama en bucle pasando $offset.
     */
    public static function yzmf_backfill_filesizes() {
        self::check();
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Sin permisos' ] );

        $offset = max( 0, intval( $_POST['offset'] ?? 0 ) );
        $batch  = 50;

        // Total bruto de attachments tipo imagen sin filesize calculado
        global $wpdb;
        $total = (int) $wpdb->get_var( "
            SELECT COUNT(*) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_yzmf_filesize'
            WHERE p.post_type = 'attachment' AND pm.meta_id IS NULL
        " );

        // Coger un lote de IDs sin filesize
        $ids = $wpdb->get_col( $wpdb->prepare( "
            SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_yzmf_filesize'
            WHERE p.post_type = 'attachment' AND pm.meta_id IS NULL
            ORDER BY p.ID ASC
            LIMIT %d
        ", $batch ) );

        $processed = 0;
        $errors    = 0;
        foreach ( $ids as $id ) {
            $path = get_attached_file( $id );
            if ( $path && file_exists( $path ) ) {
                update_post_meta( $id, '_yzmf_filesize', filesize( $path ) );
                $processed++;
            } else {
                // Marcamos con 0 para no reintentar archivos rotos en futuras pasadas
                update_post_meta( $id, '_yzmf_filesize', 0 );
                $errors++;
            }
        }

        wp_send_json_success( [
            'processed' => $processed,
            'errors'    => $errors,
            'remaining' => max( 0, $total - $processed - $errors ),
            'done'      => empty( $ids ),
        ] );
    }

    /**
     * Construye un source `base64` para Claude con la imagen en disco.
     * Devuelve null si no se puede leer.
     */
    private static function build_base64_source( $image_id ) {
        $path = get_attached_file( $image_id );
        if ( ! $path || ! file_exists( $path ) ) return null;

        // Intentar usar la versión 'medium_large' físicamente para no enviar el original (que puede pesar mucho)
        $meta = wp_get_attachment_metadata( $image_id );
        if ( ! empty( $meta['sizes']['medium_large']['file'] ) ) {
            $sized = dirname( $path ) . DIRECTORY_SEPARATOR . $meta['sizes']['medium_large']['file'];
            if ( file_exists( $sized ) ) $path = $sized;
        }

        $mime = get_post_mime_type( $image_id );
        if ( ! in_array( $mime, [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ], true ) ) return null;

        $data = @file_get_contents( $path );
        if ( $data === false ) return null;

        return [
            'type'       => 'base64',
            'media_type' => $mime,
            'data'       => base64_encode( $data ),
        ];
    }

}
