<?php
$args = array(
	'class'=>'',
);
extract(shortcode_atts($args, $atts));

$html = "";
$html .='<div class="caption-wrap fl-wrap"><ul>';
$html .= do_shortcode($content);			
$html .='</ul></div>';	
echo $html;