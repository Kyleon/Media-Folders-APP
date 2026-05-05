<?php
$args = array(
    	'class'=>'',
    	'padding'=>'',
    	'margin'=>'',		
);
$html = "";
extract(shortcode_atts($args, $atts));
$html .= '<div class="sec-skills '.$class.'" style="';
			if ($margin != '') { $html .='margin:'.$margin.';';} 
			if ($padding != '') { $html .='padding:'.$padding.';';} 
	$html .='">'; 
    $html .= '<div class="skillbar-box animaper">';
		$html .= do_shortcode($content);
	$html .='</div>';
$html .= '</div>';
echo $html;