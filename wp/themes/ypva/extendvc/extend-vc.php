<?php
/*** Removing shortcodes ***/
vc_remove_element("vc_widget_sidebar");
vc_remove_element("vc_gallery");
vc_remove_element("vc_wp_search");
vc_remove_element("vc_wp_meta");
vc_remove_element("vc_wp_recentcomments");
vc_remove_element("vc_wp_calendar");
vc_remove_element("vc_wp_pages");
vc_remove_element("vc_wp_tagcloud");
vc_remove_element("vc_wp_custommenu");
vc_remove_element("vc_wp_text");
vc_remove_element("vc_wp_posts");
vc_remove_element("vc_wp_links");
vc_remove_element("vc_wp_categories");
vc_remove_element("vc_wp_archives");
vc_remove_element("vc_wp_rss");
vc_remove_element("vc_teaser_grid");
vc_remove_element("vc_button");
vc_remove_element("vc_button2");
vc_remove_element("vc_cta_button");
vc_remove_element("vc_cta_button2");
vc_remove_element("vc_message");
vc_remove_element("vc_tour");
vc_remove_element("vc_progress_bar");
vc_remove_element("vc_pie");
vc_remove_element("vc_posts_slider");
vc_remove_element("vc_toggle");
vc_remove_element("vc_images_carousel");
vc_remove_element("vc_posts_grid");
vc_remove_element("vc_carousel");

/*** Remove unused parameters ***/
if (function_exists('vc_remove_param')) {
	vc_remove_param('vc_single_image', 'css_animation');
	vc_remove_param('vc_column_text', 'css_animation');
	vc_remove_param('vc_row', 'video_bg');
	vc_remove_param('vc_row', 'video_bg_url');
	vc_remove_param('vc_row', 'video_bg_parallax');
	vc_remove_param('vc_row', 'full_height');
	vc_remove_param('vc_row', 'content_placement');
	vc_remove_param('vc_row', 'full_width');
	vc_remove_param('vc_row', 'bg_image');
	vc_remove_param('vc_row', 'bg_color');
	vc_remove_param('vc_row', 'font_color');
	vc_remove_param('vc_row', 'margin_bottom');
	vc_remove_param('vc_row', 'bg_image_repeat');
	vc_remove_param('vc_tabs', 'interval');
	vc_remove_param('vc_separator', 'style');
	vc_remove_param('vc_separator', 'color');
	vc_remove_param('vc_separator', 'accent_color');
	vc_remove_param('vc_separator', 'el_width');
	vc_remove_param('vc_text_separator', 'style');
	vc_remove_param('vc_text_separator', 'color');
	vc_remove_param('vc_text_separator', 'accent_color');
	vc_remove_param('vc_text_separator', 'el_width');
	vc_remove_param('vc_row', 'gap');
    vc_remove_param('vc_row', 'columns_placement');
    vc_remove_param('vc_row', 'equal_height');
    vc_remove_param('vc_row_inner', 'gap');
    vc_remove_param('vc_row_inner', 'content_placement');
    vc_remove_param('vc_row_inner', 'equal_height');
    vc_remove_param('vc_hoverbox', 'use_custom_fonts_primary_title');
    vc_remove_param('vc_hoverbox', 'use_custom_fonts_hover_title');
    vc_remove_param('vc_hoverbox', 'hover_add_button');
	vc_remove_param('vc_row', 'parallax');
    vc_remove_param('vc_row', 'parallax_image');
	vc_remove_param('vc_row', 'parallax_speed_bg');
	vc_remove_param('vc_row', 'parallax_speed_video');
	vc_remove_param('vc_row', 'disable_element');
	vc_remove_param('vc_row', 'el_id');
	vc_remove_param('vc_row', 'el_class');
	//vc_remove_param('vc_row', 'css_animation');
}

/*** Row ***/

vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"show_settings_on_create"=>true,
	"heading" => "Row Type",
	"param_name" => "row_type",
	"value" => array(
		
		"Blank Section" => "st1",
		"webRedox Row" => "main-section",
		"webRedox Section" => "featured-section",
	)
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Layout",
	"param_name" => "wr_section_layout",
	"value" => array(
		"Full Width" => "section_full_width",
		"In Grid" => "section_grid",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Grid Layout",
	"param_name" => "wr_section_layout_grid",
	"value" => array(
		"Default" => "",
		"Fluid" => "section_layout_grid",
		"Fluid 2" => "section_layout_grid2",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section ID",
	"param_name" => "wr_section_id",
	"value" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section Class",
	"param_name" => "wr_section_class",
	"value" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section ID",
	"param_name" => "wr_section_id2",
	"value" => "",
	"description" => esc_attr__("Please insert page section ID here. Ex: sec1", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section Title",
	"param_name" => "wr_section_title",
	"value" => "",
	"description" => esc_attr__("Ex: My Little Story", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textarea",
	"class" => "",
	"heading" => "Section Subtitle",
	"param_name" => "wr_section_subtitle",
	"value" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section Number",
	"param_name" => "wr_section_number",
	"value" => "",
	"description" => esc_attr__("Ex: 01.", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Separator",
	"param_name" => "wr_separator",
	"value" => array(
		"Enable" => "st1",
		"Disable" => "st2",					
	),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))	
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Section Class",
	"param_name" => "wr_section_class2",
	"value" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Default Class",
	"param_name" => "wr_default_class2",
	"value" => array(
		"Disable" => "",
		"Enable" => "",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Height",
	"param_name" => "wr_section_height",
	"value" => "",
	"description" => esc_attr__("Please insert height in format: 300px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "colorpicker",
	"class" => "",
	"heading" => "Background Color",
	"param_name" => "wr_background_color",
	"value" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
/*
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Background Color Scheme",
	"param_name" => "wr_color_scheme",
	"value" => array(
		"Disable" => "",
		"Scheme 1" => "bg-light-custom",
		"Scheme 2" => "bg-primary",
		"Scheme 3" => "bg-light",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
*/
vc_add_param("vc_row", array(
	"type" => "attach_image",
	"class" => "",
	"heading" => "Background Image",
	"value" => "",
	"param_name" => "wr_background_img",
	"description" => "",
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Parallax",
	"param_name" => "wr_background_parallax",
	"value" => array(
		"Disable" => "inherit",
		"Enable" => "fixed",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Background Parallax Effect",
	"param_name" => "wr_background_parallaxx",
	"value" => array(
		"Disable" => "",
		"Enable" => "hire-me parallax cover-bg",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Background Parallax Fancy Effect",
	"param_name" => "wr_background_fancy",
	"value" => array(
		"Disable" => "",
		"Enable" => "parallax-overlay",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Default Padding",
	"param_name" => "wr_default_pad",
	"value" => array(
		"Disable" => "",
		"Space 100 100" => "section-padding",
		"Space 100 0" => "section-padding pb-0",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "dropdown",
	"class" => "",
	"heading" => "Section Default Padding",
	"param_name" => "wr_default_pad2",
	"value" => array(
		"Disable" => "",
		"Enable" => "inner-top",
		"No Padding" => "no-pad",
	),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Padding Top",
	"param_name" => "wr_padding_top",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Padding Top",
	"param_name" => "wr_padding_top2",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Padding Bottom",
	"param_name" => "wr_padding_bottom",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Padding Bottom",
	"param_name" => "wr_padding_bottom2",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),	
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Margin Top",
	"param_name" => "wr_margin_top",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Margin Top",
	"param_name" => "wr_margin_top2",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Margin Bottom",
	"param_name" => "wr_margin_bottom",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('main-section'))
));
vc_add_param("vc_row", array(
	"type" => "textfield",
	"class" => "",
	"heading" => "Margin Bottom",
	"param_name" => "wr_margin_bottom2",
	"value" => "",
	"description" => esc_attr__("In format: 10px", 'kotlis'),
	"dependency" => Array('element' => "row_type", 'value' => array('featured-section'))
));

/***************** webRedox Shortcodes *********************/

// Title Block
vc_map( array(
		"name" => "Kotlis Heading",
		"base" => "wr_vc_section_title",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Title Style",
				"param_name" => "featyretype",
				"value" => array(
					"Style 1" => "st1",
					"Style 2" => "st2",			
				),
				"description" => ""
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Title",
				"param_name" => "title",
				"value" => "",
				"description" => __("Ex: Skills and Attainments", 'kotlis'),
				"admin_label" => true,
			),
			array(
				"type" => "textarea",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Subtitle",
				"param_name" => "title2",
				"value" => "",
				"admin_label" => true,
				"dependency" => Array('element' => "featyretype", 'value' => array('st1'))
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Section Number",
				"param_name" => "title3",
				"value" => "",
				"description" => __("Ex: 02.", 'kotlis'),
				"admin_label" => true,
				"dependency" => Array('element' => "featyretype", 'value' => array('st1'))
			),					
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Text Align",
				"param_name" => "float",
				"value" => array(
					"Left" => "text-left",
					"Right" => "text-right",
					"Center" => "text-center",
				),
				"dependency" => Array('element' => "featyretype", 'value' => array('st2'))
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Margin",
				"param_name" => "margin2",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding2",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),				
			
		)
) );

// Text Block
vc_map( array(
		"name" => "Kotlis Text Block",
		"base" => "wr_vc_section_text",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),						
			array(
				"type" => "textarea_html",
				"holder" => "div",
				"class" => "",
				"heading" => "Content Text",
				"param_name" => "content",
				"value" => "I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo."
			),			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Float",
				"param_name" => "float",
				"value" => array(
					"Default" => "",
					"Left" => "float-left",
					"Right" => "float-right",
					"Center" => "float-center",
				)
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Margin",
				"param_name" => "margin2",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding2",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),				
			
		)
) );
// Image Block
vc_map( array(
		"name" => "Kotlis Image",
		"base" => "wr_vc_section_image",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Style Type",
				"param_name" => "featyretype",
				"value" => array(
					"Style 1" => "st1",
					"Style 2" => "st2",
					
				),
				"description" => ""
			),			
			array(
				"type" => "attach_image",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Upload Image",
				"param_name" => "img_url",
				"value" => "",
				"admin_label" => true,
			),		
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Custom URL",
				"param_name" => "link_url",
				"value" => "",
				"admin_label" => true,
			),			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Link Target",
				"param_name" => "link_target",
				"value" => array(
					"Self" => "_self",
					"Blank" => "_blank",
					"Parent" => "_parent",	
					"Top" => "_top"	
				),
				"description" => ""
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Width",
				"param_name" => "width",
				"value" => ""
			),				
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Height",
				"param_name" => "height",
				"value" => ""
			),
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Float",
				"param_name" => "float",
				"value" => array(
					"None" => "none",
					"Left" => "left",
					"Right" => "right",
				)

			),				
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Position",
				"param_name" => "position",
				"value" => array(
					"Default" => "inherit",
					"Absolute" => "absolute",
					"Relative" => "relative",
					"Static" => "static",
					
				),
				"description" => ""
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Top",
				"param_name" => "top",
				"value" => ""
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Bottom",
				"param_name" => "Bottom",
				"value" => ""
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Left",
				"param_name" => "left",
				"value" => ""
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Right",
				"param_name" => "right",
				"value" => ""
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Z-index",
				"param_name" => "zindex",
				"value" => ""
			),				
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Margin",
				"param_name" => "margin",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),

		)
) );
// image Gllery
vc_map( array(
		"name" => "Kotlis Image Gallery",
		"base" => "kotlis_image_gallery",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_container_element" => 'vc_row',
		"params" => array(
			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Gallery Column",
				"param_name" => "gallery_column",
				"value" => array(
					"4 Column" => "four-column",
					"3 Column" => "three-column",
					"2 Column" => "two-column",
					"1 Column" => "one-column",
				),
				"description" => ""
			),
			array(
				"type" => "attach_images",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Upload Images",
				"param_name" => "image",
				"description" => "",
				"admin_label" => true,
			),
			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Image Title",
				"param_name" => "image_title",
				"value" => array(
					"Disable" => "st1",
					"Enable" => "st2",
				),
				"description" => ""
			),
			
			
				
		)
) );
// Button Block
vc_map( array(
		"name" => "Kotlis Button",
		"base" => "wr_vc_button",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button text",
				"param_name" => "button_name",
				"value" => "",
				"admin_label" => true,
			),							
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button URL",
				"param_name" => "link_url",
				"value" => "",
				"admin_label" => true,
			),			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button Target",
				"param_name" => "link_target",
				"value" => array(
				    "" => "",
					"Self" => "_self",
					"Blank" => "_blank",
					"Parent" => "_parent",	
					"Top" => "_top"	
				),
				"description" => ""
			),	
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Scroll Link",
				"param_name" => "custom_scroll",
				"value" => array(
					"Disable" => "",
					"Enable" => "custom-scroll-link",
				),
				"description" => __("Please insert scroll link in format: #sec3", 'kotlis'),
			),			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Float",
				"param_name" => "float",
				"value" => array(
					"Default" => "",
					"Left" => "float-left",
					"Right" => "float-right",
					"Center" => "float-center",
				)
			),				
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => " Margin",
				"param_name" => "margin",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),					
		)
) );
// Separator Block
vc_map( array(
		"name" => "Kotlis Separator",
		"base" => "wr_vc_divider",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),					
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Margin",
				"param_name" => "margin",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),		
		)
) );
// Portfolio Block
vc_map( array(
		"name" => "Kotlis Portfolio",
		"base" => "wr_vc_portfolio",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),						
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => esc_html__('Portfolio Filter', 'tank'),
				"param_name" => "enable_filter",
				"value" => array(
					"Disable" => "st1",
					"Enable" => "st2",
				),
				"description" => "Enable/ Disable Portfolio Filter.",
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "All Text",
				"param_name" => "text_filter",
				"value" => "",
				"description" => "Translet Option.",
				"dependency" => Array('element' => "enable_filter", 'value' => array('st2')),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Number of categories to show",
				"param_name" => "port_filter_cat_count",
				"value" => "",
				"description" => "Number of categories to show in portfolio filter list. Ex: 5",
				"dependency" => Array('element' => "enable_filter", 'value' => array('st2')),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Exclude category",
				"param_name" => "port_filter_cat_exclude",
				"value" => "",
				"description" => "Exclude category from the filter list by the category ID e.x: 6 <br>For multiple category ID Ex: 6 7",
				"dependency" => Array('element' => "enable_filter", 'value' => array('st2')),
				"admin_label" => true,
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Number Of Posts To Show",
				"param_name" => "postcount",
				"value" => "100",
				"description" => esc_html__("Insert number of post show in format: 8", 'kotlis'),
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Include Post ID",
				"param_name" => "in_post_id",
				"value" => "",
				"admin_label" => true,
				"description" => __("(Optional) Insert post ID to show selected  posts. e.x: 1,2", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Include Category",
				"param_name" => "categoryname",
				"value" => "",
				"description" => esc_html__("(Optional) Insert category name in format: Graphic", 'kotlis'),
				"admin_label" => true,
			),				
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => esc_attr__('Post Offset', 'kotlis'),
				"param_name" => "postoffset",
				"value" => "",
				"description" => esc_attr__('(Optional)Use this field if you need.', 'kotlis'),
			),			
		)
) );
// Counter Block
vc_map( array(
		"name" => "Kotlis Counter",
		"base" => "wr_vc_counter",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_container_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),						
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "1st Counter Name",
				"param_name" => "counter_name1",
				"value" => "",
				"description" => __("Please insert counter name in format: Finished projects", 'kotlis'),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "1st Counter Number",
				"param_name" => "counter_num1",
				"value" => "",
				"description" => __("Please insert counter number in format: 461", 'kotlis'),
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "2nd Counter Name",
				"param_name" => "counter_name2",
				"value" => "",
				"description" => __("Please insert counter name in format: Working projects", 'kotlis'),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "2nd Counter Number",
				"param_name" => "counter_num2",
				"value" => "",
				"description" => __("Please insert counter number in format: 354", 'kotlis'),
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "3rd Counter Name",
				"param_name" => "counter_name3",
				"value" => "",
				"description" => __("Please insert counter name in format: Happy customers", 'kotlis'),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "3rd Counter Number",
				"param_name" => "counter_num3",
				"value" => "",
				"description" => __("Please insert counter number in format: 168", 'kotlis'),
				"admin_label" => true,
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "4th Counter Name",
				"param_name" => "counter_name4",
				"value" => "",
				"description" => __("Please insert counter name in format: Working hours", 'kotlis'),
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "4th Counter Number",
				"param_name" => "counter_num4",
				"value" => "",
				"description" => __("Please insert counter number in format: 222", 'kotlis'),
				"admin_label" => true,
			),				
            
		)
) );
// Skills Block
class WPBakeryShortCode_WR_VC_Skills  extends WPBakeryShortCodesContainer {}
//Register "container" content element. It will hold all your inner (child) content elements
vc_map( array(
        "name" => "Kotlis Skills", "webRedox",
        "base" => "wr_vc_skills",
        "as_parent" => array('only' => 'wr_vc_skill'), // Use only|except attributes to limit child shortcodes (separate multiple values with comma)
        "content_element" => true,
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
        "show_settings_on_create" => true,
        "params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),											
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => " Margin",
				"param_name" => "margin",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),				
        ),
        "js_view" => 'VcColumnView'
) );
class WPBakeryShortCode_WR_VC_Skill extends WPBakeryShortCode {}
vc_map( array(
        "name" => "Progress Bar", "webRedox",
        "base" => "wr_vc_skill",
        "content_element" => true,
		"icon" => "kotlis-icon",
        "as_child" => array('only' => 'wr_vc_skills'), // Use only|except attributes to limit parent (separate multiple values with comma)
        "params" => array(					
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data Title",
				"param_name" => "title",
				"value" => "",
				"description" => __("Ex: Photoshop", 'kotlis'),
				"admin_label" => true,
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data Percentage",
				"param_name" => "percentage",
				"value" => "",
				"description" => __("Ex: 95", 'kotlis'),
				"admin_label" => true,
			),													
		
        )
) );
// Lists Block
class WPBakeryShortCode_WR_VC_Lists  extends WPBakeryShortCodesContainer {}
//Register "container" content element. It will hold all your inner (child) content elements
vc_map( array(
        "name" => "Kotlis Lists", "webRedox",
        "base" => "wr_vc_lists",
        "as_parent" => array('only' => 'wr_vc_list'), // Use only|except attributes to limit child shortcodes (separate multiple values with comma)
        "content_element" => true,
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
        "show_settings_on_create" => true,
        "params" => array(			
        ),
        "js_view" => 'VcColumnView'
) );
class WPBakeryShortCode_WR_VC_List extends WPBakeryShortCode {}
vc_map( array(
        "name" => "Kotlis List Item", "webRedox",
        "base" => "wr_vc_list",
        "content_element" => true,
		"icon" => "kotlis-icon",
        "as_child" => array('only' => 'wr_vc_lists'), // Use only|except attributes to limit parent (separate multiple values with comma)
        "params" => array(					
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data Title",
				"param_name" => "data_title",
				"value" => "",
				"description" => __("Ex: Location", 'kotlis'),
				"admin_label" => true,
			),	
            array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data URL",
				"param_name" => "data_url",
				"admin_label" => true,
				"value" => "",
			),							
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data URL Text",
				"param_name" => "data_url_text",
				"value" => "",
				"description" => __("Ex: NY, USA", 'kotlis'),
				"admin_label" => true,
			),													
            array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Data URL Target",
				"param_name" => "data_url_target",
				"value" => array(
					"Self" => "_self",
					"Blank" => "_blank",	
					"Top" => "_top"	
				),
				"description" => "",
			),		
        )
) );
// Services Block
class WPBakeryShortCode_WR_VC_Resumes  extends WPBakeryShortCodesContainer {}
//Register "container" content element. It will hold all your inner (child) content elements
vc_map( array(
        "name" => "Kotlis Services", "webRedox",
        "base" => "wr_vc_resumes",
        "as_parent" => array('only' => 'wr_vc_resume'), // Use only|except attributes to limit child shortcodes (separate multiple values with comma)
        "content_element" => true,
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
        "show_settings_on_create" => true,
        "params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),											
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => " Margin",
				"param_name" => "margin",
				"value" => "",
				"description" => __("Please insert margin in format: 0px 0px 0px 0px", 'kotlis')
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Padding",
				"param_name" => "padding",
				"value" => "",
				"description" => __("Please insert padding in format: 0px 0px 0px 0px", 'kotlis')
			),				
        ),
        "js_view" => 'VcColumnView'
) );
class WPBakeryShortCode_WR_VC_Resume extends WPBakeryShortCode {}
vc_map( array(
        "name" => "Service Item", "webRedox",
        "base" => "wr_vc_resume",
        "content_element" => true,
		"icon" => "kotlis-icon",
        "as_child" => array('only' => 'wr_vc_resumes'), // Use only|except attributes to limit parent (separate multiple values with comma)
        "params" => array(					
			array(
				"type" => "attach_image",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Image",
				"param_name" => "image",
				"value" => "",
				"admin_label" => true,
			),	
			array(
				"type" => "textarea_html",
				"holder" => "div",
				"class" => "",
				"heading" => "Content",
				"param_name" => "content",
				"value" => ""
			),								
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Price",
				"param_name" => "date",
				"value" => "",
				"description" => __("Ex: Price :", 'kotlis'),
				"admin_label" => true,
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Amount",
				"param_name" => "place",
				"value" => "",
				"description" => __("Ex: $250-$1200", 'kotlis'),
				"admin_label" => true,
			),				
		
        )
) );

// Testimonials Block
class WPBakeryShortCode_WR_VC_Testimonials  extends WPBakeryShortCodesContainer {}
//Register "container" content element. It will hold all your inner (child) content elements
vc_map( array(
        "name" => "Kotlis Testimonial", "kotlis",
        "base" => "wr_vc_testimonials",
        "as_parent" => array('only' => 'wr_vc_testimonial'), // Use only|except attributes to limit child shortcodes (separate multiple values with comma)
        "content_element" => true,
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
        "show_settings_on_create" => true,
        "params" => array(
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Class",
				"param_name" => "class",
				"value" => ""
			),	
            
        ),
        "js_view" => 'VcColumnView'
) );

class WPBakeryShortCode_WR_VC_Testimonial extends WPBakeryShortCode {}
vc_map( array(
        "name" => "webRedox Testimonial Item", "kotlis",
        "base" => "wr_vc_testimonial",
        "content_element" => true,
		"icon" => "kotlis-icon",
        "as_child" => array('only' => 'wr_vc_testimonials'), // Use only|except attributes to limit parent (separate multiple values with comma)
        "params" => array(
				
			array(
				"type" => "attach_image",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Client's Image",
				"param_name" => "image",
				"description" => "",
				"admin_label" => true,
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Client Name",
				"param_name" => "clientname",
				"value" => "",
				"description" => "",
				"admin_label" => true,
			),
			
			array(
				"type" => "textarea_html",
				"holder" => "div",
				"class" => "",
				"heading" => "Text",
				"param_name" => "content",
				"value" => ""
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button Text",
				"param_name" => "button_text",
				"value" => "",
				"description" => "",
				"admin_label" => true,
				
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button URL",
				"param_name" => "button_url",
				"value" => "",
				"description" => "",
				
			),
			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Button Target",
				"param_name" => "button_target",
				"value" => array(
					"Self" => "_self",
					"Blank" => "_blank",
					"Parent" => "_parent",	
					"Top" => "_top"	
				),
				"description" => "",
			),
							
            
        )
) );

// Contact Info Block
vc_map( array(
		"name" => "Kotlis Contact Info",
		"base" => "wr_vc_contact_info",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_container_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Address Text",
				"param_name" => "address_title",
				"value" => "",
				"description" => esc_attr__("Please insert address text here. Ex: Address : ", 'kotlis'),
				"admin_label" => true,
			),			
			array(
				"type" => "textarea_html",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Address",
				"param_name" => "content",
				"value" => "",
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Phone Text",
				"param_name" => "phone_title",
				"value" => "",
				"description" => esc_attr__("Please insert phone text here. Ex: Phone : ", 'kotlis'),
				"admin_label" => true,
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Phone Number 1",
				"param_name" => "con_phone1",
				"value" => "",	
				"admin_label" => true,				
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Phone Number 2",
				"param_name" => "con_phone2",
				"value" => "",	
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Phone Number 3",
				"param_name" => "con_phone3",
				"value" => "",	
				"admin_label" => true,
			),
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Mail Text",
				"param_name" => "mail_title",
				"value" => "",
				"description" => esc_attr__("Please insert phone text here. Ex: Mail : ", 'kotlis'),
				"admin_label" => true,
			),			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Mail Address 1",
				"param_name" => "con_mail1",
				"value" => "",
				"admin_label" => true,
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Mail Address 2",
				"param_name" => "con_mail2",
				"value" => "",
				"admin_label" => true,
			),	
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Mail Address 3",
				"param_name" => "con_mail3",
				"value" => "",
				"admin_label" => true,
			),			
		)
) );

// Contact Form Block
vc_map( array(
		"name" => "Kotlis Contact Form",
		"base" => "wr_vc_contact_form",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_contaikotlis_element" => 'vc_row',
		"params" => array(
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "CSS Class",
				"param_name" => "class",
				"value" => ""
			),						
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Contact Form ID",
				"param_name" => "contactfromid",
				"value" => "",
				"description" => __("Please insert contact form id number in format: 27", 'kotlis'),
				"admin_label" => true,
			),				
  
		)
) );
// Google Map
vc_map( array(
		"name" => "webRedox Google Map",
		"base" => "wr_vc_map",
		"category" => 'by Kotlis',
		"icon" => "kotlis-icon",
		"allowed_container_element" => 'vc_row',
		"params" => array(

			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Latitude, Longitude",
				"param_name" => "latitude",
				"value" => "",
				"description" => "Ex: 48.859003, 2.345275",
				"admin_label" => true,
			),
			
			array(
				"type" => "textfield",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Address",
				"param_name" => "address",
				"value" => "",
				"description" => "Ex: 27th Brooklyn New York, NY 10065",
				"admin_label" => true,
			),
			
			array(
				"type" => "attach_image",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Upload Location Marker",
				"param_name" => "image",
				"description" => "",
				"admin_label" => true,
			),			
			array(
				"type" => "dropdown",
				"holder" => "hidden",
				"class" => "",
				"heading" => "Color Scheme",
				"param_name" => "map_url",
				"value" => array(
					"Default" => "st1",
					"Light" => "st2",
					"Dark" => "st3",	
					
				),
				"description" => "",
			),
			
			
		)
) );


?>