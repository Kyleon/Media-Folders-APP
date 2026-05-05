<?php
$args = array(
	'class'=>'',
	'featyretype'=>'',
	'title'=>'',			
	'subtitle'=>'',			
	'number'=>'',			
	'margin_bottom'=>'',	
);

$html = "";

extract(shortcode_atts($args, $atts));

	if($featyretype == "st2"){
		if($title != '') { 
		$html .='<div class="pr-det-container">'; 			
			$html .='<h2>'.do_shortcode($title).'</h2>';	
		$html .='</div>';
		}  		
	} 
	else {		
		$html .='<div class="section-title fl-wrap '.esc_attr($class).'"';
				if($margin_bottom != '') { $html .='style="margin-bottom:'.esc_attr($margin_bottom).';"';} 
		$html .='>'; 
			if($title != '') { 
			$html .='<h3>'.do_shortcode($title).'</h3>';
			} 
			if($subtitle != '') { 
			$html .='<h4>'.esc_html($subtitle).'</h4>';
			}
			if($number != '') { 
			$html .='<div class="section-number">'.esc_html($number).'</div>';
			}	
		$html .='<span class="separator sep-b"></span>
			<div class="clearfix"></div>';  		
		$html .='</div>';  		
    }
echo $html;