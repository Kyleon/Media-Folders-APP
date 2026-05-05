<?php
if( !function_exists ('kotlis_enqueue_scripts') ) :
	function kotlis_enqueue_scripts() {
	global $opt_theme_style, $twitter_array , $twitter_count_array, $twitter_site_array;
	$kotlis_options = get_option('kotlis');	
	wp_enqueue_script('modernizr-min', (KOTLIS_THEME_URL . '/includes/js/modernizr-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('easing-min', (KOTLIS_THEME_URL . '/includes/js/easing-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('lightgallery-min', (KOTLIS_THEME_URL . '/includes/js/lightgallery-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('isotope-min', (KOTLIS_THEME_URL . '/includes/js/isotope-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('packery-min', (KOTLIS_THEME_URL . '/includes/js/packery-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('share-min', (KOTLIS_THEME_URL . '/includes/js/share-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('sliding-menu-min', (KOTLIS_THEME_URL . '/includes/js/sliding-menu-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('tweenmax-min', (KOTLIS_THEME_URL . '/includes/js/tweenmax-min.js'), array('jquery'), '1.0',true);
	wp_enqueue_script('swiper-min', (KOTLIS_THEME_URL . '/includes/js/swiper-min.js'), array('jquery'), '1.0',true);		  
	wp_enqueue_script('utility-min', (KOTLIS_THEME_URL . '/includes/js/utility-min.js'), array('jquery'), '1.0',true);	
    wp_register_script('map-min', (KOTLIS_THEME_URL . '/includes/js/map-min.js'), array('jquery'), '1.0',true);  
	wp_register_script('map-script', (KOTLIS_THEME_URL . '/includes/js/map-script.js'), array('jquery'), '1.0',true);	
	if (kotlis_AfterSetupTheme::return_thme_option('colorstyle')=='yes'){
	wp_enqueue_script('kotlis-scripts', (KOTLIS_THEME_URL . '/includes/js/dark-scripts.js'), array('jquery'), '1.0',true);	
	} else  {
	wp_enqueue_script('kotlis-scripts', (KOTLIS_THEME_URL . '/includes/js/scripts.js'), array('jquery'), '1.0',true);
	}	
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
	wp_enqueue_script( 'comment-reply' );
	}
}
	add_action('wp_enqueue_scripts', 'kotlis_enqueue_scripts');
endif;