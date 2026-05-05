<?php
$args = array(
		'class'=>'',
    	'title'=>'',
    	'percentage'=>'',
    	'color'=>'',
    	'font_size'=>'',	
);
extract(shortcode_atts($args, $atts));

$html = '';
    if ($title != '') {	
        $html .= '<div class="custom-skillbar-title"><span>'.$title.'</span></div>';
	}
	if( $percentage != '') {
		$html .='<div class="skill-bar-percent">'.$percentage.'%</div>';
		$html .='<div class="skillbar-bg" data-percent="'.$percentage.'%">';					
			$html .=' <div class="custom-skillbar"></div>';
		$html .='</div>';
	}	

echo $html;