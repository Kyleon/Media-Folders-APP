<?php
/**
 * Registering meta boxes
 *
 * All the definitions of meta boxes are listed below with comments.
 * Please read them CAREFULLY.
 *
 * You also should read the changelog to know what has been changed before updating.
 *
 * For more information, please visit:
 * @link http://www.deluxeblogtips.com/meta-box/docs/define-meta-boxes
 */

/********************* META BOX DEFINITIONS ***********************/

/**
 * Prefix of meta keys (optional)
 * Use underscore (_) at the beginning to make keys hidden
 * Alt.: You also can make prefix empty to disable it
 */
// Better has an underscore as last sign
$prefix = 'rnr_';

global $meta_boxes;

$meta_boxes = array();

global $smof_data;


/* ----------------------------------------------------- */
// Page Sections Metaboxes
/* ----------------------------------------------------- */


/* ----------------------------------------------------- */
// Revolution Slider
/* ----------------------------------------------------- */

$revolutionslider = array();
$revolutionslider[0] = 'No Slider';

if(class_exists('RevSlider')){
    $slider = new RevSlider();
	$arrSliders = $slider->getArrSliders();
	foreach($arrSliders as $revSlider) { 
		$revolutionslider[$revSlider->getAlias()] = $revSlider->getTitle();
	}
}

/* Page Section Background Settings */

$grid_array = array('2 Columns','3 Columns','4 Columns');

$pagebg_type_array = array(
	'image' => 'Image',
	'gradient' => 'Gradient',
	'color' => 'Color'
);



/* ----------------------------------------------------- */
/* page Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'home_page_type',
	'title' => 'Default Page Template Options',
	'hide'   => array(
    // List of page templates (used for page only). Array. Optional.
    'template'    => array( 'home-page.php', 'portfolio.php', 'blog.php', 'video.php'),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Select', 'kotlis' ),
			'id'   => $prefix . 'wr_pagetype',
			'desc'  => esc_attr__( 'Default Page Type.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Select an Option', 'kotlis' ),
				'st1' => esc_attr__( 'Default', 'kotlis' ),
				'st2' => esc_attr__( 'Right Side Block', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		
		
		
	)
);


/* ----------------------------------------------------- */
/* page Header Options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_default_page_header_opt',
	'title' => 'Default Page Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_pagetype'  => 'st1',
    ),
	
	),
	
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Page Layout', 'dogmawp' ),
			'id'   => $prefix . 'page_layout',
			'desc'  => __( 'Works only Default Page Type', 'dogmawp' ),
			'type'     => 'image_select',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => __( get_template_directory_uri().'/includes/metaboxes/img/wr-page-default.png', 'gorge' ),
				'st1' => esc_attr__( get_template_directory_uri().'/includes/metaboxes/img/wr-page-right.png', 'dogmawp' ),
				'st2' => esc_attr__( get_template_directory_uri().'/includes/metaboxes/img/wr-page-left.png', 'dogmawp' ),
				'st3' => __( get_template_directory_uri().'/includes/metaboxes/img/wr-page-full.png', 'dogmawp' ),								
			),
			'desc'  => esc_attr__( '', 'dogmawp' ),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => __( 'Select an Option', 'dogmawp' ),
		),	
		array(
			   'name'     => esc_attr__( 'Page Header Section', 'kotlis' ),
			   'id'   => $prefix . 'page_header_block',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),			
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'page_right_block_header_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
			'visible' => array( 'rnr_page_header_block', '!=', 'no' )
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'page_right_block_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
			'visible' => array( 'rnr_page_header_block', '!=', 'no' )
		),
		
		
	)
);

/* ----------------------------------------------------- */
/* page st2 Header Options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_default_page_sideblock_opt',
	'title' => 'Right Side Block Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_pagetype'  => 'st2',
    ),
	
	),
	
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'sideblock_header_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'sideblock_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			   'name'     => esc_attr__( 'Page Container', 'kotlis' ),
			   'id'   => $prefix . 'ko_page_container',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'st1' => esc_attr__( 'Enable', 'kotlis' ),
				'st2' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'st1',

		    ),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'sideblock_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'sideblock_translet_scroll',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_sideblock_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);

/* ----------------------------------------------------- */
/* page st2 Header Options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_default_page_header2_opt',
	'title' => 'Page Content Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_pagetype'  => 'st2',
    ),
	
	),
	
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Footer Scrolling Menu', 'kotlis' ),
			'id'   => $prefix . 'th_page_sidebar_type_opt',
			'desc'  => esc_attr__( 'Footer page scrolling custom menu.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Disable', 'kotlis' ),
				'st1' => esc_attr__( 'Enable', 'kotlis' ),
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		
		
	)
);


/* ----------------------------------------------------- */
/* page sidebar st1 options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_default_page_sidebar_st1_opt',
	'title' => 'Scrolling Menu Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_th_page_sidebar_type_opt'  => 'st1',
    ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
				'id'		=> $prefix . 'th_page_scroll_menu_opt',
				'name'        => 'Nav Menu Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Nav Menu List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					
					array(
						'name'		=> 'Menu Name',
						'id'		=> $prefix . 'th_page_scroll_menu_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: About',
					),
					
					array(
						'name'		=> 'Section ID',
						'id'		=> $prefix . 'th_page_scroll_menu_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert visual composer row section ID here. Ex: #sec1',
					),
					
					
				),
			),
			
		
	)
);



/* ----------------------------------------------------- */
/* Home Page Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'home_page_intro_opt',
	'title' => 'Home Page Template Options.',
	// Show this meta box for posts matched below conditions
    'show'   => array(
    // List of page templates (used for page only). Array. Optional.
    'template'    => array( 'home-page.php'),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
	// SELECT BOX
		array(
			'name'     => esc_attr__( 'Intro Style', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_sc_opt',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Select an Option', 'kotlis' ),
				'st1' => esc_attr__( 'Slider Details', 'kotlis' ),
				'st2' => esc_attr__( 'Multi Slideshow', 'kotlis' ),
				'st3' => esc_attr__( 'Carousel', 'kotlis' ),
				'st5' => esc_attr__( 'Fullscreen Image', 'kotlis' ),
				'st6' => esc_attr__( 'Fullsceen Slider', 'kotlis' ),
				'st7' => esc_attr__( 'Fullscreen Slideshow', 'kotlis' ),
				'st8' => esc_attr__( 'Fullscreen Video', 'kotlis' ),
				'st4' => esc_attr__( 'Revolution Slider', 'kotlis' ),

			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
	
	)
);


/* ----------------------------------------------------- */
/* Intro Slider Details
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_half_slider_opt',
	'title' => 'Details Slider Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
       '#rnr_wr_intro_sc_opt' => 'st1',
    ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	
	'fields' => array(
	
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Gallery Image Title & Caption.', 'kotlis' ),
			'id'   => $prefix . 'custom_dt_slider_info_description_opt',
			'desc'  => esc_attr__( 'Show/Hide Image Title & Caption.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		array(
		'name'		=> 'Slider Speed',
		'id'		=> $prefix . 'md_full_details_slideshow_speed',
		'clone'		=> false,
		'type'		=> 'text',
		'std'		=> '',
		'desc'		=> 'Default: 2500',
		),
		
		array(
				'id'		=> $prefix . 'md_po_gallery',
				'name'        => 'Slider Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Slider Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					
					array(
					'name'		=> 'Slide Image',
					'id'		=> $prefix . 'md_po_half_gallery_img',
					'clone'		=> false,
					'type'		=> 'image_advanced',
					'max_file_uploads' => '1',
					'desc'		=> '',
					),
					
					
					array(
						'name'		=> 'Title',
						'id'		=> $prefix . 'md_gallery_intro_title_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Content',
						'id'		=> $prefix . 'md_gallery_intro_sub_title_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Popup Video URL',
						'id'		=> $prefix . 'md_intro_poup_vid_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'YouTube/ Viemo video URL.(Optional)',
					),
					
					array(
						'name'		=> 'Button Text',
						'id'		=> $prefix . 'md_intro_buttontxt_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Button URL',
						'id'		=> $prefix . 'md_intro_buttonurl_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),	
					array(
						'name'     => esc_attr__( 'Button Target', 'kotlis' ),
						'id'   => $prefix . 'custom_dt_slider_info_target_opt',
						'desc'  => esc_attr__( '', 'kotlis' ),
						'type'     => 'select_advanced',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'_self' => esc_attr__( 'Self', 'kotlis' ),
							'_blank' => esc_attr__( 'Blank', 'kotlis' ),
						),
						// Select multiple values, optional. Default is false.
						'multiple'    => false,
						'std'         => '_self',
						'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
					),
					
					array(
						'name'		=> 'Details Info 01',
						'id'		=> $prefix . 'md_intro_rotate_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> 'Ex: Location : &lt;span&gt; Switzerland , Bern &lt;/span&gt;',
					),
					
					array(
						'name'		=> 'Details Info 02',
						'id'		=> $prefix . 'md_intro_rotate_opt2',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> 'Ex: Photos : &lt;span&gt; 27 Photos &lt;/span&gt;',
					),
					
					array(
						'name'		=> 'Details Info 03',
						'id'		=> $prefix . 'md_intro_rotate_opt3',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> 'Ex: Camera : &lt;span&gt; Canon EOS R &lt;/span&gt;',
					),	
					
					array(
						'name'		=> 'Details Link URL',
						'id'		=> $prefix . 'md_intro_rotate_opt4',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),					
				
				),
			),
			
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_slide_scroll_swipe',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),

			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'home_slide_scroll_swipe_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Scroll down or  Swipe" text here.',
			'visible' => array( 'rnr_wr_intro_slide_scroll_swipe', '!=', 'no' )
		),	
		
		
	)
);

/* ----------------------------------------------------- */
/* Intro Multi Slideshow
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'multi_slideshow_kotlis',
	'title' => 'Multi Slideshow Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt' => 'st2',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
	
		array(
						'name'		=> 'Slider Speed',
						'id'		=> $prefix . 'md_multi_slideshow_speed',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Default: 3500',
		),
		
		array(
			'name'		=> 'Slide Show 1',
			'id'		=> $prefix . 'bl_multi_slide_1',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1000',
			'desc'		=> 'Slideshow Images',
		),
		
		array(
			'name'		=> 'Slide Show 2',
			'id'		=> $prefix . 'bl_multi_slide_2',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1000',
			'desc'		=> 'Slideshow Images',
		),
		
		array(
			'name'		=> 'Slide Show 3',
			'id'		=> $prefix . 'bl_multi_slide_3',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1000',
			'desc'		=> 'Slideshow Images',
		),
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_home_multi_slideshow_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_home_multi_slideshow_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Content',
			'id'		=> $prefix . 'bl_home_multi_slideshow_content',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),	
		
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'bl_intro_opt_multi_slideshow_custom_button_text',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Button URL',
			'id'		=> $prefix . 'bl_intro_opt_multi_slideshow_custom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_multi_slideshow_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_multi_slideshow_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_multi_slideshow_social_dis', '!=', 'st2' )
		),			
			array(
				'id'		=> $prefix . 'md_multi_slideshow_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_multi_slideshow_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_multi_slideshow_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_multi_slideshow_social_dis', '!=', 'st2' )
			),				
		
	)
);

/* ----------------------------------------------------- */
/* intro Slider
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_carousel_kotlish',
	'title' => 'Carousel Slider Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_intro_sc_opt' => 'st3',
    ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
	
		// carousel type
		array(
		'name'     => esc_attr__( 'Carousel Type', 'theside' ),
		'id'   => $prefix . 'ns_home_intro_car_new_opt',
		'desc'  => esc_attr__( '', 'theside' ),
		'type'     => 'select_advanced',
		// Array of 'value' => 'Label' pairs for select box
		'options'  => array(
			'st0' => esc_attr__( 'Select an Option', 'theside' ),
			'st1' => esc_attr__( 'Portfolio Post', 'theside' ),
			'st2' => esc_attr__( 'Custom Carousel', 'theside' ),
		),
		// Select multiple values, optional. Default is false.
		'multiple'    => false,
		'std'         => 'st0',
		'placeholder' => esc_attr__( 'Select an Option', 'theside' ),
		),
		
		
		
	)
);

/* ----------------------------------------------------- */
/* Intro Fullscreen Carousel
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_full_car_opt',
	'title' => 'Portfolio Carousel Options.',
	// Show this meta box for posts matched below conditions
    'show'   => array(
    // by metabox select
    'input_value'   => array(
                '#rnr_ns_home_intro_car_new_opt' => 'st1',
            ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	
	'fields' => array(
	
		array(
			'name'		=> 'Slider Speed',
			'id'		=> $prefix . 'md_car_slideshow_speed',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Default: 1400',
		),
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Slider Auto Play', 'popuga' ),
			'id'   => $prefix . 'wr_car_slideshow_autoplay',
			'desc'  => esc_attr__( '', 'popuga' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'false' => esc_attr__( 'Disable', 'popuga' ),
				'true' => esc_attr__( 'Enable', 'popuga' ),
			
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'false',
			'placeholder' => esc_attr__( 'Select an Option', 'popuga' ),
		),
	
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-home',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-home',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-home',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		    array(
			   'name'     => esc_attr__( 'Scroll Down Button', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_home_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_home_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_home_scroll_swipe', '!=', 'no' )
		   ),	
			array(
				'name'		=> 'External Button Text',
				'id'		=> $prefix . 'portfolio_home_tcustom_button_text',
				'clone'		=> false,
				'type'		=> 'text',
				'std'		=> '',
				'desc'		=> 'Ex: View Portfolio',
			),				
			array(
			'name'		=> 'External Button URL',
			'id'		=> $prefix . 'portfolio_home_tcustom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
			),
		


	)
);


/* ----------------------------------------------------- */
/* Intro Fullscreen Carousel
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_full_car_content2_opt',
	'title' => 'Custom Carousel Options',
	// Show this meta box for posts matched below conditions
    'show'   => array(
    // by metabox select
    'input_value'   => array(
                '#rnr_ns_home_intro_car_new_opt' => 'st2',
            ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
	
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Gallery Image Title & Caption.', 'kotlis' ),
			'id'   => $prefix . 'custom_carousel_info_description_opt',
			'desc'  => esc_attr__( 'Show/Hide Image Title & Caption.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
				array(
						'name'		=> 'Slider Speed',
						'id'		=> $prefix . 'md_cus_car_slideshow_speed',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Default: 1400',
				),

				// SELECT BOX
						array(
							'name'     => esc_attr__( 'Slider Auto Play', 'popuga' ),
							'id'   => $prefix . 'wr_cus_car_slideshow_autoplay',
							'desc'  => esc_attr__( '', 'popuga' ),
							'type'     => 'select_advanced',
							// Array of 'value' => 'Label' pairs for select box
							'options'  => array(
								'false' => esc_attr__( 'Disable', 'popuga' ),
								'true' => esc_attr__( 'Enable', 'popuga' ),
							
							),
							// Select multiple values, optional. Default is false.
							'multiple'    => false,
							'std'         => 'false',
							'placeholder' => esc_attr__( 'Select an Option', 'popuga' ),
						),				
					
					array(
				'id'		=> $prefix . 'md_po_car_cus_gallery',
				'name'        => 'Carousel Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Carousel Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					
					array(
					'name'		=> 'Carousel Image',
					'id'		=> $prefix . 'md_po_car_cus_gallery_img',
					'clone'		=> false,
					'type'		=> 'image_advanced',
					'max_file_uploads' => '1',
					'desc'		=> '',
					),
					
					
					array(
						'name'		=> 'Title',
						'id'		=> $prefix . 'md_car_cus_gallery_intro_title_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Sub Title',
						'id'		=> $prefix . 'md_car_cus_gallery_intro_sub_title_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> 'Optional.',
					),
					
					
					array(
						'name'		=> 'Custom URL',
						'id'		=> $prefix . 'md_car_cus_intro_buttonurl_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Optional.',
					),
					
					array(
						'name'		=> 'Popup Video URL',
						'id'		=> $prefix . 'md_car_cus_intro_pop_video_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Optional. <br> Use Youtube/ Vimeo video URL.',
					),
				
				),
			),
			
			array(
			   'name'     => esc_attr__( 'Scroll Down Button', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_cus_home_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_cus_home_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_cus_home_scroll_swipe', '!=', 'no' )
		   ),	
			array(
				'name'		=> 'External Button Text',
				'id'		=> $prefix . 'portfolio_cus_home_tcustom_button_text',
				'clone'		=> false,
				'type'		=> 'text',
				'std'		=> '',
				'desc'		=> 'Ex: View Portfolio',
			),				
			array(
			'name'		=> 'External Button URL',
			'id'		=> $prefix . 'portfolio_cus_home_tcustom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
			),
			
	)
);


/* ----------------------------------------------------- */
/* Intro fullscreen Image
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_fullscreen_image_kotlis',
	'title' => 'Fullscreen Image Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt'   => 'st5',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
			'name'		=> 'Background Image',
			'id'		=> $prefix . 'bl_fullscreen_image_slide',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1',
			'desc'		=> '',
		),
		
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_home_fullscreen_image_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_home_fullscreen_image_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Content',
			'id'		=> $prefix . 'bl_home_fullscreen_image_content',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),	
		
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'bl_intro_opt_fullscreen_image_custom_button_text',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Button URL',
			'id'		=> $prefix . 'bl_intro_opt_fullscreen_image_custom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),

		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_fullscreen_image_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_fullscreen_image_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_fullscreen_image_social_dis', '!=', 'st2' )
		),			
			array(
				'id'		=> $prefix . 'md_fullscreen_image_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_fullscreen_image_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_fullscreen_image_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_fullscreen_image_social_dis', '!=', 'st2' )
			),
			
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Right Side Category', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_fullscreen_image_right_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
			array(
				'id'		=> $prefix . 'md_fullscreen_image_right_side_title_opt',
				'name'        => 'Right Side Category List',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Right Side Category Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Number',
						'id'		=> $prefix . 'md_fullscreen_image_right_side_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: 01.',
					),									
					array(
						'name'		=> 'Content',
						'id'		=> $prefix . 'md_fullscreen_image_right_side_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: Commercial',
					),
					
				),
				'hidden' => array( 'rnr_wr_intro_fullscreen_image_right_dis', '!=', 'st2' )
			),

	)
);


/* ----------------------------------------------------- */
/* Intro Full slider
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_full_slider_opt',
	'title' => 'Fullsceen slider Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt'   => 'st6',
            ),	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(	
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Gallery Image Title & Caption.', 'kotlis' ),
			'id'   => $prefix . 'custom_opt_slider_info_description_opt',
			'desc'  => esc_attr__( 'Show/Hide Image Title & Caption.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		array(
						'name'		=> 'Slider Speed',
						'id'		=> $prefix . 'md_full_slideshow_speed',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Default: 2500',
		),
	
		array(
				'id'		=> $prefix . 'md_po_full_gallery',
				'name'        => 'Slider Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Slider Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
									
					array(
					'name'		=> 'Slide Image',
					'id'		=> $prefix . 'md_po_full_gallery_img',
					'clone'		=> false,
					'type'		=> 'image_advanced',
					'max_file_uploads' => '1',
					'desc'		=> '',
					),
					
					
					array(
						'name'		=> 'Title',
						'id'		=> $prefix . 'md_gallery_full_intro_title_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Content',
						'id'		=> $prefix . 'md_gallery_full_intro_sub_title_opt',
						'clone'		=> false,
						'type'		=> 'textarea',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Button Text',
						'id'		=> $prefix . 'md_intro_full_buttontxt_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),
					
					array(
						'name'		=> 'Button URL',
						'id'		=> $prefix . 'md_intro_full_buttonurl_opt',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> '',
					),
				
				),
			),
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_full_slider_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_full_slider_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_full_slider_social_dis', '!=', 'st2' )
		),			
			array(
				'id'		=> $prefix . 'md_full_slider_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_full_slider_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_full_slider_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_full_slider_social_dis', '!=', 'st2' )
			),
	
		
	)
);


/* ----------------------------------------------------- */
/* Intro slideshow Image
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_slideshow_kotlis',
	'title' => 'Fullscreen slideshow Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt' => 'st7',
    ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
						'name'		=> 'Slider Speed',
						'id'		=> $prefix . 'md_full_multi_details_slideshow_speed',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Default: 3500',
		),
		
		array(
			'name'		=> 'Slidwshow Images',
			'id'		=> $prefix . 'bl_slideshow_slide',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1000',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_home_slideshow_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_home_slideshow_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Content',
			'id'		=> $prefix . 'bl_home_slideshow_content',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),	
		
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'bl_intro_opt_slideshow_custom_button_text',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Button URL',
			'id'		=> $prefix . 'bl_intro_opt_slideshow_custom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_slideshow_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_slideshow_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_slideshow_social_dis', '!=', 'st2' )
		),
				
			array(
				'id'		=> $prefix . 'md_slideshow_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_slideshow_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_slideshow_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_slideshow_social_dis', '!=', 'st2' )
			),	
		
	)
);


/* ----------------------------------------------------- */
/* Intro Video Image
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_video_kotlis',
	'title' => 'Video Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt' => 'st8',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
			'name'		=> 'Background Images',
			'id'		=> $prefix . 'bl_video_slide',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'max_file_uploads' => '1',
			'desc'		=> 'Working only mobile device.',
		),
		array(
			'name'     => esc_attr__( 'Video Style', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_video_opt',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Vimeo', 'kotlis' ),
				'st3' => esc_attr__( 'Youtube', 'kotlis' ),
				'st2' => esc_attr__( 'MP4', 'kotlis' ),
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'YouTube Video ID',
			'id'		=> $prefix . 'bl_home_video_id2',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Ex: Hg5iNVSp2z8',
			'hidden' => array( 'rnr_wr_intro_video_opt', '!=', 'st3' )
		),	
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Video Sound', 'restabook' ),
			'id'   => $prefix . 'kt_intro_youtube_video_sound',
			'desc'  => esc_attr__( '', 'restabook' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'1' => esc_attr__( 'Mute', 'restabook' ),
				'0' => esc_attr__( 'On', 'restabook' ),
			),
			'std'         => '1',
			'hidden' => array( 'rnr_wr_intro_video_opt', '!=', 'st3' )
		),
		array(
			'name'		=> 'Vimeo Video ID',
			'id'		=> $prefix . 'bl_home_video_id',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Ex: 97871257',
			'hidden' => array( 'rnr_wr_intro_video_opt', '!=', 'st1' )
		),
		array(
			'name'		=> 'MP4 Video URL',
			'id'		=> $prefix . 'bl_intro_opt_video_mp4_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Ex: http://springbook.kwst.net/video/1.mp4',
			'hidden' => array( 'rnr_wr_intro_video_opt', '!=', 'st2' )
		),
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Video Sound', 'restabook' ),
			'id'   => $prefix . 'kt_intro_mp4_video_sound',
			'desc'  => esc_attr__( '', 'restabook' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'muted' => esc_attr__( 'Mute', 'restabook' ),
				'unmuted' => esc_attr__( 'On', 'restabook' ),
			),
			'std'         => 'muted',
			'hidden' => array( 'rnr_wr_intro_video_opt', '!=', 'st2' )
		),		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_home_video_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_home_video_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Content',
			'id'		=> $prefix . 'bl_home_video_content',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),	
		
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'bl_intro_opt_video_custom_button_text',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Button URL',
			'id'		=> $prefix . 'bl_intro_opt_video_custom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_video_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_video_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_video_social_dis', '!=', 'st2' )
		),			
			array(
				'id'		=> $prefix . 'md_video_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_video_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_video_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_video_social_dis', '!=', 'st2' )
			),
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Right Side Category', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_video_right_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
			array(
				'id'		=> $prefix . 'md_video_right_side_title_opt',
				'name'        => 'Right Side Category List',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Right Side Category Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Number',
						'id'		=> $prefix . 'md_video_right_side_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: 01.',
					),									
					array(
						'name'		=> 'Content',
						'id'		=> $prefix . 'md_video_right_side_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: Commercial',
					),
					
				),
				'hidden' => array( 'rnr_wr_intro_video_right_dis', '!=', 'st2' )
			),		
	)
);

/* ----------------------------------------------------- */
/* Intro Revolution Slider
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'intro_rev_kotlis',
	'title' => 'Revolution Slider Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
                '#rnr_wr_intro_sc_opt' => 'st4',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
			'name'		=> 'Revolution slider Shortcode',
			'id'		=> $prefix . 'th_rev_shortcode_opt',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Ex: [rev_slider alias="focus-parallax"]',
		),
		
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_home_rev_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_home_rev_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Content',
			'id'		=> $prefix . 'bl_home_rev_content',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),	
		
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'bl_intro_opt_rev_custom_button_text',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Button URL',
			'id'		=> $prefix . 'bl_intro_opt_rev_custom_button_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),

		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Social Share', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_rev_social_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		array(
			'name'		=> 'Follow Text',
			'id'		=> $prefix . 'bl_home_rev_sc_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Replace "Follow" text here.',
			'hidden' => array( 'rnr_wr_intro_rev_social_dis', '!=', 'st2' )
		),			
			array(
				'id'		=> $prefix . 'md_rev_social_title_opt',
				'name'        => 'Social Share Icon',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Social Share Icon List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Icon Name',
						'id'		=> $prefix . 'md_rev_social_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Fontawesome Icon Class Name Here. Ex: fab fa-facebook-f',
					),				
					array(
						'name'		=> 'URL Link',
						'id'		=> $prefix . 'md_rev_social_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Insert Icon Link URL Here.',
					),									
					
				),
				'hidden' => array( 'rnr_wr_intro_rev_social_dis', '!=', 'st2' )
			),
			
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Right Side Category', 'kotlis' ),
			'id'   => $prefix . 'wr_intro_rev_right_dis',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
			array(
				'id'		=> $prefix . 'md_rev_right_side_title_opt',
				'name'        => 'Right Side Category List',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Right Side Category Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name'		=> 'Number',
						'id'		=> $prefix . 'md_rev_right_side_title_url',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: 01.',
					),									
					array(
						'name'		=> 'Content',
						'id'		=> $prefix . 'md_rev_right_side_title',
						'clone'		=> false,
						'type'		=> 'text',
						'std'		=> '',
						'desc'		=> 'Ex: Commercial',
					),
					
				),
				'hidden' => array( 'rnr_wr_intro_rev_right_dis', '!=', 'st2' )
			),

	)
);

/* ----------------------------------------------------- */
/* portfolio page Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_page_types',
	'title' => 'Portfolio Page Template Function',
	'show'   => array(
    'template'    => array( 'portfolio.php' ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Select Template', 'kotlis' ),
			'id'   => $prefix . 'wr_portfolio_pagetype',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Select an Option', 'kotlis' ),
				'st4' => esc_attr__( 'Horizontal 1 Column', 'kotlis' ),
				'st2' => esc_attr__( 'Horizontal 2 Column', 'kotlis' ),
				'st3' => esc_attr__( 'Horizontal 3 Column', 'kotlis' ),
				'st5' => esc_attr__( 'Column Grid', 'kotlis' ),
				'st6' => esc_attr__( 'Masonry', 'kotlis' ),
				'st7' => esc_attr__( 'Masonry 2', 'kotlis' ),
				
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		
	)
);

/* ----------------------------------------------------- */
/* portfolio style 2 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_2row',
	'title' => 'Horizontal 2 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st2',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-2row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-2row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-2row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-2row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'portfolio_2row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-2row', '!=', 'no' )
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_2row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-2row', '!=', 'no' )
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_2row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_2row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_2row_scroll_swipe', '!=', 'no' )
		   ),
		
	
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style 3 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_3row',
	'title' => 'Horizontal 3 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st3',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-3row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-3row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-3row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-3row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'portfolio_3row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-3row', '!=', 'no' )
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_3row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-3row', '!=', 'no' )
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_3row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_3row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_3row_scroll_swipe', '!=', 'no' )
		   ),
		
	
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style 1 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_1row',
	'title' => 'Horizontal 1 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st4',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-1row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-1row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-1row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-1row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'portfolio_1row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-1row', '!=', 'no' )
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_1row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-1row', '!=', 'no' )
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_1row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_1row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_1row_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style Column Grid 
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_boxed',
	'title' => 'Column Grid Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st5',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-box_grid',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-box_grid',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-box_grid',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-box_grid',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'portfolio_box_grid_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-box_grid', '!=', 'no' )
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_box_grid_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-box_grid', '!=', 'no' )
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_box_grid_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_box_grid_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_box_grid_scroll_swipe', '!=', 'no' )
		   ),
	
		
	)
);

/* ----------------------------------------------------- */
/* Portfolio Masonry Style
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_column_grid',
	'title' => 'Masonry Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st6',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-column_grid',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-column_grid',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-column_grid',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-column_grid',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'portfolio_column_grid_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-column_grid', '!=', 'no' )
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_column_grid_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-column_grid', '!=', 'no' )
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_column_grid_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_column_grid_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_scroll_swipe', '!=', 'no' )
		   ),
	
		
	)
);


/* ----------------------------------------------------- */
/* Boxed Header Options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_column_grid2_header_opt',
	'title' => 'Column Grid 2 Sidebar Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st7',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(

		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'bl_column_grid2_header_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'bl_column_grid2_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_column_grid2_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_column_grid2_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid2_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style Boxed
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_opt_column_grid2',
	'title' => 'Column Grid 2 Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_portfolio_pagetype' => 'st7',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Portfolio Filter', 'kotlis' ),
		   'id'   => $prefix . 'port-filter-column_grid2',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'portfolio-post-show-column_grid2',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'portfolio-post-cat-column_grid2',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'portfolio-post-offset-column_grid2',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'portfolio_column_grid2_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_port-filter-column_grid2', '!=', 'no' )
		   ),
	
		
	)
);

/* ----------------------------------------------------- */
/* video page Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_page_types',
	'title' => 'Video Page Template Function',
	'show'   => array(
    'template'    => array( 'video.php' ),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Select Template', 'kotlis' ),
			'id'   => $prefix . 'wr_video_pagetype',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Select an Option', 'kotlis' ),
				'st4' => esc_attr__( 'Horizontal 1 Column', 'kotlis' ),
				'st2' => esc_attr__( 'Horizontal 2 Column', 'kotlis' ),
				'st3' => esc_attr__( 'Horizontal 3 Column', 'kotlis' ),
				'st5' => esc_attr__( 'Column Grid', 'kotlis' ),
				'st6' => esc_attr__( 'Masonry', 'kotlis' ),
				'st7' => esc_attr__( 'Masonry 2', 'kotlis' ),
				
				
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		
	)
);

/* ----------------------------------------------------- */
/* portfolio style 2 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_2row',
	'title' => 'Horizontal 2 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st2',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-2row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-2row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-2row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-2row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'video_2row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_2row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_2row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_2row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_2row_scroll_swipe', '!=', 'no' )
		   ),
		
	
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style 3 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_3row',
	'title' => 'Horizontal 3 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st3',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-3row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-3row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-3row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-3row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'video_3row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_3row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_3row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_3row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_3row_scroll_swipe', '!=', 'no' )
		   ),
		
	
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style 1 Column
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_1row',
	'title' => 'Horizontal 1 Column Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st4',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-1row',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-1row',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-1row',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-1row',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'video_1row_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_1row_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_1row_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_1row_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_1row_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style Column Grid 
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_boxed',
	'title' => 'Column Grid Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st5',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-box_grid',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-box_grid',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-box_grid',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-box_grid',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'video_box_grid_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_box_grid_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_box_grid_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_box_grid_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_box_grid_scroll_swipe', '!=', 'no' )
		   ),
	
		
	)
);

/* ----------------------------------------------------- */
/* Portfolio Masonry Style
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_column_grid',
	'title' => 'Masonry Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st6',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-column_grid',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-column_grid',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-column_grid',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-column_grid',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'Filter Text',
			'id'		=> $prefix . 'video_column_grid_translet_opt1',
			'desc'		=> 'Replace "Filter" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_column_grid_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_column_grid_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_column_grid_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_column_grid_scroll_swipe', '!=', 'no' )
		   ),
	
		
	)
);


/* ----------------------------------------------------- */
/* Boxed Header Options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_column_grid2_header_opt',
	'title' => 'Column Grid 2 Sidebar Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st7',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(

		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'video_bl_column_grid2_header_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'video_bl_column_grid2_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_column_grid2_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_column_grid2_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_column_grid2_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);


/* ----------------------------------------------------- */
/* portfolio style Boxed
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_opt_column_grid2',
	'title' => 'Column Grid 2 Options.',
	'show'   => array(
    // by metabox select
	'input_value'   => array(
    '#rnr_wr_video_pagetype' => 'st7',
            ),
	
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
		   'name'     => esc_attr__( 'Video Filter', 'kotlis' ),
		   'id'   => $prefix . 'video-filter-column_grid2',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		  
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'video-post-show-column_grid2',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 1000,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'video-post-cat-column_grid2',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).<br>Works Only If Filter Option  Disabled.',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
			array(
			'name'		=> 'Post Offset',
			'id'		=> $prefix . 'video-post-offset-column_grid2',
			'desc'		=> 'Hide number of latest post (optional).',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		   array(
			'name'		=> 'All Text',
			'id'		=> $prefix . 'video_column_grid2_translet_opt2',
			'desc'		=> 'Replace "All" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
	
		
	)
);

/* ----------------------------------------------------- */
/* Video Post Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_type',
	'title' => 'Video Details Page Options.',
	'pages' => array( 'video' ),
	'context' => 'normal',	

	'fields' => array(
		
				
		array(
			'name'		=> 'Popup Video URL',
			'id'		=> $prefix . 'video_post_vid_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Use Youtube/ Vimeo Video URL.<br> Required.',
		),
		
	
	)
);

/* ----------------------------------------------------- */
/* Portfolio Column Grid style 1
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_details_page_sidebar_type1',
	'title' => 'Video Column Grid Sidebar Options',
	'pages' => array( 'video'),
	'context' => 'normal',	

	'fields' => array(
	
		array(
		'name'		=> 'Background Images',
		'id'		=> $prefix . 'video_column_grid_details_sidebar_image',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1',
		'desc'		=> '',
		),	
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'video_column_grid_details_sidebar_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'video_column_grid_details_sidebar_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'video_column_grid_details_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'video_column_grid_details_translet_scroll',
			'desc'		=> 'Replace "Scroll Down To Discover" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_video_column_grid_details_scroll_swipe', '!=', 'no' )
		   ),
	
	)
);

$meta_boxes[] = array(
	'id' => 'video_details_page_gallery_type1',
	'title' => 'Video Column Grid Video Gallery Options',
	'pages' => array( 'video'),
	'context' => 'normal',	

	'fields' => array(
		array(
		   'name'     => esc_attr__( 'Gallery Title Section', 'kotlis' ),
		   'id'   => $prefix . 'video_column_grid_gallery_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'no',

		  ),
		array(
			'name'		=> 'Gallery Title Text',
			'id'		=> $prefix . 'video_column_grid_gallery_title',
			'desc'		=> 'Ex: Project Gallery',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		array(
			'name'		=> 'Gallery Subtitle Text',
			'id'		=> $prefix . 'video_column_grid_gallery_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> ''
		   ),
		array(
			'name'		=> 'Gallery Section Number',
			'id'		=> $prefix . 'video_column_grid_gallery_number',
			'desc'		=> 'Ex: 01.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),	
		array(
		   'name'     => esc_attr__( 'Video Gallery Section', 'kotlis' ),
		   'id'   => $prefix . 'video_column_grid_gallery_images_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'no',

		  ),
		
		// SELECT BOX
					array(
						'name'     => esc_attr__( 'Video Gallery Column', 'popuga' ),
						'id'   => $prefix . 'md_video_gallery_column_opt',
						'desc'  => esc_attr__( 'Optional.', 'popuga' ),
						'type'     => 'select_advanced',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'st1' => esc_attr__( 'Three Column', 'popuga' ),
							'st2' => esc_attr__( 'Two Column', 'popuga' ),
							'st3' => esc_attr__( 'One Column', 'popuga' ),
							
							
						),
						// Select multiple values, optional. Default is false.
						'multiple'    => false,
						'std'         => 'st1',
						'placeholder' => esc_attr__( 'Select an Option.', 'popuga' ),
					),
		
		array(
				'id'		=> $prefix . 'so_drt_po_gallery',
				'name'        => 'Video Galley',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Video Galley Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					// SELECT BOX
					array(
						'name'     => esc_attr__( 'Thumbnail Size', 'popuga' ),
						'id'   => $prefix . 'md_video_gallery_column',
						'desc'  => esc_attr__( 'Required.', 'popuga' ),
						'type'     => 'select_advanced',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'gallery-item-one' => esc_attr__( 'Default', 'popuga' ),
							'gallery-item-second' => esc_attr__( 'Double', 'popuga' ),
							
							
						),
						// Select multiple values, optional. Default is false.
						'multiple'    => false,
						'std'         => 'gallery-item-one',
						'placeholder' => esc_attr__( 'Select an Option.', 'popuga' ),
					),
				
					array(
					'name'		=> 'Upload Video Thumbnail',
					'id'		=> $prefix . 'video-image-popu',
					'clone'		=> false,
					'type'		=> 'image_advanced',
					'max_file_uploads' => '1',
					'desc'		=> 'Required.',
					),
					
					array(
					'name'		=> 'Popup Video URL',
					'id'		=> $prefix . 'ns_video_gallery_video_opt',
					'clone'		=> false,
					'type'		=> 'text',
					'std'		=> '',
					'desc'		=> 'Youtube/ Vimeo Video URL.',
					),
					
				
				),
			),			
				
	)
);

$meta_boxes[] = array(
	'id' => 'video_details_page_content_type1',
	'title' => 'Video Column Grid Content Options',
	'pages' => array( 'video'),
	'context' => 'normal',	

	'fields' => array(
			
		array(
		   'name'     => esc_attr__( 'Content Title Section', 'kotlis' ),
		   'id'   => $prefix . 'video_column_grid_content_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'no',

		  ),
		array(
			'name'		=> 'Content Title Text',
			'id'		=> $prefix . 'video_column_grid_content_title',
			'desc'		=> 'Ex: Project Details',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		array(
			'name'		=> 'Content Subtitle Text',
			'id'		=> $prefix . 'video_column_grid_content_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> ''
		   ),
		array(
			'name'		=> 'Content Section Number',
			'id'		=> $prefix . 'video_column_grid_content_number',
			'desc'		=> 'Ex: 02.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   		   
		array(
		   'name'     => esc_attr__( 'Project Information', 'kotlis' ),
		   'id'   => $prefix . 'video_column_grid_project_info',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'no',

		  ),		   
		array(
				'id'		=> $prefix . 'video_column_grid_project_info_main',
				'name'        => 'Project Info Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Project Info List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					
					array(
						'name' => 'Data Title',
						'id'		=> $prefix . 'video_column_grid_dt_in_title',
						'type' => 'text',
						'desc'		=> 'Ex: Location',
					),
					
					array(
						'name' => 'Data Subtitle',
						'id'		=> $prefix . 'video_column_grid_dt_in_subtitle',
						'type' => 'text',
						'desc'		=> 'Ex: NY , USA',
					),

				),
			),				
		array(
			'name'		=> 'Project Button Text',
			'id'		=> $prefix . 'video_column_grid_dt_in_button_text',
			'desc'		=> 'Ex: View Project',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),
		array(
			'name'		=> 'Project Button URL',
			'id'		=> $prefix . 'video_column_grid_dt_in_button_url',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ),		   
		array(
		   'name'     => esc_attr__( 'Prev / Next Post', 'kotlis' ),
		   'id'   => $prefix . 'video_column_grid_project_prev_next',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),			
	)
);

//video end

/* ----------------------------------------------------- */
/* Portfolio Post Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'video_type',
	'title' => 'Portfolio Popup Options.',
	'pages' => array( 'portfolio' ),
	'context' => 'normal',	

	'fields' => array(
		
				
		array(
			'name'		=> 'Popup Video URL',
			'id'		=> $prefix . 'video_portpost_vid_url',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> 'Use Youtube/ Vimeo Video URL.<br> Optional.',
			'tooltip' => array(
                    'icon'     => 'info',
                    'content'  => 'Effected only in portfolio page template and portfolio carousel.',
                    'position' => 'top',
            ),
		),
		
	
	)
);

/* ----------------------------------------------------- */
/* Portfolio Post Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'portfolio_type',
	'title' => 'Portfolio Details Page Options.',
	'pages' => array( 'portfolio' ),
	'context' => 'normal',	

	'fields' => array(
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Details Page Style', 'kotlis' ),
			'id'   => $prefix . 'wr_port_dt_opt',
			'desc'  => esc_attr__( '', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => esc_attr__( 'Select an Option', 'kotlis' ),
				'st2' => esc_attr__( 'Carousel', 'kotlis' ),
				'st4' => esc_attr__( 'Fullscreen Slider', 'kotlis' ),
				'st1' => esc_attr__( 'Column Grid', 'kotlis' ),				
				'st3' => esc_attr__( 'Column Fullwidth', 'kotlis' ),
				
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Gallery Image Title & Caption.', 'kotlis' ),
			'id'   => $prefix . 'port_carousel_info_description_opt',
			'desc'  => esc_attr__( 'Show/Hide Image Title & Caption.', 'kotlis' ),
			'type'     => 'select_advanced',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st1' => esc_attr__( 'Disable', 'kotlis' ),
				'st2' => esc_attr__( 'Enable', 'kotlis' ),
			),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st1',
			'placeholder' => esc_attr__( 'Select an Option', 'kotlis' ),
		),
		
		
		
	)
);


/* ----------------------------------------------------- */
/* Portfolio Column Grid style 1
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'port_details_page_sidebar_type1',
	'title' => 'Portfolio Column Grid Sidebar Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt'   => 'st1',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(
	
		array(
		'name'		=> 'Background Images',
		'id'		=> $prefix . 'portfolio_column_grid_details_sidebar_image',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1',
		'desc'		=> '',
		),	
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'portfolio_column_grid_details_sidebar_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'portfolio_column_grid_details_sidebar_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_column_grid_details_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_column_grid_details_translet_scroll',
			'desc'		=> 'Replace "Scroll Down To Discover" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_details_scroll_swipe', '!=', 'no' )
		   ),
	
	)
);

$meta_boxes[] = array(
	'id' => 'port_details_page_content_type1',
	'title' => 'Portfolio Column Grid Content Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt'   => 'st1',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(
		array(
		   'name'     => esc_attr__( 'Gallery Title Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_gallery_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Gallery Title Text',
			'id'		=> $prefix . 'portfolio_column_grid_gallery_title',
			'desc'		=> 'Ex: Project Gallery',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_gallery_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Gallery Subtitle Text',
			'id'		=> $prefix . 'portfolio_column_grid_gallery_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_gallery_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Gallery Section Number',
			'id'		=> $prefix . 'portfolio_column_grid_gallery_number',
			'desc'		=> 'Ex: 01.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_gallery_title_section', '!=', 'no' )
		   ),	
		array(
		   'name'     => esc_attr__( 'Gallery Images Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_gallery_images_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
		  ),		   		
		array(
				'id'		=> $prefix . 'ko_col_grid_gallery',
				'name'        => 'Image Galley',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Image Galley Item', // ID of the subfield
				'save_state' => true,
				'fields' => array(
				
					// SELECT BOX
					array(
						'name'     => esc_attr__( 'Thumbnail Size', 'popuga' ),
						'id'   => $prefix . 'ko_col_grid_gallery_size',
						'desc'  => esc_attr__( 'Required.', 'popuga' ),
						'type'     => 'select_advanced',
						// Array of 'value' => 'Label' pairs for select box
						'options'  => array(
							'gallery-item-one' => esc_attr__( 'Default', 'popuga' ),
							'gallery-item-second' => esc_attr__( 'Double', 'popuga' ),
						),
						// Select multiple values, optional. Default is false.
						'multiple'    => false,
						'std'         => 'gallery-item-one',
						'placeholder' => esc_attr__( 'Select an Option.', 'popuga' ),
					),
				
					array(
					'name'		=> 'Upload Gallery Images',
					'id'		=> $prefix . 'portfolio_column_grid_gallery_images',
					'clone'		=> false,
					'type'		=> 'image_advanced',
					'max_file_uploads' => '1000',
					'desc'		=> '<b>Upload only one image, if you added a popup video URL.</b>',
					),
					
					array(
					'name'		=> 'Popup Video URL',
					'id'		=> $prefix . 'portfolio_column_grid_gallery_images_popvid',
					'clone'		=> false,
					'type'		=> 'text',
					'std'		=> '',
					'desc'		=> 'Youtube/ Vimeo Video URL.',
					),
				),
				'visible' => array( 'rnr_portfolio_column_grid_gallery_images_section', '!=', 'no' )
			),			
		array(
		   'name'     => esc_attr__( 'Content Title Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_content_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Content Title Text',
			'id'		=> $prefix . 'portfolio_column_grid_content_title',
			'desc'		=> 'Ex: Project Details',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_content_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Content Subtitle Text',
			'id'		=> $prefix . 'portfolio_column_grid_content_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_content_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Content Section Number',
			'id'		=> $prefix . 'portfolio_column_grid_content_number',
			'desc'		=> 'Ex: 02.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_content_title_section', '!=', 'no' )
		   ),		   		   
		array(
		   'name'     => esc_attr__( 'Project Information', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_project_info',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),		   
		array(
				'id'		=> $prefix . 'portfolio_column_grid_project_info_main',
				'name'        => 'Project Info Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Project Info List', // ID of the subfield
				'save_state' => true,
				'fields' => array(
					array(
						'name' => 'Data Title',
						'id'		=> $prefix . 'port_column_grid_dt_in_title',
						'type' => 'text',
						'desc'		=> 'Ex: Location',
					),
					
					array(
						'name' => 'Data Subtitle',
						'id'		=> $prefix . 'port_column_grid_dt_in_subtitle',
						'type' => 'text',
						'desc'		=> 'Ex: NY , USA',
					),
					
					array(
						'name' => 'Data Subtitle Link URL',
						'id'		=> $prefix . 'port_column_grid_dt_in_subtitle_url',
						'type' => 'text',
						'desc'		=> '',
					),					
					array(
					   'name'     => esc_attr__( 'Data Link URL Open In', 'kotlis' ),
					   'id'   => $prefix . 'port_column_grid_dt_in_subtitle_url_target',
					   'desc' => '',
					   'type'     => 'radio',
					   // Array of 'value' => 'Label' pairs for select box
					   'options'  => array(
					   '_self' => esc_attr__( 'Same Tab', 'kotlis' ),
						'_blank' => esc_attr__( 'New Tab', 'kotlis' ),			
					   ),
					   // Select multiple values, optional. Default is false.
					   'std'         => '_self',

					  ),
				),
				'visible' => array( 'rnr_portfolio_column_grid_project_info', '!=', 'no' )
			),
        array(
		   'name'     => esc_attr__( 'Project Button', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_details_info_button_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',

		),			
		array(
			'name'		=> 'Button Text',
			'id'		=> $prefix . 'port_column_grid_dt_in_button_text',
			'desc'		=> 'Ex: View Project',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_details_info_button_show', '!=', 'st2')
		   ),
		array(
			'name'		=> 'Project Button URL',
			'id'		=> $prefix . 'port_column_grid_dt_in_button_url',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_grid_details_info_button_show', '!=', 'st2')
		   ),	
		array(
		   'name'     => esc_attr__( 'Project Button URL Open In', 'kotlis' ),
		   'id'   => $prefix . 'port_column_grid_dt_in_button_link_target',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
		   'no' => esc_attr__( 'Same Tab', 'kotlis' ),
			'yes' => esc_attr__( 'New Tab', 'kotlis' ),			
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
           'visible' => array( 'rnr_portfolio_column_grid_details_info_button_show', '!=', 'st2')
		  ),		   
		array(
		   'name'     => esc_attr__( 'Prev / Next Post', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_grid_project_prev_next',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),			
	)
);


/* ----------------------------------------------------- */
/* Portfolio Details Carousel Style 2
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'port_details_page_header_type2',
	'title' => 'Portfolio Carousel Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt'  => 'st2',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(

		array(
		'name'		=> 'Carousel Images',
		'id'		=> $prefix . 'th_gallery_imge_st2',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1000',
		'desc'		=> 'Add Youtube/ Vimeo video URL in image description for popup video.',
		),
		
		array(
		   'name'     => esc_attr__( 'Thumbnails Images', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_details_thumb',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Thumbnails Text',
			'id'		=> $prefix . 'portfolio_carousel_details_thumb_title',
			'desc'		=> 'Replace "Show Thumbnails" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_carousel_details_thumb', '!=', 'no' )
		   ),	
		array(
		   'name'     => esc_attr__( 'Details Info', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_details_view',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Details Text',
			'id'		=> $prefix . 'portfolio_carousel_details_view_title',
			'desc'		=> 'Replace "View Details" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_carousel_details_view', '!=', 'no')
		   ),
		array(
		   'name'     => esc_attr__( 'Project Details Title', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_details_info_title_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',
		  ),		   
		array(
			'name'		=> 'Details Title Text',
			'id'		=> $prefix . 'portfolio_carousel_details_info_title',
			'desc'		=> 'Ex: Welcome To New York',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_carousel_details_info_title_show', '!=', 'st2')
		   ),		   
		array(
		   'name'     => esc_attr__( 'Project Information', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_project_info',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
		  ),		   
		array(
			'id'		=> $prefix . 'portfolio_carousel_project_info_main',
			'name'        => 'Project Info Item',
			'type'        => 'group',
			'clone'       => true,
			'sort_clone'  => true,
			'collapsible' => true,
			'group_title' => 'Project Info List', // ID of the subfield
			'save_state' => true,
			'fields' => array(
				array(
					'name' => 'Data Title',
					'id'   => $prefix . 'port_car_dt_in_title',
					'type' => 'text',
					'desc'		=> 'Ex: Location',
				),
				
				array(
					'name' => 'Data Subtitle',
					'id'   => $prefix . 'port_car_dt_in_subtitle',
					'type' => 'text',
					'desc'		=> 'Ex: NY , USA',
				),
				array(
					'name' => 'Data Subtitle Link URL',
					'id'		=> $prefix . 'port_car_dt_in_subtitle_url',
					'type' => 'text',
					'desc'		=> '',
				),
				array(
				   'name'     => esc_attr__( 'Data Link URL Open In', 'kotlis' ),
				   'id'   => $prefix . 'port_car_dt_in_subtitle_url_target',
				   'desc' => '',
				   'type'     => 'radio',
				   // Array of 'value' => 'Label' pairs for select box
				   'options'  => array(
				   '_self' => esc_attr__( 'Same Tab', 'kotlis' ),
					'_blank' => esc_attr__( 'New Tab', 'kotlis' ),			
				   ),
				   // Select multiple values, optional. Default is false.
				   'std'         => '_self',

				),					
			),
			'visible' => array( 'rnr_portfolio_carousel_project_info', '!=', 'no' )
		),	
		array(
		   'name'     => esc_attr__( 'Project Button', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_details_info_button_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',

		),
		array(
			'name'		=> 'Project Button Text',
			'id'		=> $prefix . 'port_car_dt_in_button_text',
			'desc'		=> 'Ex: View Project',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_carousel_details_info_button_show', '!=', 'st2')
		   ),
		array(
			'name'		=> 'Project Button URL',
			'id'		=> $prefix . 'port_car_dt_in_button_url',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_carousel_details_info_button_show', '!=', 'st2')
		   ),	
		array(
		   'name'     => esc_attr__( 'Project Button URL Open In', 'kotlis' ),
		   'id'   => $prefix . 'port_car_dt_in_button_link_target',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
		   'no' => esc_attr__( 'Same Tab', 'kotlis' ),
			'yes' => esc_attr__( 'New Tab', 'kotlis' ),			
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
		   'visible' => array( 'rnr_portfolio_carousel_details_info_button_show', '!=', 'st2')
            
		  ),		   
		array(
		   'name'     => esc_attr__( 'Prev / Next Post', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_carousel_project_prev_next',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),			
	)
);


/* ----------------------------------------------------- */
/* Portfolio Column Fullwidth style 1
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'port_details_page_sidebar_type2',
	'title' => 'Portfolio Column Fullwidth Sidebar Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt'  => 'st3',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(
	
		array(
		'name'		=> 'Background Images',
		'id'		=> $prefix . 'portfolio_column_fullwidth_details_sidebar_image',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1',
		'desc'		=> '',
		),	
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'portfolio_column_fullwidth_details_sidebar_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'portfolio_column_fullwidth_details_sidebar_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'portfolio_column_fullwidth_details_scroll_swipe',
			   'desc' => '',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'portfolio_column_fullwidth_details_translet_scroll',
			'desc'		=> 'Replace "Scroll Down To Discover" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_details_scroll_swipe', '!=', 'no' )
		   ),
	
	)
);

$meta_boxes[] = array(
	'id' => 'port_details_page_content_type2',
	'title' => 'Portfolio Column Fullwidth Content Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt' => 'st3',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(
		array(
		   'name'     => esc_attr__( 'Gallery Title Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_gallery_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Gallery Title Text',
			'id'		=> $prefix . 'portfolio_column_fullwidth_gallery_title',
			'desc'		=> 'Ex: Project Gallery',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_gallery_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Gallery Subtitle Text',
			'id'		=> $prefix . 'portfolio_column_fullwidth_gallery_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_gallery_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Gallery Section Number',
			'id'		=> $prefix . 'portfolio_column_fullwidth_gallery_number',
			'desc'		=> 'Ex: 01.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_gallery_title_section', '!=', 'no' )
		   ),	
		array(
		   'name'     => esc_attr__( 'Gallery Images Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_gallery_images_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),		   
		array(
		'name'		=> 'Gallery Images',
		'id'		=> $prefix . 'portfolio_column_fullwidth_gallery_images',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1000',
		'desc'		=> 'Add Youtube/ Vimeo video URL in image description for popup video.',
		'visible' => array( 'rnr_portfolio_column_fullwidth_gallery_images_section', '!=', 'no' )
		),
		
		array(
		   'name'     => esc_attr__( 'Content Title Section', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_content_title_section',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Content Title Text',
			'id'		=> $prefix . 'portfolio_column_fullwidth_content_title',
			'desc'		=> 'Ex: Project Details',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_content_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Content Subtitle Text',
			'id'		=> $prefix . 'portfolio_column_fullwidth_content_subtitle',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_content_title_section', '!=', 'no' )
		   ),
		array(
			'name'		=> 'Content Section Number',
			'id'		=> $prefix . 'portfolio_column_fullwidth_content_number',
			'desc'		=> 'Ex: 02.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_content_title_section', '!=', 'no' )
		   ),		   		   
		array(
		   'name'     => esc_attr__( 'Project Information', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_project_info',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),		   
		array(
				'id'		=> $prefix . 'portfolio_column_fullwidth_project_info_main',
				'name'        => 'Project Info Item',
				'type'        => 'group',
				'clone'       => true,
				'sort_clone'  => true,
				'collapsible' => true,
				'group_title' => 'Project Info List', // ID of the subfield
				'save_state' => true,
				'fields' => array(

					array(
						'name' => 'Data Title',
						'id'   => $prefix . 'port_column_fullwidth_dt_in_title',
						'type' => 'text',
						'desc'		=> 'Ex: Location',
					),
					
					array(
						'name' => 'Data Subtitle',
						'id'   => $prefix . 'port_column_fullwidth_dt_in_subtitle',
						'type' => 'text',
						'desc'		=> 'Ex: NY , USA',
					),
					array(
						'name' => 'Data Subtitle Link URL',
						'id'		=> $prefix . 'port_column_fullwidth_dt_in_subtitle_url',
						'type' => 'text',
						'desc'		=> '',
					),	
					array(
					   'name'     => esc_attr__( 'Data Link URL Open In', 'kotlis' ),
					   'id'   => $prefix . 'port_column_fullwidth_dt_in_subtitle_url_target',
					   'desc' => '',
					   'type'     => 'radio',
					   // Array of 'value' => 'Label' pairs for select box
					   'options'  => array(
					   '_self' => esc_attr__( 'Same Tab', 'kotlis' ),
						'_blank' => esc_attr__( 'New Tab', 'kotlis' ),			
					   ),
					   // Select multiple values, optional. Default is false.
					   'std'         => '_self',

					),					
				),
				'visible' => array( 'rnr_portfolio_column_fullwidth_project_info', '!=', 'no' )
			),	
        array(
		   'name'     => esc_attr__( 'Project Button', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_details_info_button_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',

		),			
		array(
			'name'		=> 'Project Button Text',
			'id'		=> $prefix . 'port_column_fullwidth_dt_in_button_text',
			'desc'		=> 'Ex: View Project',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_details_info_button_show', '!=', 'st2')
		   ),
		array(
			'name'		=> 'Project Button URL',
			'id'		=> $prefix . 'port_column_fullwidth_dt_in_button_url',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_column_fullwidth_details_info_button_show', '!=', 'st2')
		   ),	
		array(
		   'name'     => esc_attr__( 'Project Button URL Open In', 'kotlis' ),
		   'id'   => $prefix . 'port_column_fullwidth_dt_in_button_link_target',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
		   'no' => esc_attr__( 'Same Tab', 'kotlis' ),
			'yes' => esc_attr__( 'New Tab', 'kotlis' ),			
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
			'visible' => array( 'rnr_portfolio_column_fullwidth_details_info_button_show', '!=', 'st2')
		  ),		   
		array(
		   'name'     => esc_attr__( 'Prev / Next Post', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_column_fullwidth_project_prev_next',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),			
	)
);

/* ----------------------------------------------------- */
/* Portfolio Details Fullscreen Slider style 4
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'port_details_page_header_type4',
	'title' => 'Portfolio Fullscreen Slider Options',
	// Show this meta box for posts matched below conditions
	'show'   => array(
	'input_value'   => array(
    '#rnr_wr_port_dt_opt'  => 'st4',
    ),
	),
    'pages' => array( 'portfolio'),
	'context' => 'normal',	

	'fields' => array(
	
		array(
		'name'		=> 'Slider Images',
		'id'		=> $prefix . 'th_gallery_imge_st4',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1000',
		'desc'		=> 'Add Youtube/ Vimeo video URL in image description for popup video.',
		),
		array(
		   'name'     => esc_attr__( 'Fullscreen Mode', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_details_screen',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Fullscreen Mode Text',
			'id'		=> $prefix . 'portfolio_full_slider_details_screen_title',
			'desc'		=> 'Replace "Fullscreen Mode" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_screen', '!=', 'no' )	
		   ),		
		array(
		   'name'     => esc_attr__( 'Thumbnails Images', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_details_thumb',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),
		array(
			'name'		=> 'Thumbnails Text',
			'id'		=> $prefix . 'portfolio_full_slider_details_thumb_title',
			'desc'		=> 'Replace "Show Thumbnails" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_thumb', '!=', 'no' )			
		   ),	
		array(
		   'name'     => esc_attr__( 'Details Info', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_details_view',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
		  ),
		array(
			'name'		=> 'Details Text',
			'id'		=> $prefix . 'portfolio_full_slider_details_view_title',
			'desc'		=> 'Replace "View Details" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_view', '!=', 'no')
		   ),
		array(
		   'name'     => esc_attr__( 'Project Details Title', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_details_info_title_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',
		  ),   
		array(
			'name'		=> 'Details Title Text',
			'id'		=> $prefix . 'portfolio_full_slider_details_info_title',
			'desc'		=> 'Ex: Welcome To New York',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_info_title_show', '!=', 'st2')
		   ),		   
		array(
		   'name'     => esc_attr__( 'Project Information', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_project_info',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),		   
		array(
			'id'		=> $prefix . 'portfolio_full_slider_project_info_main',
			'name'        => 'Project Info Item',
			'type'        => 'group',
			'clone'       => true,
			'sort_clone'  => true,
			'collapsible' => true,
			'group_title' => 'Project Info List', // ID of the subfield
			'save_state' => true,
			'fields' => array(
				array(
					'name' => 'Data Title',
					'id'   => $prefix . 'port_fl_sl_dt_in_title',
					'type' => 'text',
					'desc'		=> 'Ex: Location',
				),					
				array(
					'name' => 'Data Subtitle',
					'id'   => $prefix . 'port_fl_sl_dt_in_subtitle',
					'type' => 'text',
					'desc'		=> 'Ex: NY , USA',
				),
				array(
					'name' => 'Data Subtitle Link URL',
					'id'		=> $prefix . 'port_fl_sl_dt_in_subtitle_url',
					'type' => 'text',
					'desc'		=> '',
				),	
				array(
				   'name'     => esc_attr__( 'Data Link URL Open In', 'kotlis' ),
				   'id'   => $prefix . 'port_fl_sl_dt_in_subtitle_urltarget',
				   'desc' => '',
				   'type'     => 'radio',
				   // Array of 'value' => 'Label' pairs for select box
				   'options'  => array(
				   '_self' => esc_attr__( 'Same Tab', 'kotlis' ),
					'_blank' => esc_attr__( 'New Tab', 'kotlis' ),			
				   ),
				   // Select multiple values, optional. Default is false.
				   'std'         => '_self',

				),					
			),
			'visible' => array( 'rnr_portfolio_full_slider_project_info', '!=', 'no' )
		),
        array(
		   'name'     => esc_attr__( 'Project Button', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_details_info_button_show',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'st1' => esc_attr__( 'Enable', 'kotlis' ),
			'st2' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'st1',

		),		
		array(
			'name'		=> 'Project Button Text',
			'id'		=> $prefix . 'port_fl_sl_dt_in_button_text',
			'desc'		=> 'Ex: View Project',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_info_button_show', '!=', 'st2')
		   ),
		array(
			'name'		=> 'Project Button URL',
			'id'		=> $prefix . 'port_fl_sl_dt_in_button_url',
			'desc'		=> '',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_portfolio_full_slider_details_info_button_show', '!=', 'st2')
		   ),	
		array(
		   'name'     => esc_attr__( 'Project Button URL Open In', 'kotlis' ),
		   'id'   => $prefix . 'port_fl_sl_dt_in_button_link_target',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
		   'no' => esc_attr__( 'Same Tab', 'kotlis' ),
			'yes' => esc_attr__( 'New Tab', 'kotlis' ),			
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',
			'visible' => array( 'rnr_portfolio_full_slider_details_info_button_show', '!=', 'st2')
		  ),		   
		array(
		   'name'     => esc_attr__( 'Prev / Next Post', 'kotlis' ),
		   'id'   => $prefix . 'portfolio_full_slider_project_prev_next',
		   'desc' => '',
		   'type'     => 'radio',
		   // Array of 'value' => 'Label' pairs for select box
		   'options'  => array(
			'yes' => esc_attr__( 'Enable', 'kotlis' ),
			'no' => esc_attr__( 'Disable', 'kotlis' ),
		   ),
		   // Select multiple values, optional. Default is false.
		   'std'         => 'yes',

		  ),			
	
	)
);


/* ----------------------------------------------------- */
/* Blog Page Type Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'blog_page_type',
	'title' => 'Blog Page Template Function',
	// Show this meta box for posts matched below conditions
        'show'   => array(
    // List of page templates (used for page only). Array. Optional.
    'template'    => array( 'blog.php'),
	),
	'pages' => array( 'page' ),
	'context' => 'normal',	
	'fields' => array(
		
		// SELECT BOX
		array(
			'name'     => esc_attr__( 'Blog Layout', 'dogmawp' ),
			'id'   => $prefix . 'wrblog-pagetype',
			'desc'  => __( 'Works only Blog Page Template', 'dogmawp' ),
			'type'     => 'image_select',
			// Array of 'value' => 'Label' pairs for select box
			'options'  => array(
				'st0' => __( get_template_directory_uri().'/includes/metaboxes/img/wr-page-default.png', 'gorge' ),
				'st1' => esc_attr__( get_template_directory_uri().'/includes/metaboxes/img/wr-page-right.png', 'dogmawp' ),
				'st2' => esc_attr__( get_template_directory_uri().'/includes/metaboxes/img/wr-page-left.png', 'dogmawp' ),
				
				
			),
			'desc'  => esc_attr__( '', 'dogmawp' ),
			// Select multiple values, optional. Default is false.
			'multiple'    => false,
			'std'         => 'st0',
			'placeholder' => __( 'Select an Option', 'dogmawp' ),
		),
		  array(
				'name'       => esc_attr__( 'Number Of Post Show', 'kotlis' ),
				'id'         => $prefix . 'blog-post-show',
				'desc'		=> 'Show number of latest post',
				'type'       => 'slider',
				// Text labels displayed before and after value
				'prefix'     => __( '', 'kotlis' ),
				'suffix'     => __( ' Posts', 'kotlis' ),
				'js_options' => array(
					'min'  => 1,
					'max'  => 100,
					'step' => 1,
				),
			),	

			array(
			'name'		=> 'Include Category',
			'id'		=> $prefix . 'blog-post-cat',
			'desc'		=> 'Enter category name ex: web design, web development (Optional).',			
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> ''
		   ), 		
			
	)
);


/* ----------------------------------------------------- */
/* Blog Page Header & Sideblock options
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_blog_page_sidebar_opt',
	'title' => 'Header & Sideblock Options.',
	'show'   => array(
    // List of page templates (used for page only). Array. Optional.
    'template'    => array( 'blog.php'),
	),	
	'pages' => array( 'page' ),
	'context' => 'normal',	

	'fields' => array(
		
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'blog_right_block_header_title',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'blog_right_block_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),		
		array(
			   'name'     => esc_attr__( 'Page Header Section', 'kotlis' ),
			   'id'   => $prefix . 'blog_header_block',
			   'desc' => 'This option is only available at the "Right & Left Sidebar Layout" style.',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),					
		    array(
			   'name'     => esc_attr__( 'Scroll Down', 'kotlis' ),
			   'id'   => $prefix . 'blog_right_block_scroll_swipe',
			   'desc' => 'This option is only available at the "Default Layout" style.',
			   'type'     => 'radio',
			   // Array of 'value' => 'Label' pairs for select box
			   'options'  => array(
				'yes' => esc_attr__( 'Enable', 'kotlis' ),
				'no' => esc_attr__( 'Disable', 'kotlis' ),
			   ),
			   // Select multiple values, optional. Default is false.
			   'std'         => 'yes',

		    ),		   
		   array(
			'name'		=> 'Scroll Down Text',
			'id'		=> $prefix . 'blog_right_block_translet_opt3',
			'desc'		=> 'Replace "Scroll down or Swipe" text here.',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'visible' => array( 'rnr_blog_right_block_scroll_swipe', '!=', 'no' )
		   ),
		
	)
);

// Blog Post Metaboxes
/* ----------------------------------------------------- */
$meta_boxes[] = array(
	'id' => 'th_blog_single_page_sidebar_opt',
	'title' => 'Header & Sideblock Options.',	
	'pages' => array( 'post' ),
	'context' => 'normal',	

	'fields' => array(
		
		array(
			'name'		=> 'Title',
			'id'		=> $prefix . 'blog_single_right_block_header_title',
			'clone'		=> false,
			'type'		=> 'text',
			'std'		=> '',
			'desc'		=> '',
		),
		
		array(
			'name'		=> 'Subtitle',
			'id'		=> $prefix . 'blog_single_right_block_header_subtitle',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> '',
			'desc'		=> '',
		),
		array(
		'name'		=> 'Background Image',
		'id'		=> $prefix . 'wr_blog_header_img',
		'clone'		=> false,
		'type'		=> 'image_advanced',
		'max_file_uploads' => '1',
		'desc'		=> '',
		),		
		
	)
);

$meta_boxes[] = array(
	'id' => 'rnr-blogmeta-video',
	'title' => 'Post Format Video Option',
	'show'   => array(
    'post_format' => array( 'Video' ),
	),
	'pages' => array( 'post'),
	'context' => 'normal',

	// List of meta fields
	'fields' => array(

		array(
			'name'		=> 'Vimeo/YouTube Video Embed Link:',
			'id'		=> $prefix . 'bl-video',
			'desc'		=> 'Insert Vimeo/YouTube Video Embed Link URL Here.',
			'clone'		=> false,
			'type'		=> 'textarea',
			'std'		=> ''
		),
		
	)
);
$meta_boxes[] = array(
	'id' => 'rnr-blogmeta-gallery',
	'title' => 'Post Format Gallery Option',
	'show'   => array(
    'post_format' => array( 'Gallery' ),
	),
	'pages' => array( 'post'),
	'context' => 'normal',

	// List of meta fields
	'fields' => array(

		array(
			'name'		=> 'Upload  Slider Images',
			'id'		=> $prefix . 'wr_galleryimg_blog',
			'clone'		=> false,
			'type'		=> 'image_advanced',
			'desc'		=> '',
		),

		
	)
);

/********************* META BOX REGISTERING ***********************/

/**
 * Register meta boxes
 *
 * @return void
 */
function kotlis_register_meta_boxes()
{
	global $meta_boxes;

	// Make sure there's no errors when the plugin is deactivated or during upgrade
	if ( class_exists( 'RW_Meta_Box' ) )
	{
		foreach ( $meta_boxes as $meta_box )
		{
			new RW_Meta_Box( $meta_box );
		}
	}
}

// Hook to 'admin_init' to make sure the meta box class is loaded before
// (in case using the meta box class in another plugin)
// This is also helpful for some conditionals like checking page template, categories, etc.
add_action( 'admin_init', 'kotlis_register_meta_boxes' );