<?php
$args = array(
	'data_title'=>'Location',	
	'data_url'=>'#',	
	'data_url_text'=>'NY, USA',	
	'data_url_target'=>'_self',	
);

extract(shortcode_atts($args, $atts));

$html = '';
    $html .= '<li>';
	if($data_title != '') {	
	$html .= '<span>'.esc_html($data_title).'</span>';
	}
	if($data_url != '') {
	$html .= '<a href="'.esc_url($data_url).'" target="'.esc_attr($data_url_target).'">'.esc_html($data_url_text).'</a>';
	}	
    $html .= '</li>';
echo $html;