<?php
$args = array(
    	'class'=>'',
    	'padding'=>'',
    	'margin'=>'',		
);
$html = "";
extract(shortcode_atts($args, $atts));
$html .= '<div class="sec-services '.$class.'" style="';
			if ($margin != '') { $html .='margin:'.$margin.';';} 
			if ($padding != '') { $html .='padding:'.$padding.';';} 
	$html .='">'; 
    $html .= '<div class="serv-wrap fl-wrap">';
		$html .= do_shortcode($content);
	$html .='</div>';
$html .= '</div>';
echo $html;