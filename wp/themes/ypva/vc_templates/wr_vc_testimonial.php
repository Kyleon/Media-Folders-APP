<?php

$args = array(
		'image'=>'',
		'companyname'=>'',
		'clientname'=>'',
		'button_text'=>'Via Twitter',
		'button_url'=>'',
		'button_target'=>'',
		
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
	$link_target_opt ='';
	if ($button_target == "_blank"){
	$link_target_opt .='_blank';
	}
	else if ($button_target == "_parent"){
	$link_target_opt .='_parent';
	}
	else if ($button_target == "_top"){
	$link_target_opt .='_top';
	}
	else {
	$link_target_opt .='_self';
	}

    $html .= '<div class="swiper-slide">';
        $html .= '<div class="testi-item fl-wrap">';
			if (is_numeric($image)) {
			$html .= '<div class="testi-avatar"><img src="'.$kotlis_image.'" alt="'.esc_attr($kotlis_alt).'"></div>';
			}
			$html .= '<h3>'.do_shortcode($clientname).'</h3>';
			$html .= '<p>"'.$content.' "</p>';
			if ($button_url != ""){
			$html .= '<a href="'.$button_url.'" class="teti-link" target="'.$link_target_opt.'">'.$button_text.'</a>';
			}
        $html .= '</div>';
    $html .= '</div>';

echo $html;