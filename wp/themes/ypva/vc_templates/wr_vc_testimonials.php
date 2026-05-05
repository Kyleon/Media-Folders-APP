<?php

$args = array(
    	'class'=>'',
);

$html = "";

extract(shortcode_atts($args, $atts));

	$html .= '<div class="sec-testi '.$class.'">';
		$html .= '<div class="testilider fl-wrap" data-effects="slide">';
		    $html .= '<div class="swiper-container">';
		        $html .= '<div class="swiper-wrapper">';
		            $html .= do_shortcode($content);
		        $html .= '</div>';
		    $html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="testilider-controls">
                <div class="ss-slider-btn ss-slider-prev color-bg"><i class="fal fa-angle-left"></i></div>
                <div class="ss-slider-btn ss-slider-next color-bg"><i class="fal fa-angle-right"></i></div>
            </div>';
	$html .= '</div>';

echo $html;