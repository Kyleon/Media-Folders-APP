<?php
/**
 * Endpoints REST del módulo Feed (publicaciones tipo Instagram).
 * Namespace: yzmf/v1
 *
 * Rutas ADMIN (permission: upload_files, vía YZMF_REST::can_upload):
 *   GET    /feed                       → lista (resumen) con filtros
 *   POST   /feed                       → crear
 *   GET    /feed/hashtags              → [{ tag, slug, count }]
 *   GET    /feed/{id}                  → detalle (todas las fotos)
 *   PUT    /feed/{id}                  → actualizar
 *   DELETE /feed/{id}                  → borrar (?force=1)
 *   GET    /feed/{id}/comments         → moderación (?status=all|hold|approve|spam)
 *   PUT    /feed/comments/{cid}        → moderar (approve|unapprove|spam|trash)
 *   DELETE /feed/comments/{cid}        → borrar comentario
 *
 * Rutas PÚBLICAS (sin auth):
 *   GET    /feed/public                → publicaciones publicadas (resumen, cacheado)
 *   GET    /feed/public/{id}           → detalle público (fotos + comentarios aprobados)
 *   POST   /feed/{id}/like             → alterna like (dedup por huella)
 *   POST   /feed/{id}/comments         → nuevo comentario (cola de moderación nativa)
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Feed_REST {

	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	public static function register_routes() {
		$ns   = YZMF_REST::NS;
		$auth = [ 'YZMF_REST', 'can_upload' ];

		register_rest_route( $ns, '/feed', [
			[ 'methods' => 'GET',  'callback' => [ __CLASS__, 'list_feed' ],   'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'create_feed' ], 'permission_callback' => $auth ],
		] );

		register_rest_route( $ns, '/feed/hashtags', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'list_hashtags' ],
			'permission_callback' => $auth,
		] );

		// Feed público (cacheado). Se registra antes del comodín numérico,
		// aunque \d+ no capturaría "public" de todas formas.
		register_rest_route( $ns, '/feed/public', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'public_list' ],
			'permission_callback' => '__return_true',
		] );
		register_rest_route( $ns, '/feed/public/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ __CLASS__, 'public_detail' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( $ns, '/feed/(?P<id>\d+)', [
			[ 'methods' => 'GET',    'callback' => [ __CLASS__, 'get_feed' ],    'permission_callback' => $auth ],
			[ 'methods' => 'PUT',    'callback' => [ __CLASS__, 'update_feed' ], 'permission_callback' => $auth ],
			[ 'methods' => 'DELETE', 'callback' => [ __CLASS__, 'delete_feed' ], 'permission_callback' => $auth ],
		] );

		register_rest_route( $ns, '/feed/(?P<id>\d+)/like', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'toggle_like' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( $ns, '/feed/(?P<id>\d+)/comments', [
			[ 'methods' => 'GET',  'callback' => [ __CLASS__, 'list_comments' ],  'permission_callback' => $auth ],
			[ 'methods' => 'POST', 'callback' => [ __CLASS__, 'create_comment' ], 'permission_callback' => '__return_true' ],
		] );

		register_rest_route( $ns, '/feed/comments/(?P<cid>\d+)', [
			[ 'methods' => 'PUT',    'callback' => [ __CLASS__, 'moderate_comment' ], 'permission_callback' => $auth ],
			[ 'methods' => 'DELETE', 'callback' => [ __CLASS__, 'delete_comment' ],   'permission_callback' => $auth ],
		] );
	}

	/* ─────────── CRUD admin ─────────── */

	public static function list_feed( WP_REST_Request $req ) {
		$page     = max( 1, (int) $req->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) ( $req->get_param( 'per_page' ) ?: 20 ) ) );
		$search   = trim( (string) $req->get_param( 'search' ) );
		$hashtag  = trim( (string) $req->get_param( 'hashtag' ) );
		$status   = (string) $req->get_param( 'status' );

		$statuses = $status === 'publish' || $status === 'draft'
			? [ $status ]
			: [ 'publish', 'draft' ];

		$args = [
			'post_type'      => YZMF_Feed::POST_TYPE,
			'post_status'    => $statuses,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];
		if ( $search !== '' )  $args['s'] = $search;
		if ( $hashtag !== '' ) {
			$args['tax_query'] = [ [
				'taxonomy' => YZMF_Feed::TAX,
				'field'    => 'slug',
				'terms'    => sanitize_title( $hashtag ),
			] ];
		}

		$q     = new WP_Query( $args );
		$items = [];
		foreach ( $q->posts as $p ) {
			$items[] = YZMF_Feed::summary( $p->ID );
		}

		return rest_ensure_response( [
			'items' => $items,
			'total' => (int) $q->found_posts,
			'pages' => (int) $q->max_num_pages,
		] );
	}

	public static function create_feed( WP_REST_Request $req ) {
		$params  = self::params( $req );
		$caption = self::sanitize_caption( $params['caption'] ?? '' );
		$status  = self::sanitize_status( $params['status'] ?? 'draft' );
		$title   = self::derive_title( $params['title'] ?? '', $caption );

		$id = wp_insert_post( [
			'post_type'      => YZMF_Feed::POST_TYPE,
			'post_status'    => $status,
			'post_title'     => $title,
			'post_content'   => $caption,
			'comment_status' => 'open',
		], true );

		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'yzmf_feed_create_failed', $id->get_error_message(), [ 'status' => 500 ] );
		}

		if ( isset( $params['photos'] ) ) {
			YZMF_Feed::set_photos( $id, $params['photos'] );
		}
		YZMF_Feed::set_hashtags( $id, $params['hashtags'] ?? [], $caption );

		YZMF_Feed::bust_cache();
		return self::respond_full( $id, 201 );
	}

	public static function get_feed( WP_REST_Request $req ) {
		return self::respond_full( (int) $req['id'] );
	}

	public static function update_feed( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== YZMF_Feed::POST_TYPE ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}

		$params  = self::params( $req );
		$update  = [ 'ID' => $id ];
		$caption = null;

		if ( isset( $params['caption'] ) ) {
			$caption = self::sanitize_caption( $params['caption'] );
			$update['post_content'] = $caption;
		}
		if ( isset( $params['status'] ) ) {
			$update['post_status'] = self::sanitize_status( $params['status'] );
		}
		if ( isset( $params['title'] ) ) {
			$update['post_title'] = self::derive_title( $params['title'], $caption ?? $post->post_content );
		}
		if ( count( $update ) > 1 ) {
			wp_update_post( $update );
		}

		if ( isset( $params['photos'] ) ) {
			YZMF_Feed::set_photos( $id, $params['photos'] );
		}
		// Reasigna hashtags si llegan chips nuevos o cambia el texto (re-parseo #).
		if ( isset( $params['hashtags'] ) || $caption !== null ) {
			$effective_caption = $caption !== null ? $caption : $post->post_content;
			YZMF_Feed::set_hashtags( $id, $params['hashtags'] ?? YZMF_Feed::get_hashtags( $id ), $effective_caption );
		}

		YZMF_Feed::bust_cache();
		return self::respond_full( $id );
	}

	public static function delete_feed( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== YZMF_Feed::POST_TYPE ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}

		$force  = (bool) $req->get_param( 'force' );
		$result = $force ? wp_delete_post( $id, true ) : wp_trash_post( $id );
		if ( ! $result ) {
			return new WP_Error( 'yzmf_feed_delete_failed', 'No se pudo eliminar', [ 'status' => 500 ] );
		}

		YZMF_Feed::bust_cache();
		return rest_ensure_response( [ 'id' => $id, 'deleted' => true, 'force' => $force ] );
	}

	public static function list_hashtags( WP_REST_Request $req ) {
		return rest_ensure_response( YZMF_Feed::all_hashtags() );
	}

	/* ─────────── Público ─────────── */

	public static function public_list( WP_REST_Request $req ) {
		$page     = max( 1, (int) $req->get_param( 'page' ) );
		$per_page = min( 50, max( 1, (int) ( $req->get_param( 'per_page' ) ?: 12 ) ) );
		$hashtag  = trim( (string) $req->get_param( 'hashtag' ) );

		$cache_key = YZMF_Feed::CACHE_KEY . '_' . md5( $page . '|' . $per_page . '|' . $hashtag );
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$args = [
			'post_type'      => YZMF_Feed::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];
		if ( $hashtag !== '' ) {
			$args['tax_query'] = [ [
				'taxonomy' => YZMF_Feed::TAX,
				'field'    => 'slug',
				'terms'    => sanitize_title( $hashtag ),
			] ];
		}

		$q     = new WP_Query( $args );
		$items = [];
		foreach ( $q->posts as $p ) {
			$s = YZMF_Feed::summary( $p->ID );
			unset( $s['status'] ); // irrelevante en público
			$items[] = $s;
		}

		$payload = [
			'items' => $items,
			'total' => (int) $q->found_posts,
			'pages' => (int) $q->max_num_pages,
		];
		set_transient( $cache_key, $payload, YZMF_Feed::CACHE_TTL );
		return rest_ensure_response( $payload );
	}

	public static function public_detail( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== YZMF_Feed::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}

		$data = YZMF_Feed::full( $id );
		unset( $data['status'] );
		$data['comments'] = self::approved_comments( $id );
		return rest_ensure_response( $data );
	}

	public static function toggle_like( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== YZMF_Feed::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}

		$params = self::params( $req );
		$token  = substr( sanitize_text_field( $params['token'] ?? '' ), 0, 64 );
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$result = YZMF_Feed::toggle_like( $id, $ip . '|' . $token );

		return rest_ensure_response( $result );
	}

	/* ─────────── Comentarios ─────────── */

	public static function list_comments( WP_REST_Request $req ) {
		$id     = (int) $req['id'];
		$status = (string) $req->get_param( 'status' );
		$map    = [ 'approve' => 'approve', 'hold' => 'hold', 'spam' => 'spam', 'all' => 'all' ];
		$status = $map[ $status ] ?? 'all';

		$comments = get_comments( [
			'post_id' => $id,
			'status'  => $status,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		] );

		$out = array_map( [ __CLASS__, 'format_comment' ], $comments );
		return rest_ensure_response( $out );
	}

	public static function create_comment( WP_REST_Request $req ) {
		$id   = (int) $req['id'];
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== YZMF_Feed::POST_TYPE || $post->post_status !== 'publish' ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}
		if ( ! comments_open( $id ) ) {
			return new WP_Error( 'yzmf_feed_comments_closed', 'Comentarios cerrados', [ 'status' => 403 ] );
		}

		$params = self::params( $req );
		$data   = [
			'comment_post_ID' => $id,
			'author'          => sanitize_text_field( $params['author'] ?? '' ),
			'email'           => sanitize_email( $params['email'] ?? '' ),
			'url'             => '',
			'comment'         => trim( (string) ( $params['content'] ?? '' ) ),
		];
		if ( $data['comment'] === '' ) {
			return new WP_Error( 'yzmf_feed_empty_comment', 'El comentario está vacío', [ 'status' => 400 ] );
		}

		$comment = wp_handle_comment_submission( wp_slash( $data ) );
		if ( is_wp_error( $comment ) ) {
			$code = (int) ( $comment->get_error_data() ?: 400 );
			return new WP_Error( 'yzmf_feed_comment_failed', $comment->get_error_message(), [ 'status' => $code ?: 400 ] );
		}

		YZMF_Feed::bust_cache();
		$approved = (string) $comment->comment_approved === '1';
		return rest_ensure_response( [
			'id'         => (int) $comment->comment_ID,
			'approved'   => $approved,
			'moderation' => ! $approved,
			'message'    => $approved ? 'Comentario publicado' : 'Comentario enviado, pendiente de aprobación',
		] );
	}

	public static function moderate_comment( WP_REST_Request $req ) {
		$cid    = (int) $req['cid'];
		if ( ! get_comment( $cid ) ) {
			return new WP_Error( 'yzmf_comment_not_found', 'No existe', [ 'status' => 404 ] );
		}
		$params = self::params( $req );
		$action = (string) ( $params['action'] ?? '' );
		$map    = [ 'approve' => 'approve', 'unapprove' => 'hold', 'spam' => 'spam', 'trash' => 'trash' ];
		if ( ! isset( $map[ $action ] ) ) {
			return new WP_Error( 'yzmf_comment_bad_action', 'Acción inválida', [ 'status' => 400 ] );
		}

		wp_set_comment_status( $cid, $map[ $action ] );
		YZMF_Feed::bust_cache();
		return rest_ensure_response( self::format_comment( get_comment( $cid ) ) );
	}

	public static function delete_comment( WP_REST_Request $req ) {
		$cid = (int) $req['cid'];
		if ( ! get_comment( $cid ) ) {
			return new WP_Error( 'yzmf_comment_not_found', 'No existe', [ 'status' => 404 ] );
		}
		wp_delete_comment( $cid, true );
		YZMF_Feed::bust_cache();
		return rest_ensure_response( [ 'id' => $cid, 'deleted' => true ] );
	}

	/* ─────────── Helpers ─────────── */

	private static function respond_full( $id, $status = 200 ) {
		$data = YZMF_Feed::full( $id );
		if ( ! $data ) {
			return new WP_Error( 'yzmf_feed_not_found', 'No existe', [ 'status' => 404 ] );
		}
		$resp = rest_ensure_response( $data );
		$resp->set_status( $status );
		return $resp;
	}

	private static function approved_comments( $id ) {
		$comments = get_comments( [
			'post_id' => $id,
			'status'  => 'approve',
			'orderby' => 'comment_date_gmt',
			'order'   => 'ASC',
		] );
		return array_map( function ( $c ) {
			return [
				'id'      => (int) $c->comment_ID,
				'author'  => $c->comment_author,
				'content' => $c->comment_content,
				'date'    => mysql_to_rfc3339( $c->comment_date_gmt ),
			];
		}, $comments );
	}

	private static function format_comment( $c ) {
		$status = wp_get_comment_status( $c->comment_ID );
		return [
			'id'       => (int) $c->comment_ID,
			'post_id'  => (int) $c->comment_post_ID,
			'author'   => $c->comment_author,
			'email'    => $c->comment_author_email,
			'content'  => $c->comment_content,
			'date'     => mysql_to_rfc3339( $c->comment_date_gmt ),
			'status'   => $status,           // approved | unapproved | spam | trash
			'approved' => $status === 'approved',
		];
	}

	/** Devuelve params JSON o form, priorizando JSON. */
	private static function params( WP_REST_Request $req ) {
		$json = $req->get_json_params();
		if ( is_array( $json ) && ! empty( $json ) ) return $json;
		return $req->get_params();
	}

	private static function sanitize_caption( $text ) {
		return sanitize_textarea_field( (string) $text );
	}

	private static function sanitize_status( $status ) {
		return $status === 'publish' ? 'publish' : 'draft';
	}

	/** Título legible para el admin: el explícito, o un extracto del caption. */
	private static function derive_title( $explicit, $caption ) {
		$explicit = trim( (string) $explicit );
		if ( $explicit !== '' ) return sanitize_text_field( $explicit );
		$caption = trim( (string) $caption );
		if ( $caption !== '' ) {
			$short = wp_trim_words( $caption, 8, '…' );
			if ( $short !== '' ) return $short;
		}
		return __( 'Publicación', 'yz-media-folders' );
	}
}
