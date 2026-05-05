<?php
global $kotlis_options;
$kotlis_options = get_option('kotlis');
function kotlis_style() {
wp_enqueue_style('kotlis-main', (KOTLIS_THEME_URL . '/style.css'));
wp_enqueue_style('kotlis-reset', (KOTLIS_THEME_URL . '/includes/css/reset.css'));
wp_enqueue_style('kotlis-plugins', (KOTLIS_THEME_URL . '/includes/css/plugins.css'));
wp_enqueue_style('kotlis-style', (KOTLIS_THEME_URL . '/includes/css/style.css'));
if (kotlis_AfterSetupTheme::return_thme_option('colorstyle')=='yes'){
wp_enqueue_style('kotlis-style-dark', (KOTLIS_THEME_URL . '/includes/css/style-dark.css'));
}

if (kotlis_AfterSetupTheme::return_thme_option('cursors')!='yes'){
wp_enqueue_style('kotlis-cursors', (KOTLIS_THEME_URL . '/includes/css/cursors.css'));
}

wp_enqueue_style('kotlis-map', (KOTLIS_THEME_URL . '/includes/css/map.css'));
wp_enqueue_style('kotlis-main-style', (KOTLIS_THEME_URL . '/includes/css/kotlis-main-style.css'));
wp_enqueue_style('js_composer_front', (KOTLIS_THEME_URL . '/includes/css/js_composer.min.css'));
}
add_action('wp_enqueue_scripts', 'kotlis_style');

function kotlis_fonts_url() {
    $kotlis_font_url = '';
    
    if ( 'off' !== _x( 'on', 'Mukta font: on or off', 'kotlis' ) ) {
        $kotlis_font_url = add_query_arg( 'family','Playfair+Display|Ek+Mukta:200,300,400,500,600,700,800&subset=devanagari,latin-ext' , "//fonts.googleapis.com/css" );
    }
    return $kotlis_font_url;
}

function kotlis_scripts() {
    wp_enqueue_style( 'kotlis_fonts', kotlis_fonts_url(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'kotlis_scripts' );

function kotlis_enqueue_custom_admin_style() {
wp_register_style( 'custom_wp_admin_css', (KOTLIS_THEME_URL . '/includes/css/admin-style.css'), false, '1.0.0' );
wp_enqueue_style( 'custom_wp_admin_css' );
}
add_action( 'admin_enqueue_scripts', 'kotlis_enqueue_custom_admin_style' );