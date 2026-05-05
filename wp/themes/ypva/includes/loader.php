<?php
function kotlis_import_files() {
	return array(
		array(
			'import_file_name'             => 'Ligh Version Demo',
			'categories'                   => array( 'Kotlis' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/light/demo-content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/light/widgets.wie',
			//'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'ocdi/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/light/redux.json',
					'option_name' => 'kotlis',
				),
			),
			'import_preview_image_url'     => 'https://webredox.net/demo/wp/kotlis/images/2.jpg',
			'import_notice'                => __( 'Be patient, it can take a couple of minutes.', 'kotlis' ),
			'preview_url'                  => 'http://webredox.net/demo/wp/kotlis/light/',
		),
		
		array(
			'import_file_name'             => 'Dark Version Demo',
			'categories'                   => array( 'Kotlis' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/dark/demo-content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/dark/widgets.wie',
			//'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'ocdi/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/dark/redux.json',
					'option_name' => 'kotlis',
				),
			),
			'import_preview_image_url'     => 'https://webredox.net/demo/wp/kotlis/images/1.jpg',
			'import_notice'                => __( 'Be patient, it can take a couple of minutes.', 'kotlis' ),
			'preview_url'                  => 'http://webredox.net/demo/wp/kotlis/dark',
		),	

		array(
			'import_file_name'             => 'WooCommerce Demo',
			'categories'                   => array( 'Kotlis' ),
			'local_import_file'            => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/woo/demo-content.xml',
			'local_import_widget_file'     => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/dark/widgets.wie',
			//'local_import_customizer_file' => trailingslashit( get_template_directory() ) . 'ocdi/customizer.dat',
			'local_import_redux'           => array(
				array(
					'file_path'   => trailingslashit( get_template_directory() ) . 'includes/kotlis-demo/dark/redux.json',
					'option_name' => 'kotlis',
				),
			),
			'import_preview_image_url'     => 'https://webredox.net/demo/wp/kotlis/images/1.jpg',
			'import_notice'                => __( 'Be patient, it can take a couple of minutes.', 'kotlis' ),
			'preview_url'                  => 'http://webredox.net/demo/wp/kotlis/dark',
		),	
		
		
	);
}
add_filter( 'pt-ocdi/import_files', 'kotlis_import_files' );

function kotlis_after_import_setup() {
	// Assign menus to their locations.
	$main_menu = get_term_by( 'name', 'Main Menu', 'nav_menu' );

	set_theme_mod( 'nav_menu_locations', array(
			'top-menu' => $main_menu->term_id,
		)
	);

	// Assign front page and posts page (blog page).
	$front_page_id = get_page_by_title( 'Home Carousel' );
	$blog_page_id  = get_page_by_title( 'Blog' );

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $front_page_id->ID );
	update_option( 'page_for_posts', $blog_page_id->ID );

}
add_action( 'pt-ocdi/after_import', 'kotlis_after_import_setup' );

function ocdi_plugin_page_setup( $default_settings ) {
	$default_settings['parent_slug'] = 'themes.php';
	$default_settings['page_title']  = esc_html__( 'Kotlis Demo Importer' , 'kotlis' );
	$default_settings['menu_title']  = esc_html__( 'Kotlis Demo Importer' , 'kotlis' );
	$default_settings['capability']  = 'import';
	$default_settings['menu_slug']   = 'kotlis-one-click-demo-import';

	return $default_settings;
}
add_filter( 'pt-ocdi/plugin_page_setup', 'ocdi_plugin_page_setup' );