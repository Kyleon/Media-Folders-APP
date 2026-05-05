<?php
$args = array(
		'class'=>'',
    	'image'=>'',
    	'place'=>'',
    	'date'=>'',	
);
extract(shortcode_atts($args, $atts));
	if (is_numeric($image)) {
		$kotlis_image = wp_get_attachment_url( $image );
		$kotlis_alt = get_post_meta($image, '_wp_attachment_image_alt', TRUE);
	} else {
		$kotlis_image = $image;
		$kotlis_alt = $image;
	}
$html = '';
    $html .= '<div class="serv-item">';
		if (is_numeric($image)) {
		$html .= '<div class="serv-media">';
		    $html .= '<img src="'.$kotlis_image.'" alt="'.esc_attr($kotlis_alt).'" />';
		$html .= '</div>';
		}	
        if ($content != '' || $date != '' || $place != '') {		
		$html .='<div class="serv-text">';
			if ($content != '') {	
				$html .=''.$content.'';
			} 
		    if ($date != '' || $place != '') {			
			    $html .='<div class="serv-price">'.$date.' <span> '.$place.'</span></div>';
			}			
		$html .='</div>';	
		} 						
    $html .='</div>';
echo $html;