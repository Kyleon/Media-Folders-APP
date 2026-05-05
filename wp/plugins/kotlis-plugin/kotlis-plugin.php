<?php
/*
Plugin Name: Kotlis Plugin
Plugin URI: https://webredox.net
Description: Declares a plugin that will create Page Settings, WPBakery Addons & Custom Post Type
Version: 3.1.4
Author: webRedox
Author URI: https://webredox.net
License: GPLv2
*/

define('KOTLIS_PLUGIN_PATH', plugin_dir_path(__FILE__));
include (KOTLIS_PLUGIN_PATH .'metaboxes.php');
include (KOTLIS_PLUGIN_PATH .'meta-box-group.php');
include (KOTLIS_PLUGIN_PATH .'meta-box-show-hide.php');
include (KOTLIS_PLUGIN_PATH .'meta-box-tooltip.php');
include (KOTLIS_PLUGIN_PATH .'meta-box-conditional-logic.php');
function kotlis_register_metabox_list() {
require (KOTLIS_PLUGIN_PATH .'/plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://webredox.net/demo/wp/kotlis/pluginupdate/details.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'kotlis-plugin'
);
}
add_action('init', 'kotlis_register_metabox_list');

global $kotlis_options;


if( ! function_exists( 'portfolio_post_types' ) ) {
    function portfolio_post_types() {
		
		$kotlis_options = get_option('kotlis');
		//portfolio dt base
		if(!empty($kotlis_options['portfolio_main_base_opt'])) {
			$kotlis_port_main_base = esc_html(kotlis_AfterSetupTheme::return_thme_option('portfolio_main_base_opt',''));
		}
		else {
			$kotlis_port_main_base ='portfolio';
		};		

        register_post_type(
            'portfolio',
            array(
                'labels' => array(
                    'name'          => __( 'Portfolios', 'portfolio' ),
                    'singular_name' => __( 'Portfolio', 'portfolio' ),
                    'add_new'       => __( 'Add New', 'portfolio' ),
                    'add_new_item'  => __( 'Add New Portfolio', 'portfolio' ),
                    'edit'          => __( 'Edit', 'portfolio' ),
                    'edit_item'     => __( 'Edit Portfolio', 'portfolio' ),
                    'new_item'      => __( 'New Portfolio', 'portfolio' ),
                    'view'          => __( 'View Portfolio', 'portfolio' ),
                    'view_item'     => __( 'View Portfolio', 'portfolio' ),
                    'search_items'  => __( 'Search Portfolio', 'portfolio' ),
                    'not_found'     => __( 'No Portfolio item found', 'portfolio' ),
                    'not_found_in_trash' => __( 'No portfolio item found in Trash', 'portfolio' ),
                    'parent'        => __( 'Parent Portfolio', 'portfolio' ),
                ),
                
                'description'       => __( 'Create a Portfolio.', 'portfolio' ),
                'public'            => true,
                'show_ui'           => true,
                'show_in_menu'          => true,
                'publicly_queryable'    => true,
				'capability_type' => 'post',
                'exclude_from_search'   => true,
                'menu_position'         => 6,
                'hierarchical'      => false,
                'query_var'         => true,
				'rewrite' => array(
                'slug' => $kotlis_port_main_base
				),				
				'menu_icon' => 'dashicons-portfolio',
                'supports'  => array (
                    'title', //Text input field to create a post title.
                    'editor',
                    'thumbnail',
                    
                )
            )
        );
//portfolio
if(!empty($kotlis_options['portfolio_category_base_opt'])) {
	$kotlis_port_cat_base = esc_html(kotlis_AfterSetupTheme::return_thme_option('portfolio_category_base_opt',''));
}
else {
	$kotlis_port_cat_base ='portfolio_category';
};
register_taxonomy('portfolio_category', 'portfolio', array('hierarchical' => true, 'label' => 'Portfolio Category', 'singular_name' => 'Portfolio Category', "rewrite" => array('slug' =>  $kotlis_port_cat_base,'with_front' => true), "query_var" => true, 'show_admin_column' => true, 'labels' => ['all_items' => __('All Categories', 'kotlis-plugin'),'edit_item' => __('Edit Category', 'kotlis-plugin'),'view_item' => __('View Category', 'kotlis-plugin'),'update_item' => __('Update Category', 'kotlis-plugin'),'add_new_item' => __('Add New Category', 'kotlis-plugin'),'new_item_name' => __('New Category Name', 'kotlis-plugin'),'search_items' => __('Search Categories', 'kotlis-plugin'),'popular_items' => __('Popular Categories', 'kotlis-plugin'),'separate_items_with_commas' => __('Separate Category with comma', 'kotlis-plugin'), 'choose_from_most_used' => __('Choose from most used Categories', 'kotlis-plugin'),'not_found' => __('No Categories found', 'kotlis-plugin'),])); 

    }
}

add_action( 'init', 'portfolio_post_types' ); // register post type

register_taxonomy_for_object_type('category', 'custom-type');


if( ! function_exists( 'video_post_types' ) ) {
    function video_post_types() {
		
		$kotlis_options = get_option('kotlis');
		//video dt base
		if(!empty($kotlis_options['video_main_base_opt'])) {
			$kotlis_video_main_base = esc_html(kotlis_AfterSetupTheme::return_thme_option('video_main_base_opt',''));
		}
		else {
			$kotlis_video_main_base ='video';
		};		

        register_post_type(
            'video',
            array(
                'labels' => array(
                    'name'          => __( 'Videos', 'video' ),
                    'singular_name' => __( 'Video', 'video' ),
                    'add_new'       => __( 'Add New', 'video' ),
                    'add_new_item'  => __( 'Add New Video', 'video' ),
                    'edit'          => __( 'Edit', 'video' ),
                    'edit_item'     => __( 'Edit Video', 'video' ),
                    'new_item'      => __( 'New Video', 'video' ),
                    'view'          => __( 'View Video', 'video' ),
                    'view_item'     => __( 'View Video', 'video' ),
                    'search_items'  => __( 'Search Video', 'video' ),
                    'not_found'     => __( 'No Video item found', 'video' ),
                    'not_found_in_trash' => __( 'No video item found in Trash', 'video' ),
                    'parent'        => __( 'Parent Video', 'video' ),
                ),
                
                'description'       => __( 'Create a Video.', 'video' ),
                'public'            => true,
                'show_ui'           => true,
                'show_in_menu'          => true,
                'publicly_queryable'    => true,
				'capability_type' => 'post',
                'exclude_from_search'   => true,
                'menu_position'         => 6,
                'hierarchical'      => false,
                'query_var'         => true,
				'rewrite' => array(
                'slug' => $kotlis_video_main_base
				),	
				'menu_icon' => 'dashicons-format-video',
                'supports'  => array (
                    'title', //Text input field to create a post title.
                    'editor',
                    'thumbnail',
                    
                )
            )
        );
//video
if(!empty($kotlis_options['video_category_base_opt'])) {
	$kotlis_video_cat_base = esc_html(kotlis_AfterSetupTheme::return_thme_option('video_category_base_opt',''));
}
else {
	$kotlis_video_cat_base ='video_category';
};
register_taxonomy('video_category', 'video', array('hierarchical' => true, 'label' => 'video Category', 'singular_name' => 'video Category', "rewrite" => array('slug' =>  $kotlis_video_cat_base,'with_front' => true), "query_var" => true, 'show_admin_column' => true, 'labels' => ['all_items' => __('All Categories', 'kotlis-plugin'),'edit_item' => __('Edit Category', 'kotlis-plugin'),'view_item' => __('View Category', 'kotlis-plugin'),'update_item' => __('Update Category', 'kotlis-plugin'),'add_new_item' => __('Add New Category', 'kotlis-plugin'),'new_item_name' => __('New Category Name', 'kotlis-plugin'),'search_items' => __('Search Categories', 'kotlis-plugin'),'popular_items' => __('Popular Categories', 'kotlis-plugin'),'separate_items_with_commas' => __('Separate Category with comma', 'kotlis-plugin'), 'choose_from_most_used' => __('Choose from most used Categories', 'kotlis-plugin'),'not_found' => __('No Categories found', 'kotlis-plugin'),])); 
        
        

    }
}

add_action( 'init', 'video_post_types' ); // register post type

register_taxonomy_for_object_type('category', 'custom-type');

add_filter('widget_title', 'do_shortcode');
add_shortcode('span', 'wpse_shortcode_span');
function wpse_shortcode_span( $attr, $content ){ return '<span>'. $content . '</span>'; }
add_shortcode('br', 'wpse_shortcode_br');
function wpse_shortcode_br( $attr ){ return '<br>'; }
function kotlis_social_media_icons( $kotlis_contactmethods ) {
    // Add social media
    
    $kotlis_contactmethods['twitter'] = 'Twitter';
    $kotlis_contactmethods['facebook'] = 'Facebook';
    $kotlis_contactmethods['instagram'] = 'Instagram';
    $kotlis_contactmethods['tumblr'] = 'Tumblr';
    $kotlis_contactmethods['pinterest'] = 'Pinterest';
    $kotlis_contactmethods['youtube'] = 'Youtube';

    return $kotlis_contactmethods;
}
add_filter('user_contactmethods','kotlis_social_media_icons',10,1);
/* ==========================================
   Add featured image column to admin panel post list page
========================================== */
add_filter('manage_posts_columns', 'add_img_column');
add_filter('manage_posts_custom_column', 'manage_img_column', 10, 2);

function add_img_column($columns) {
	$columns['img'] = 'Thumbnail';
	return $columns;
}

function manage_img_column($column_name, $post_id) {
	if( $column_name == 'img' ) {
		echo get_the_post_thumbnail( $post_id, array( 80, 60) ); return true; // 80, 60 is for image size.
	}
}

// Change columns order
add_filter('manage_posts_columns', 'column_order');
function column_order($columns) {
  $n_columns = array();
  $move = 'img'; // what to move
  $before = 'title'; // move before this
  foreach($columns as $key => $value) {
    if ($key==$before){
      $n_columns[$move] = $move;
    }
      $n_columns[$key] = $value;
  }
  return $n_columns;
}

function kotlis_year_shortcode() {
  $kotlis_year = date('Y');
  return $kotlis_year;
}
add_shortcode('kotlis_year', 'kotlis_year_shortcode');

/**
*
*
*
 * Allow shortcodes in widgets
 * @since v1.0
 */
add_filter('widget_text', 'do_shortcode');

if( !function_exists('symple_fix_shortcodes') ) {
	function symple_fix_shortcodes($content){   
		$array = array (
			'<p>['		=> '[', 
			']</p>'		=> ']', 
			']<br />'	=> ']'
		);
		$content = strtr($content, $array);
		return $content;
	}
	add_filter('the_content', 'symple_fix_shortcodes');
}

// Section Title Shortcode (Visual)
if(! function_exists('wr_vc_section_title_shortcode')){
	function wr_vc_section_title_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'title'=>'',
			'title2'=>'',
			'title3'=>'',
			'color'=>'',
			'color2'=>'',
			'font_size'=>'',
			'font_size2'=>'',
			'font_weight'=>'',
			'line_height'=>'',
			'text_align'=>'',
			'text_transform'=>'',			
			'float'=>'text-left',					
			'featyretype'=>'st1',					
			'margin'=>'',					
			'padding'=>'',	
			'margin2'=>'',					
			'padding2'=>'',							
			'featyretype'=>'',							
			), $atts) );				
		$html='';					
		    if($featyretype == "st2"){
				if($title != '') { 
				$html .='<div class="pr-det-container '.esc_attr($class).' ">'; 			
					$html .='<h2 class="'.esc_attr($float).'" style="';
					if($margin2 != '') { $html .='margin:'.$margin2.';';} 
				if($padding2 != '') { $html .='padding:'.$padding2.';';}
					$html .='">'.do_shortcode($title).'</h2>';	
				$html .='</div>';
				}  		
			} 
			else {
			$html .='<div class="sec-title '.esc_attr($class).' " style="';
				if($margin2 != '') { $html .='margin:'.$margin2.';';} 
				if($padding2 != '') { $html .='padding:'.$padding2.';';}						
			$html .='">';
				if($title != '' || $title2 != '' || $title3 != '') {	
				$html .='<div class="section-title fl-wrap">';
					if($title != '') {	
					    $html .='<h3>'.$title.'</h3>';
					} if($title2 != '') {	
					    $html .='<h4>'.$title2.'</h4>';
					} if($title3 != '') {	
					    $html .='<div class="section-number">'.$title3.'</div>';
					}
				$html .='</div>';
				}														
			$html .='</div>';  
			}
		return $html;
	}
	add_shortcode('wr_vc_section_title', 'wr_vc_section_title_shortcode');
}

// Section Content Shortcode (Visual)
if(! function_exists('wr_vc_section_text_shortcode')){
	function wr_vc_section_text_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',			
			'float'=>'',						
			'margin2'=>'',					
			'padding2'=>'',													
			), $atts) );				
		$html='';		
		    $html .='<div class="sec-text '.$class.' '.$float.'" style="';
					if($margin2 != '') { $html .='margin:'.$margin2.';';} 
					if($padding2 != '') { $html .='padding:'.$padding2.';';}  				
			    $html .='">';
					if($content != '') {	
					$html .=''.$content.'';
					}				
			$html .='</div>';                
		return $html;
	}
	add_shortcode('wr_vc_section_text', 'wr_vc_section_text_shortcode');
}

// Section Image Shortcode (Visual)

if(! function_exists('wr_vc_section_image_shortcode')){
	function wr_vc_section_image_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'width'=>'',
			'height'=>'',
			'margin'=>'',
			'padding'=>'',			
			'position'=>'',			
			'float'=>'',			
			'top'=>'',
			'bottom'=>'',
			'right'=>'',
			'left'=>'',
			'img_url'=>'',
			'link_url'=>'',
			'link_target'=>'',			
			'featyretype'=>'',
			'zindex'=>'',

			), $atts) );

		$html='';

			if (is_numeric($img_url)) {
				$kotlis_image = wp_get_attachment_url( $img_url );
				$kotlis_image_alt = get_post_meta($img_url, '_wp_attachment_image_alt', TRUE);
			} else {
				$kotlis_image = $img_url;
				$kotlis_image_alt = $img_url;
			}						

			    $html .='<div class="sec-image '.$class.'">';	
                if($featyretype == "st2"){
					if($link_url != '') {	
						$html .='<a href="'.$link_url.'"';
							if($link_target != '') { $html .='target="'.$link_target.'"';}						
						$html .='>';
					}
					$html .='<img src="'.esc_url($kotlis_image).'" ';
						$html .='style="';
						if($width != '') { $html .='width:'.$width.';';}  				
						if($height != '') { $html .='height:'.$height.';';}  				
						if($float != '') { $html .='float:'.$float.';';}  				
						if($position != '') { $html .='position:'.$position.';';}  				
						if($top != '') { $html .='top:'.$top.';';}  				
						if($bottom != '') { $html .='bottom:'.$bottom.';';}  				
						if($right != '') { $html .='right:'.$right.';';}  				
						if($left != '') { $html .='left:'.$left.';';}  				
						if($zindex != '') { $html .='z-index:'.$zindex.';';}  				
						if($margin != '') { $html .='margin:'.$margin.';';} 
						if($padding != '') { $html .='padding:'.$padding.';';}
						$html .='"';
					$html .=' alt="'.esc_attr($kotlis_image_alt).'" />';
					if($link_url != '') {
						$html .='</a>';
					}						
				} else {
					if($link_url != '') {	
						$html .='<a href="'.$link_url.'"';
							if($link_target != '') { $html .='target="'.$link_target.'"';}						
						$html .='>';
					}	
				    $html .='<img src="'.esc_url($kotlis_image).'" ';
					    $html .='style="';
						if($width != '') { $html .='width:'.$width.';';}  				
						if($height != '') { $html .='height:'.$height.';';}  				
						if($float != '') { $html .='float:'.$float.';';}  				
						if($position != '') { $html .='position:'.$position.';';}  				
						if($top != '') { $html .='top:'.$top.';';}  				
						if($bottom != '') { $html .='bottom:'.$bottom.';';}  				
						if($right != '') { $html .='right:'.$right.';';}  				
						if($left != '') { $html .='left:'.$left.';';}  				
						if($zindex != '') { $html .='z-index:'.$zindex.';';}  				
						if($margin != '') { $html .='margin:'.$margin.';';} 
						if($padding != '') { $html .='padding:'.$padding.';';}
						$html .='"';
					$html .=' alt="'.esc_attr($kotlis_image_alt).'" class="img-responsive respimg"/>';
					if($link_url != '') {
						$html .='</a>';
					}	
				}	
				$html .='</div>';			
                
		return $html;
	}
	add_shortcode('wr_vc_section_image', 'wr_vc_section_image_shortcode');
}
// Button Section Shortcode (Visual)
if(! function_exists('wr_vc_button_shortcode')){
	function wr_vc_button_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',						
			'float'=>'',					
			'margin'=>'',					
			'padding'=>'',	
			'button_name'=>'',																
			'link_url'=>'',						
			'link_target'=>'',																				
			'custom_scroll'=>'',																				
			), $atts) );
        $html='';
		    $html .='<div class="sec-button '.$class.' '.$float.'" style="';
				if($margin != '') { $html .='margin:'.$margin.';';} 
				if($padding != '') { $html .='padding:'.$padding.';';} 
			$html .='">'; 
				if($link_url != '') {	
					$html .='<a class="'.$custom_scroll.' btn fl-btn" href="'.$link_url.'"';
						if($link_target != '') { $html .='target="'.$link_target.'"';}						
					$html .='>'.$button_name.'</a>';
				}							
            $html .='</div>';  		
			
		return $html;
	}
	add_shortcode('wr_vc_button', 'wr_vc_button_shortcode');
}

// Separator Section Shortcode (Visual)
if(! function_exists('wr_vc_divider_shortcode')){
	function wr_vc_divider_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'margin'=>'',
			'padding'=>'',
											
			), $atts) );
		
        $html='';			
   		
		    $html .='<div class="sec-divider '.$class.'">';  
		        $html .='<div class="sec-dec" style="';
					if($margin != '') { $html .='margin:'.$margin.';';} 
					if($padding != '') { $html .='padding:'.$padding.';';}  				
				$html .='">'; 		
                $html .='</div>';  		
            $html .='</div>';  		
			
		return $html;
	}
	add_shortcode('wr_vc_divider', 'wr_vc_divider_shortcode');
}

// Protfolio Section 
 if(! function_exists('wr_vc_portfolio_body_shortcode')){
	function wr_vc_portfolio_body_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',	
			'postcount'=>'',
			'categoryname'=>'',
			'postoffset'=>'',											
    	    'enable_filter'=>'',			
    	    'port_filter_cat_count'=>'',			
    	    'port_filter_cat_exclude'=>'',			
    	    'text_filter'=>'All',			
			
			), $atts) );
		$html='';
		
	$html .= '<div class="sec-portfolio column-wrapper_smallpadding '.esc_attr($class).'">';

			if($enable_filter == "st2"){
			if(!get_post_meta(get_the_ID(), 'portfolio_category', true)) {
			$portfolio_category = get_terms('portfolio_category', array('exclude' => $port_filter_cat_exclude, 'number'=>$port_filter_cat_count));
			if($portfolio_category) {
			$html .= '<div class="fbc_white">';
			$html .= '<div class="gallery-filters">';
			$html .= '<a href="#" class="gallery-filter  gallery-filter-active" data-filter="*">'.esc_html($text_filter).'</a>';
			foreach($portfolio_category as $portfolio_cat) {
			$html .= '<a href="#" class="gallery-filter " data-filter=".'.esc_attr($portfolio_cat->slug).'">'.esc_html($portfolio_cat->name).'</a>';
			}
			$html .= '</div>';
			$html .= '</div>';
			}
			}
			}
            $html .= '<div class="gallery-items min-pad   three-column fl-wrap lightgallery">';
				global $post;			
				$paged=(get_query_var('paged'))?get_query_var('paged'):1;
				$loop = new WP_Query( array( 'post_type' => 'portfolio', 'portfolio_category'=> $categoryname, 'posts_per_page'=> $postcount,  'offset' => $postoffset, 'paged'=>$paged ) );			
				while ( $loop->have_posts() ) : $loop->the_post();
				$portfolio_category = wp_get_post_terms($post->ID,'portfolio_category', array('exclude' => $port_filter_cat_exclude));
				$kotlis_class = ""; 
				$kotlis_categories = ""; 
				foreach ($portfolio_category as $kotlis_item) {
					$kotlis_class.=esc_attr($kotlis_item->slug . ' ');
					$kotlis_categories.='<span class="cat-divider">';
					$kotlis_categories.=esc_attr($kotlis_item->name . '  ');
					$kotlis_categories.='</span>';
				}	
					if (has_post_thumbnail( $post->ID ) ):		
					$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), '' );					
					$kotlis_image_alt = get_post_meta( get_post_thumbnail_id( $post->ID ), '_wp_attachment_image_alt', true);	

					$html .= '<div class="gallery-item '.esc_attr($kotlis_class).'">';	
					    $html .= '<a href="'.get_the_permalink().'">';	
							$html .='<div class="grid-item-holder hov_zoom">';
							    $html .='<img src="'.esc_url($kotlis_image[0]).'" alt="'.esc_attr($kotlis_image_alt).'" />';
								$html .='<a href="'.esc_url($kotlis_image[0]).'" class="box-media-zoom   popup-image"><i class="fal fa-search"></i></a>';
								$html .='<div class="thumb-info">';
									$html .= '<h3><a href="'.get_the_permalink().'">'.get_the_title().'</a></h3>';	
									/*$html .= '<p>'.get_the_excerpt().'</p>';	*/					
								$html .= '</div>';											
						    $html .= '</div>';	
						$html .= '</a>';		
					$html .= '</div>';														
					endif;	
				endwhile;
				wp_reset_postdata();	

			$html .= '</div>';	
	$html .= '</div>';	
			return $html;
	}
	add_shortcode('wr_vc_portfolio', 'wr_vc_portfolio_body_shortcode');
}

// Counter Section Shortcode (Visual)

if(! function_exists('wr_vc_counter_shortcode')){
	function wr_vc_counter_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'counter_name1'=>'',			
			'counter_num1'=>'',		
			'counter_name2'=>'',			
			'counter_num2'=>'',	
			'counter_name3'=>'',			
			'counter_num3'=>'',		
			'counter_name4'=>'',			
			'counter_num4'=>'',				
			), $atts) );
				
		$html='';
		    
		$html .='<div class="sec-counter '.$class.'">';			
			$html .='<div class="inline-facts-holder fl-wrap">';				
				if($counter_num1 != '') {                    
				$html .='<div class="inline-facts">';
					$html .='<div class="milestone-counter">';
						$html .='<div class="stats animaper">';
							$html .='<div class="num" data-content="'.$counter_num1.'" data-num="'.$counter_num1.'">'.$counter_num1.'</div>';
						$html .='</div>';
					$html .='</div>';
					$html .='<h6>'.$counter_name1.'</h6>';
				$html .='</div>';
				} if($counter_num2 != '') {                   
				$html .='<div class="inline-facts">';
					$html .='<div class="milestone-counter">';
						$html .='<div class="stats animaper">';
							$html .='<div class="num" data-content="'.$counter_num2.'" data-num="'.$counter_num2.'">'.$counter_num2.'</div>';
						$html .='</div>';
					$html .='</div>';
					$html .='<h6>'.$counter_name2.'</h6>';
				$html .='</div>';
				} if($counter_num3 != '') {                     
				$html .='<div class="inline-facts">';
					$html .='<div class="milestone-counter">';
						$html .='<div class="stats animaper">';
							$html .='<div class="num" data-content="'.$counter_num3.'" data-num="'.$counter_num3.'">'.$counter_num3.'</div>';
						$html .='</div>';
					$html .='</div>';
					$html .='<h6>'.$counter_name3.'</h6>';
				$html .='</div>';
				} if($counter_num4 != '') {                     
				$html .='<div class="inline-facts">';
					$html .='<div class="milestone-counter">';
						$html .='<div class="stats animaper">';
							$html .='<div class="num" data-content="'.$counter_num4.'" data-num="'.$counter_num4.'">'.$counter_num4.'</div>';
						$html .='</div>';
					$html .='</div>';
					$html .='<h6>'.$counter_name4.'</h6>';
				$html .='</div>';
				}										
			$html .='</div>';	
		$html .='</div>';               
		return $html;
	}
	add_shortcode('wr_vc_counter', 'wr_vc_counter_shortcode');
}

// Contact Info (Visual)
if(! function_exists('wr_vc_contact_info_shortcode')){
	function wr_vc_contact_info_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'float'=>'',
			'df_padding'=>'',
			'address_title'=>'',
			'phone_title'=>'',
			'mail_title'=>'',
			'con_phone1'=>'',
			'con_phone2'=>'',
			'con_phone3'=>'',
			'con_mail1'=>'',
			'con_mail2'=>'',
			'con_mail3'=>'',			
			),  $atts) );
		$html='';
		$html .='<div class="sec-contact-info '.$class.'">';            
			$html .='<div class="contact-details fl-wrap">';
			    $html .='<ul>';
					if($mail_title != '' || $con_mail1 != '' || $con_mail2 != '' || $con_mail3 != '') {
					$html .='<li><span>'.$mail_title.' </span>';
					    $html .='<a target="_blank" href="mailto:'.$con_mail1.'">'.$con_mail1.'</a>';
						if($con_mail2 != '') {	
						$html .=' , <a target="_blank" href="mailto:'.$con_mail2.'">'.$con_mail2.'</a>';
						} if($con_mail3 != '') {				
						$html .=' , <a target="_blank" href="mailto:'.$con_mail3.'">'.$con_mail3.'</a>';
						}		
					$html .='</li>'; 			
					}                
					if($content != '' || $address_title != '') {	            		
					$html .='<li><span>'.$address_title.' </span><p>'.$content.'</p></li>';
					}
				    if($phone_title != '' || $con_phone1 != '' || $con_phone2 != '' || $con_phone3 != '') {
				    $html .='<li><span>'.$phone_title.' </span>';
					    $html .='<a href="tel:'.$con_phone1.'">'.$con_phone1.'</a>';
						if($con_phone2 != '') {	
						$html .=' , <a href="tel:'.$con_phone2.'">'.$con_phone2.'</a>';
						} if($con_phone3 != '') {				
						$html .=' , <a href="tel:'.$con_phone3.'">'.$con_phone3.'</a>';
						}
					$html .='</li>'; 
					}										

			    $html .='</ul>'; 
			$html .='</div>'; 
        $html .='</div>'; 
        return $html;						
	}
	add_shortcode('wr_vc_contact_info', 'wr_vc_contact_info_shortcode');
}

// Contact Form (Visual)
if(! function_exists('wr_vc_contact_shortcode')){
	function wr_vc_contact_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'title'=>'',
			'contactfromid'=>'',
			'form_title'=>'',
			'form_subtitle'=>'',
			), $atts) );

		$html='';	
			$html .='<div class="sec-contact-form '.$class.'">'; 
			    $html .='<div id="contact-form" class="custom-form">'; 		
					$html .=''.do_shortcode('[contact-form-7 id="'.$contactfromid.'" title="Contact Form"]').'';
				$html .='</div>'; 			
			$html .='</div>'; 				
		return $html;	
	}
	add_shortcode('wr_vc_contact_form', 'wr_vc_contact_shortcode');
}

// Google Map
if(! function_exists('wr_vc_map_shortcode')){
	function wr_vc_map_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'class'=>'',
			'id'=>'',
			'image'=>'',
			'latitude'=>'',
			'longitude'=>'',
			'address'=>'',
			'map_url'=>'',
			
			
			), $atts) );
		if(is_numeric($image)) {
            $kotlis_image = wp_get_attachment_url( $image );
        } else {
            $kotlis_image = $image;
        }
		
		$html='';
		$dot="'";
		$map_url_opt="";
		if($map_url == "st2"){
		$map_url_opt="//{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png";
		}
		else if($map_url == "st3"){
		$map_url_opt="//{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png";	
		}
		else {
		$map_url_opt="//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png";	
		}


		$html .= '<div class="map-container">
					<div id="map-single" class="map" data-map-back="'.$map_url_opt.'" data-latlog="['.$latitude.']" data-popuptext="'.$address.'"  data-popupicon="'.$kotlis_image.'"></div>				
				</div>';		
		
		wp_enqueue_script( 'map-min' );	
        wp_enqueue_script( 'map-script' );		
		return $html;
	}
	add_shortcode('wr_vc_map', 'wr_vc_map_shortcode');
}

// image gallery
if(! function_exists('kotlis_image_gallery_shortcode')){
	function kotlis_image_gallery_shortcode($atts, $content = null){
		extract(shortcode_atts( array(
			'image'=>'',
			'gallery_column'=>'four-column',
			'image_title'=>'',
			
			), $atts) );
		
		$ids        = $atts['image'];
		$ids        = explode(',', $ids);
		
		$html='';
		$dot="'";
		
		$html .= '<div class="ff_panel-conainer-page fl-wrap">';
		$html .= '<div class="gallery-items min-pad   '.esc_attr($gallery_column).' fl-wrap lightgallery">';
		
		foreach ($ids as $id) {
		$image = wp_get_attachment_image_src($id, '');
		$image_alt = get_the_title( $id, '' );
		$html .= '<div class="gallery-item nature">
                 <div class="grid-item-holder hov_zoom">
                 <img  src="'.esc_url($image[0]).'"  alt="'.esc_attr($image_alt).'">
                 <a href="'.esc_url($image[0]).'" class="box-media-zoom   popup-image"><i class="fal fa-search"></i></a>';    if($image_title == "st2"){                                
					 $html .= '<div class="thumb-info">
					 <h3><a href="portfolio-single.html">'.esc_attr($image_alt).'</a></h3>
					</div>';
					}
        $html .= '</div>
        </div>';
		
		}
		
		
		$html .= '</div>';
		$html .= '</div>';
		
				
		return $html;
	}
	add_shortcode('kotlis_image_gallery', 'kotlis_image_gallery_shortcode');
}

?>