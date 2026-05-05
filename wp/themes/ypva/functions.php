<?php
define('KOTLIS_THEME_PATH',	get_template_directory());
define('KOTLIS_THEME_URL', get_template_directory_uri());
require (KOTLIS_THEME_PATH . '/includes/style.php');
require (KOTLIS_THEME_PATH . '/includes/js.php');
require (KOTLIS_THEME_PATH . '/includes/color.php');
require (KOTLIS_THEME_PATH . '/includes/AfterSetupTheme.php');
require (KOTLIS_THEME_PATH . '/includes/functions.php');
require (KOTLIS_THEME_PATH . '/pagination.php');
require (KOTLIS_THEME_PATH . '/includes/ini/kotlis-base.php');

if ( ! isset( $content_width ) ) $content_width = 900;	

$kotlis_options = get_option('kotlis');

// register nav menu
function kotlis_register_menus() {
register_nav_menus( array( 
'top-menu' => esc_html__( 'Primary Menu','kotlis' ),
)
		);
}

add_action( 'after_setup_theme', 'kotlis_setup' );
function kotlis_setup() {
	
    // Add support for Block Styles.
	add_theme_support( 'wp-block-styles' );

	// Add support for full and wide align images.
	add_theme_support( 'align-wide' );

	// Add support for editor styles.
	add_theme_support( 'editor-styles' );
	// Enqueue editor styles.
	add_editor_style( 'style-editor.css' );
	
	// Add custom editor font sizes.
	add_theme_support(
			'editor-font-sizes',
			array(
				array(
					'name'      => esc_html__( 'Small', 'kotlis' ),
					'shortName' => esc_html__( 'S', 'kotlis' ),
					'size'      => 10,
					'slug'      => 'small',
				),
				array(
					'name'      => esc_html__( 'Normal', 'kotlis' ),
					'shortName' => esc_html__( 'M', 'kotlis' ),
					'size'      => 13,
					'slug'      => 'normal',
				),
				array(
					'name'      => esc_html__( 'Large', 'kotlis' ),
					'shortName' => esc_html__( 'L', 'kotlis' ),
					'size'      => 36,
					'slug'      => 'large',
				),
				array(
					'name'      => esc_html__( 'Huge', 'kotlis' ),
					'shortName' => esc_html__( 'XL', 'kotlis' ),
					'size'      => 49,
					'slug'      => 'huge',
				),
			)
		);
	
	add_theme_support( 'editor-color-palette', array(
        array(
            'name' => esc_html__( 'Black', 'kotlis' ),
            'slug' => 'color-black',
            'color' => '#000',
        ),
        array(
            'name' => esc_html__( 'Light Grey', 'kotlis' ),
            'slug' => 'color-white',
            'color' => '#fff',
        ),
        
    ) );
	
	// Add support for responsive embedded content.
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( "title-tag" );
	remove_theme_support( 'widgets-block-editor' );
	add_theme_support( 'post-formats', array('image','video','gallery') );
	add_post_type_support( 'portgallery', 'post-formats' );
}
add_action( 'init', 'kotlis_lang_setup' );
function kotlis_lang_setup(){
load_theme_textdomain('kotlis', get_template_directory() . '/languages');
}
// Word Limit 
	function kotlis_string_limit_words($string, $word_limit)
	{
	$words = explode(' ', $string, ($word_limit + 1));
	if(count($words) > $word_limit)
	array_pop($words);
	return implode(' ', $words);
	}
// Add post thumbnail functionality
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( 559, 220, true ); // Normal post thumbnails
	add_image_size( 'kotlis_blog_image', 370, 208, true ); // Blog Thumbnail
	add_image_size( 'kotlis_portfolio_image', 758, 520, true ); // Portfolio Thumbnail
	add_image_size( 'kotlis_portfolio_image_gallery_car', 604, 400, true ); // Portfolio Thumbnail
	add_image_size( 'kotlis_port_gallery_header', 762, 441, true ); //galeery header
	add_image_size( 'kotlis_blog', 695, 375, true ); // Blog
	add_image_size( 'kotlis_blog_pagination', 50, 50, true ); // Blog pagination
	
function kotlis_move_comment_field_to_bottom( $fields ) {
$comment_field = $fields['comment'];
unset( $fields['comment'] );
$fields['comment'] = $comment_field;
return $fields;
}
 
add_filter( 'comment_form_fields', 'kotlis_move_comment_field_to_bottom' );

// How comments are displayed
function kotlis_comment($comment, $args, $depth) {
    $GLOBALS['comment'] = $comment;
    extract($args, EXTR_SKIP);
if ( 'div' == $args['style'] ) {
      $tag = 'div';
      $add_below = 'comment';
    } else {
      $tag = 'li';
      $add_below = 'div-comment';
    }
?>
    <<?php echo esc_attr($tag); ?> <?php comment_class(empty( $args['has_children'] ) ? '' : 'parent') ?>>
    <?php if ( 'div' != $args['style'] ) : ?>	
    <?php endif; ?>    
	<div id="div-comment-<?php comment_ID() ?>" class="comment-body">
		<div class="comment-author">
		<?php if ($args['avatar_size'] != 0) echo get_avatar( $comment, '50' ); ?>
		</div>
	    <cite class="fn"><?php printf(__('%s','kotlis'), get_comment_author_link()) ?></cite>
		<div class="comment-meta">
		   <h6><a href="#"><?php comment_date(get_option( 'date_format')); ?></a> / <?php comment_reply_link(array_merge( $args, array('add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth']))) ?></h6>
		</div>	
		<div class="comment-text fl-wrap">
			<?php comment_text() ?>
		</div>	
	</div>
    <div class="clearfix"></div>
      <?php if ($comment->comment_approved == '0') : ?>
    <em class="comment-awaiting-moderation"><?php esc_html_e('Your comment is awaiting moderation.','kotlis') ?></em>
    <br />
	
   <?php endif; ?>    
<?php if ( 'div' != $args['style'] ) : ?>
    
    <?php endif; ?>
<?php
        }
// create sidebar & widget area
if (function_exists('register_sidebar')) {
function kotlis_theme_slug_widgets_init() {
    register_sidebar( array(
        'name' => esc_html__( 'Blog Sidebar', 'kotlis' ),
        'id' => 'sidebar-1',
        'description' => esc_html__( 'This area for blog widgets.', 'kotlis' ),
        'before_widget' => '<div id="%1$s" class="widget widget-block sb-post-widget fl-wrap %2$s"><div class="sb-post-widget_content">',
		'after_widget'  => '</div></div>', 
		'before_title'  => '<div class="sb-post-widget-header fl-wrap">', 
		'after_title'   => '</div>'
    ) );
}
add_action( 'widgets_init', 'kotlis_theme_slug_widgets_init' );

function kotlis_widgets_init() {
    register_sidebar( array(
        'name' => esc_html__( 'Page Sidebar', 'kotlis' ),
        'id' => 'sidebar-2',
        'description' => esc_html__( 'This area for pages widgets.', 'kotlis' ),
        'before_widget' => '<div id="%1$s" class="widget widget-block sb-post-widget fl-wrap %2$s"><div class="sb-post-widget_content">',
		'after_widget'  => '</div></div>', 
		'before_title'  => '<div class="sb-post-widget-header fl-wrap">', 
		'after_title'   => '</div>'
    ) );
}
add_action( 'widgets_init', 'kotlis_widgets_init' );

function kotlis_widgets_header_init() {
    register_sidebar( array(
        'name' => esc_html__( 'Header Click Sidebar', 'kotlis' ),
        'id' => 'sidebar-3',
        'description' => esc_html__( 'This area for header click widgets.', 'kotlis' ),
        'before_widget' => '<div id="%1$s" class="widget widget-block sb-widget-wrap fl-wrap %2$s">',
		'after_widget'  => '</div>', 
		'before_title'  => '<h3>', 
		'after_title'   => '</h3>'
    ) );
}
add_action( 'widgets_init', 'kotlis_widgets_header_init' );

if (class_exists('WooCommerce')) {
function solonick_woo_widgets_init() {
    register_sidebar( array(
        'name' => esc_html__( 'WOOCOMMERCE Sidebar', 'nastik' ),
        'id' => 'sidebar-4',
        'description' => esc_html__( 'This area for All WOOCOMMERCE Widget.', 'nastik' ),
        'before_widget' => '<div id="%1$s" class="widget widget-block sb-widget-wrap fl-wrap %2$s">',
		'after_widget'  => '</div>', 
		'before_title'  => '<h3>', 
		'after_title'   => '</h3>'
    ) );
}
add_action( 'widgets_init', 'solonick_woo_widgets_init' );
}

}

function kotlis_my_search_form( $form ) {
$kotlis_options = get_option('kotlis');
if (!empty($kotlis_options['translet_opt_6'])) {
$kotlis_search_text = esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_6',''));
}
else {
$kotlis_search_text ='Type & Hit Enters...';
}
    $kotlis_form = '<div class="widget-container fl-wrap"><div class="nav-search"><form role="search" method="get" id="searchform" class="searh-inner fl-wrap" action="' . esc_url(home_url( '/' )) . '" >
    <div><label class="screen-reader-text" for="s">' . esc_html__( 'Search for:','kotlis' ) . '</label>
    <input type="text" value="' . get_search_query() . '" name="s" id="s" class="search fl-wrap" placeholder="'. esc_attr($kotlis_search_text).'" />
    <button class="search-submit color-bg" id="submit_btn"><i class="fal fa-search"></i> </button>
    </div>
    </form></div></div>';
 
    return $kotlis_form;
}
add_filter( 'get_search_form', 'kotlis_my_search_form' );

if (function_exists('vc_set_as_theme')) vc_set_as_theme();
// Initialising Shortcodes
if (class_exists('WPBakeryVisualComposerAbstract')) {
  function requireVcExtend(){
    require_once KOTLIS_THEME_PATH . '/extendvc/extend-vc.php';
  }
  
}

add_filter("use_block_editor_for_post_type", "kotlis_disable_gutenberg_editor");
function kotlis_disable_gutenberg_editor() {
	if (kotlis_AfterSetupTheme::return_thme_option('opt_theme_gutenberg')=='st2'){
		return true;
	} else {
		return false;
	}
}

function endor_excerpt_more( $more ) {
    return '...';
}
add_filter('excerpt_more', 'endor_excerpt_more');
function endor_excerpt_length( $length ) {
    return 15;
}
add_filter( 'excerpt_length', 'endor_excerpt_length', 999 );

add_filter( 'run_wptexturize', '__return_false' );

/* CHECK WOOCOMMERCE IS ACTIVE
  ================================================== */ 
  if ( ! function_exists( 'kotlis_woocommerce_activated' ) ) {
    function kotlis_woocommerce_activated() {
      if ( class_exists( 'woocommerce' ) ) {
        return true;
      } else {
        return false;
      }
    }
  }

function kotlis_woo_wid_add_class($html) {
  $html = '<ul class="widget-posts">';
  return $html;
}
add_filter('woocommerce_before_widget_product_list', 'kotlis_woo_wid_add_class', 1, 15);

function kotlis_woo_wid_re_add_class($html) {
  $html = '<ul class="widget-posts">';
  return $html;
}
add_filter('woocommerce_before_widget_product_review_list', 'kotlis_woo_wid_re_add_class', 1, 15);


function woocommerce_pagination() {
		kotlis_pagination(); 		
	}
add_action( 'woocommerce_pagination', 'woocommerce_pagination', 10);

/**
 * Change number of related products output
 */ 
function woo_related_products_limit() {
  global $product;
	
	$args['posts_per_page'] = 6;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'kotlis_related_products_args', 20 );
  function kotlis_related_products_args( $args ) {
	$args['posts_per_page'] = 3; // 3 related products
	$args['columns'] = 3; // arranged in 1 columns
	return $args;
}

/*removing default submit tag*/
remove_action('wpcf7_init', 'wpcf7_add_form_tag_submit');
/*adding action with function which handles our button markup*/
add_action('wpcf7_init', 'kotlis_child_cf7_button');
/*adding out submit button tag*/
if (!function_exists('kotlis_child_cf7_button')) {
function kotlis_child_cf7_button() {
wpcf7_add_form_tag('submit', 'kotlis_child_cf7_button_handler');
}
}

/*out button markup inside handler*/
if (!function_exists('kotlis_child_cf7_button_handler')) {
function kotlis_child_cf7_button_handler($tag) {
$tag = new WPCF7_FormTag($tag);
$class = wpcf7_form_controls_class($tag->type);
$atts = array();
$atts['class'] = $tag->get_class_option($class);
$atts['class'] .= ' kotlis-child-custom-btn';
$atts['id'] = $tag->get_id_option();
$atts['tabindex'] = $tag->get_option('tabindex', 'int', true);
$value = isset($tag->values[0]) ? $tag->values[0] : '';
if (empty($value)) {
$value = esc_html__('Send', 'kotlis');
}
$atts['type'] = 'submit';
$atts = wpcf7_format_atts($atts);
$html = sprintf('<button class="btn float-btn flat-btn color-bg wpcf7-form-control wpcf7-submit">%2$s</button>', $atts, $value);
return $html;
}
}

function kotlis_body_classes( $classes ) {
	if (kotlis_AfterSetupTheme::return_thme_option('colorstyle')=='yes'){ 
    $classes[] = 'dark-version';
    } else {
	$classes[] = 'light-version';	
	}	
    return $classes;
}
add_filter( 'body_class','kotlis_body_classes' );

if (is_admin() && isset($_GET['activated'])){
	wp_redirect(admin_url("themes.php?page=kotlis"));
}