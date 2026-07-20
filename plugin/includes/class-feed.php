<?php
/**
 * CPT yzmf_feed_post + taxonomía yzmf_hashtag: feed de fotografía tipo
 * Instagram (varias fotos por publicación, texto y hashtags navegables).
 *
 * Modelo de datos (sin tablas propias):
 *   - Caption          → post_content
 *   - Estado           → post_status (publish | draft)
 *   - Fecha            → post_date
 *   - Fotos ordenadas  → meta _yzmf_feed_photos (JSON con array de attachment IDs)
 *   - Portada derivada → meta _yzmf_feed_cover (primer attachment, para listados)
 *   - Hashtags         → términos de la taxonomía yzmf_hashtag
 *   - Likes            → meta _yzmf_feed_likes (contador) + _yzmf_feed_liked_by
 *                        (hashes de huella para deduplicar sin login)
 *   - Comentarios      → sistema nativo de WordPress (cola de moderación)
 *
 * La gestión externa (PWA, shortcode público) usa los helpers estáticos de
 * esta clase. Serializa las fotos con YZMF_Ajax::format_image().
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Feed {

	const POST_TYPE   = 'yzmf_feed_post';
	const TAX         = 'yzmf_hashtag';
	const META_PHOTOS = '_yzmf_feed_photos';
	const META_COVER  = '_yzmf_feed_cover';
	const META_LIKES  = '_yzmf_feed_likes';
	const META_LIKED  = '_yzmf_feed_liked_by';

	/** Máximo de huellas guardadas para deduplicar likes (evita meta gigante). */
	const LIKED_CAP = 5000;

	/** Transient del feed público (se invalida en cada mutación). */
	const CACHE_KEY = 'yzmf_feed_public_cache';
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	public static function register() {
		register_post_type( self::POST_TYPE, [
			'labels'          => [
				'name'          => __( 'Publicaciones', 'yz-media-folders' ),
				'singular_name' => __( 'Publicación',   'yz-media-folders' ),
			],
			'public'          => false,   // privado: se expone por REST/shortcode propios
			'show_ui'         => false,   // gestionado desde la PWA
			'show_in_menu'    => false,
			'show_in_rest'    => false,   // REST propio en yzmf/v1
			'has_archive'     => false,
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'supports'        => [ 'title', 'editor', 'comments' ],
		] );

		register_taxonomy( self::TAX, self::POST_TYPE, [
			'labels'       => [
				'name'          => __( 'Hashtags', 'yz-media-folders' ),
				'singular_name' => __( 'Hashtag',  'yz-media-folders' ),
			],
			'hierarchical' => false,   // etiquetas planas, como los tags de WP
			'public'       => false,
			'show_ui'      => false,
			'show_in_rest' => true,
			'rewrite'      => false,
		] );
	}

	/* ─────────── Fotos ─────────── */

	/** Array ordenado de attachment IDs (ints) de una publicación. */
	public static function get_photos( $id ) {
		$raw = get_post_meta( $id, self::META_PHOTOS, true );
		$ids = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
		if ( ! is_array( $ids ) ) return [];
		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/** Guarda el array de attachment IDs y cachea la portada (primera foto). */
	public static function set_photos( $id, $ids ) {
		$clean = array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
		update_post_meta( $id, self::META_PHOTOS, wp_slash( wp_json_encode( $clean ) ) );
		if ( ! empty( $clean[0] ) ) {
			update_post_meta( $id, self::META_COVER, (int) $clean[0] );
		} else {
			delete_post_meta( $id, self::META_COVER );
		}
		return $clean;
	}

	public static function get_cover_id( $id ) {
		$cover = (int) get_post_meta( $id, self::META_COVER, true );
		if ( $cover ) return $cover;
		$photos = self::get_photos( $id );
		return $photos[0] ?? 0;
	}

	/* ─────────── Hashtags ─────────── */

	/**
	 * Asigna hashtags a la publicación. Combina las etiquetas explícitas
	 * (chips de la PWA) con las que aparezcan como #palabra dentro del texto.
	 */
	public static function set_hashtags( $id, $tags = [], $caption = '' ) {
		$names = [];
		foreach ( (array) $tags as $t ) {
			$n = self::normalize_hashtag( $t );
			if ( $n !== '' ) $names[] = $n;
		}
		foreach ( self::parse_hashtags_from_caption( $caption ) as $n ) {
			$names[] = $n;
		}
		$names = array_values( array_unique( $names ) );
		wp_set_object_terms( $id, $names, self::TAX, false );
		return $names;
	}

	/** Extrae tokens #palabra del texto (soporta acentos y ñ). */
	public static function parse_hashtags_from_caption( $caption ) {
		if ( ! is_string( $caption ) || $caption === '' ) return [];
		if ( ! preg_match_all( '/#([\p{L}\p{N}_]+)/u', $caption, $m ) ) return [];
		$out = [];
		foreach ( $m[1] as $raw ) {
			$n = self::normalize_hashtag( $raw );
			if ( $n !== '' ) $out[] = $n;
		}
		return array_values( array_unique( $out ) );
	}

	/** Normaliza un hashtag: sin #, minúsculas, solo letras/números/_/-. */
	public static function normalize_hashtag( $tag ) {
		$tag = trim( (string) $tag );
		$tag = ltrim( $tag, '#' );
		$tag = mb_strtolower( $tag );
		$tag = preg_replace( '/[^\p{L}\p{N}_\-]+/u', '', $tag );
		return (string) $tag;
	}

	/** Nombres de los hashtags de una publicación. */
	public static function get_hashtags( $id ) {
		$terms = wp_get_object_terms( $id, self::TAX, [ 'fields' => 'names' ] );
		return is_wp_error( $terms ) ? [] : $terms;
	}

	/** Todos los hashtags con recuento (para chips/autocompletar). */
	public static function all_hashtags() {
		$terms = get_terms( [
			'taxonomy'   => self::TAX,
			'hide_empty' => false,
			'orderby'    => 'count',
			'order'      => 'DESC',
		] );
		if ( is_wp_error( $terms ) ) return [];
		$out = [];
		foreach ( $terms as $t ) {
			$out[] = [
				'tag'   => $t->name,
				'slug'  => $t->slug,
				'count' => (int) $t->count,
			];
		}
		return $out;
	}

	/* ─────────── Likes ─────────── */

	/**
	 * Alterna el like de una huella (IP + token de cliente). Devuelve
	 * [ 'likes' => int, 'liked' => bool ]. Deduplica para que un mismo
	 * visitante no infle el contador.
	 */
	public static function toggle_like( $id, $fingerprint ) {
		$hash  = self::fingerprint_hash( $fingerprint );
		$liked = get_post_meta( $id, self::META_LIKED, true );
		if ( ! is_array( $liked ) ) $liked = [];

		$pos = array_search( $hash, $liked, true );
		if ( $pos !== false ) {
			unset( $liked[ $pos ] );
			$liked = array_values( $liked );
			$now_liked = false;
		} else {
			$liked[] = $hash;
			// Cap: descarta las huellas más antiguas si crece demasiado.
			if ( count( $liked ) > self::LIKED_CAP ) {
				$liked = array_slice( $liked, -self::LIKED_CAP );
			}
			$now_liked = true;
		}

		update_post_meta( $id, self::META_LIKED, $liked );
		$count = count( $liked );
		update_post_meta( $id, self::META_LIKES, $count );

		return [ 'likes' => $count, 'liked' => $now_liked ];
	}

	public static function get_likes( $id ) {
		return (int) get_post_meta( $id, self::META_LIKES, true );
	}

	private static function fingerprint_hash( $fingerprint ) {
		return wp_hash( 'yzmf_feed_like|' . (string) $fingerprint );
	}

	/* ─────────── Serialización ─────────── */

	/** Resumen para listados (portada + contadores, sin todas las fotos). */
	public static function summary( $id ) {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) return null;

		$cover_id = self::get_cover_id( $id );
		$cover    = $cover_id ? YZMF_Ajax::format_image( $cover_id, 'list' ) : null;
		$photos   = self::get_photos( $id );

		return [
			'id'            => $id,
			'title'         => $post->post_title,
			'caption'       => $post->post_content,
			'status'        => $post->post_status,
			'date'          => mysql_to_rfc3339( $post->post_date_gmt ),
			'modified'      => mysql_to_rfc3339( $post->post_modified_gmt ),
			'cover'         => $cover,
			'photos_count'  => count( $photos ),
			'hashtags'      => self::get_hashtags( $id ),
			'likes'         => self::get_likes( $id ),
			'comment_count' => (int) get_comments_number( $id ),
		];
	}

	/** Detalle completo con todas las fotos serializadas. */
	public static function full( $id ) {
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== self::POST_TYPE ) return null;

		$photos = [];
		foreach ( self::get_photos( $id ) as $att_id ) {
			if ( get_post( $att_id ) ) {
				$photos[] = YZMF_Ajax::format_image( $att_id, 'detail' );
			}
		}

		return [
			'id'            => $id,
			'title'         => $post->post_title,
			'caption'       => $post->post_content,
			'status'        => $post->post_status,
			'date'          => mysql_to_rfc3339( $post->post_date_gmt ),
			'modified'      => mysql_to_rfc3339( $post->post_modified_gmt ),
			'photos'        => $photos,
			'hashtags'      => self::get_hashtags( $id ),
			'likes'         => self::get_likes( $id ),
			'comment_count' => (int) get_comments_number( $id ),
		];
	}

	/* ─────────── Caché pública ─────────── */

	public static function bust_cache() {
		global $wpdb;
		// Borra todas las variantes cacheadas (por hashtag/página).
		$like = $wpdb->esc_like( '_transient_' . self::CACHE_KEY ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		$like_t = $wpdb->esc_like( '_transient_timeout_' . self::CACHE_KEY ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like_t ) );
		do_action( 'litespeed_purge_all' );
	}
}
