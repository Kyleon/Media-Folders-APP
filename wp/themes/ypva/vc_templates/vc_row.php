<?php
$output = $el_class = '';
extract(shortcode_atts(array(
	'el_class' => '',
	'row_type' => 'st1',
	'wr_section_id' => '',
	'wr_section_id2' => '',
	'wr_section_class' => '',
	'wr_default_class2' => '',
	'wr_section_layout' => '',
	'wr_section_layout_grid' => '',	
	'wr_section_height' => '',
	'wr_background_img' => '',
	'wr_background_color' => '',
	'wr_color_scheme' => '',
	'wr_background_parallax' => '',
	'wr_background_parallaxx' => '',
	'wr_background_fancy' => '',
	'wr_padding_top' => '',
	'wr_padding_top2' => '',
	'wr_padding_bottom' => '',
	'wr_padding_bottom2' => '',
	'wr_margin_top' => '',
	'wr_margin_top2' => '',
	'wr_margin_bottom' => '',
	'wr_margin_bottom2' => '',
	'wr_default_pad' => '',	
	'wr_section_class2' => '',	
	'wr_default_pad2' => '',	
	'wr_featured_img' => '',	
	'wr_section_title' => '',	
	'wr_section_subtitle' => '',	
	'wr_section_number' => '',	
	'wr_separator' => '',	

), $atts));

wp_enqueue_style( 'js_composer_front' );
wp_enqueue_script( 'wpb_composer_front_js' );
wp_enqueue_style('js_composer_custom_css');

// separator
if ($wr_separator == "st2"){
   $wr_section_separator ='';
} else {
   $wr_section_separator ='<div class="sec-dec"></div>';
}

$wr_back_image ="";
if ($wr_background_img != '' || $wr_background_img != ' ') { 
	$wr_back_image = wp_get_attachment_image_src( $wr_background_img, 'full');
}
$wr_featured_image ="";
if($wr_featured_img != '' || $wr_featured_img != ' ') { 
	$wr_featured_image = wp_get_attachment_image_src( $wr_featured_img, 'full');
}

$wr_class_layout_full =  "";
$wr_row_layout_full =  "";
$wr_class_layout_grid =  "";
$wr_row_layout_grid =  "";
if ($wr_section_layout == "section_grid"){
	if ($wr_section_layout_grid == "section_layout_grid"){
	$wr_class_layout_grid =  "container-fluid";
	} elseif ($wr_section_layout_grid == "section_layout_grid2"){
	$wr_class_layout_grid =  "container sections";		
	} else {
	$wr_class_layout_grid =  "container";	
	}	
	$wr_row_layout_grid =  "row";	
}
else {
	$wr_class_layout_full =  "wr-section-full-width";
	$wr_row_layout_full =  "row-off";	
}

//blank row
if ($row_type == 'st1'){
    $output .='<div class="clear"></div>';
    $output .='<div class="blank-section">';
	
}
// main row
else if ($row_type == 'main-section'){

$output .='<div class="clear"></div>';
$output .='<div';
if ($wr_section_id != ""){
$output .=' id="'.$wr_section_id.'"';
}
$output .=' class="full-width-section '.$wr_class_layout_full.' '.$wr_color_scheme.' '.$wr_section_class.'" ';
$output.=">";	
    $output .='<div class="block-wrapper '.$wr_default_pad.' '.$wr_background_parallaxx.'"';	
	if ($wr_section_height != "" || $wr_background_color != "" || $wr_background_img != "" || $wr_background_parallax != "" || $wr_padding_top != "" || $wr_padding_bottom != "" || $wr_margin_top != "" || $wr_margin_bottom != ""){
			$output .= " style='";
				if ($wr_section_height != ""){
					$output .="min-height:".$wr_section_height.";";
				}			
				if ($wr_background_color != ""){
					$output .="background-color:".$wr_background_color.";";
				}
				if ($wr_background_img != ""){
					$output .="background: url(".$wr_back_image[0].");";
				}
				if ($wr_background_parallax != ""){
					$output .="background-attachment:".$wr_background_parallax.";";
				}				
				if ($wr_padding_top != ""){
					$output .=" padding-top:".$wr_padding_top.";";
				}
				if ($wr_padding_bottom != ""){
					$output .=" padding-bottom:".$wr_padding_bottom.";";
				}
				if ($wr_margin_top != ""){
					$output .=" margin-top:".$wr_margin_top.";";
				}
				if ($wr_margin_bottom != ""){
					$output .=" margin-bottom:".$wr_margin_bottom.";";
				}				
				$output.="'";
		}
	$output.=">";
	    $output.='<div class="sec-bg-fancy '.$wr_background_fancy.'"></div>';
		$output .='<div class="section-wrapper '.$wr_class_layout_grid.'">';		
		$output .='<div class="row-layout '.$wr_row_layout_full.' '.$wr_row_layout_grid.'">';

}

else if ($row_type == 'featured-section'){

$output .='<div class="clear"></div>';
$output .='<section';
	if ($wr_section_id2 != ""){
	$output .=' id="'.$wr_section_id2.'"';
	}	
	if ($wr_padding_top2 != "" || $wr_padding_bottom2 != "" || $wr_margin_top2 != "" || $wr_margin_bottom2 != ""){
	$output .= " style='";
		if ($wr_padding_top2 != ""){
			$output .=" padding-top:".$wr_padding_top2.";";
		}
		if ($wr_padding_bottom2 != ""){
			$output .=" padding-bottom:".$wr_padding_bottom2.";";
		}
		if ($wr_margin_top2 != ""){
			$output .=" margin-top:".$wr_margin_top2.";";
		}
		if ($wr_margin_bottom2 != ""){
			$output .=" margin-bottom:".$wr_margin_bottom2.";";
		}				
		$output.="'";		
	}
$output.=">";	
	$output .='<div class="container small-container '.$wr_default_pad2.' '.$wr_section_class2.' '.$wr_default_class2.'" >';
	
		if ($wr_section_title != "" || $wr_section_subtitle != "" || $wr_section_number != ""){
		$output .='<div class="section-title fl-wrap">';	
			if ($wr_section_title != ''){
			    $output .='<h3> '.$wr_section_title.'</h3>';
			}
			if ($wr_section_subtitle != ''){
			    $output .='<h4> '.$wr_section_subtitle.'</h4>';
			}
			if ($wr_section_number != ''){
			    $output .='<div class="section-number"> '.$wr_section_number.'</div>';
			}			
		$output .='</div>';	
		}
		$output .='<div class="column-wrapper_item fl-wrap">';
		    $output .='<div class="column-wrapper_text fl-wrap">';

}
//blank row by default
else {
$output .='<div class="clear"></div>';
$output .='<div class="blank-section">';
	
}

if ($row_type != 'content_menu'){
	$output .= wpb_js_remove_wpautop($content);
}
//blank row
if ($row_type == 'st1'){
	$output .= '</div><div class="clear"></div>';
}
// main row
else if ($row_type == 'main-section'){
$output .= '</div></div></div></div><div class="clear"></div>';
}
// featured row
else if ($row_type == 'featured-section'){
$output .= '</div></div></div></section>'.$wr_section_separator.'<div class="clear"></div>';
}
//blank row by default
else {
$output .= '</div><div class="clear"></div>';
}
echo $output;