<?php
/**
 * Shortcode [yzmf_feed] — muestra el feed público (publicaciones tipo
 * Instagram) como una rejilla de portadas. Al pulsar una publicación se
 * abre un visor con carrusel, texto, hashtags, likes y comentarios.
 *
 * El HTML de la rejilla se renderiza en servidor (SEO + primera pintura).
 * El visor y las interacciones (like/comentar) las resuelve el JS contra
 * los endpoints públicos de yzmf/v1.
 *
 * Atributos:
 *   columns   nº de columnas en escritorio (por defecto 3)
 *   limit     nº de publicaciones a cargar (por defecto 12)
 *   hashtag   filtra por un hashtag concreto (slug o nombre)
 */

defined( 'ABSPATH' ) || exit;

class YZMF_Feed_Shortcode {

	const HANDLE_CSS = 'yzmf-feed';
	const HANDLE_JS  = 'yzmf-feed';

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcode' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	public static function register_shortcode() {
		add_shortcode( 'yzmf_feed', [ __CLASS__, 'render_shortcode' ] );
	}

	public static function register_assets() {
		wp_register_style(
			self::HANDLE_CSS,
			YZMF_URL . 'assets/css/yzmf-feed.css',
			[],
			YZMF_VERSION
		);
		wp_register_script(
			self::HANDLE_JS,
			YZMF_URL . 'assets/js/yzmf-feed.js',
			[],
			YZMF_VERSION,
			true
		);
		wp_localize_script( self::HANDLE_JS, 'yzmfFeed', [
			'rest' => esc_url_raw( rest_url( YZMF_REST::NS . '/' ) ),
			'i18n' => [
				'like'        => __( 'Me gusta', 'yz-media-folders' ),
				'comments'    => __( 'Comentarios', 'yz-media-folders' ),
				'namePh'      => __( 'Tu nombre', 'yz-media-folders' ),
				'emailPh'     => __( 'Tu email', 'yz-media-folders' ),
				'commentPh'   => __( 'Escribe un comentario…', 'yz-media-folders' ),
				'send'        => __( 'Enviar', 'yz-media-folders' ),
				'noComments'  => __( 'Sé el primero en comentar', 'yz-media-folders' ),
				'loading'     => __( 'Cargando…', 'yz-media-folders' ),
				'close'       => __( 'Cerrar', 'yz-media-folders' ),
				'prev'        => __( 'Anterior', 'yz-media-folders' ),
				'next'        => __( 'Siguiente', 'yz-media-folders' ),
			],
		] );
	}

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( [
			'columns' => 3,
			'limit'   => 12,
			'hashtag' => '',
		], $atts, 'yzmf_feed' );

		$columns = max( 1, min( 6, (int) $atts['columns'] ) );
		$limit   = max( 1, min( 50, (int) $atts['limit'] ) );
		$hashtag = trim( (string) $atts['hashtag'] );

		$args = [
			'post_type'      => YZMF_Feed::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
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

		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			return '<!-- yzmf_feed: sin publicaciones -->';
		}

		wp_enqueue_style( self::HANDLE_CSS );
		wp_enqueue_script( self::HANDLE_JS );

		$cards = '';
		foreach ( $q->posts as $p ) {
			$cards .= self::render_card( $p->ID );
		}

		return sprintf(
			'<div class="yzmf-feed" data-yzmf-feed style="--yzmf-feed-cols:%d;">%s</div>',
			$columns,
			$cards
		);
	}

	private static function render_card( $id ) {
		$cover_id = YZMF_Feed::get_cover_id( $id );
		if ( ! $cover_id ) return '';

		$img = wp_get_attachment_image_src( $cover_id, 'medium_large' );
		if ( ! $img ) return '';
		$alt = get_post_meta( $cover_id, '_wp_attachment_image_alt', true );

		$count   = count( YZMF_Feed::get_photos( $id ) );
		$likes   = YZMF_Feed::get_likes( $id );
		$ccount  = (int) get_comments_number( $id );
		$caption = get_post( $id )->post_content;

		$multi = $count > 1
			? '<span class="yzmf-feed-multi" aria-label="Varias fotos">▣</span>'
			: '';

		return sprintf(
			'<button type="button" class="yzmf-feed-card" data-id="%d" aria-label="%s">'
			. '<img src="%s" alt="%s" loading="lazy" />'
			. '%s'
			. '<span class="yzmf-feed-meta"><span>❤ %d</span><span>💬 %d</span></span>'
			. '</button>',
			$id,
			esc_attr( wp_trim_words( $caption, 12, '…' ) ),
			esc_url( $img[0] ),
			esc_attr( $alt ),
			$multi,
			$likes,
			$ccount
		);
	}
}
