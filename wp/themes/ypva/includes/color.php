<?php
add_action('wp_head', 'kotlis_custom_colors', 160);
function kotlis_custom_colors() { 
$kotlis_options = get_option('kotlis');
?>
 
<style type="text/css" class="kotlis-custom-dynamic-css">

<?php if(!empty(kotlis_AfterSetupTheme::return_thme_option('opt_logo_mobile_dimensions','height'))||!empty(kotlis_AfterSetupTheme::return_thme_option('opt_logo_mobile_dimensions','width'))) { ?>

@media only screen and (max-width: 768px){
body .logo-holder img{
	height:<?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('opt_logo_mobile_dimensions','height'));?>!important;
	width:<?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('opt_logo_mobile_dimensions','width'));?>!important;
}
}
<?php } ;?>
<?php if(!empty(kotlis_AfterSetupTheme::return_thme_option('opt_header_logo_spacing_resposive','margin-top'))) { ?>
@media only screen and (max-width: 768px){
body .logo-holder{
	top:<?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('opt_header_logo_spacing_resposive','margin-top'));?>!important;
	
}
}
<?php } ;?>

<?php if(!empty(kotlis_AfterSetupTheme::return_thme_option('opt_header_nav_menu_sub_bg_color','rgba'))) { ?>
.nav-holder nav li ul {
    background: <?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('opt_header_nav_menu_sub_bg_color','rgba'));?>;
}
<?php } ;?>

 </style>
 
 
 <?php 
	}
?>
