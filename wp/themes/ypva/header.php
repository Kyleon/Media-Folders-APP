<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>> 
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<?php $kotlis_options = get_option('kotlis'); ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
   	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<!--loader-->
<div class="loader-wrap">
	<div class="spinner">
		<div class="double-bounce1"></div>
		<div class="double-bounce2"></div>
	</div>
</div>
<!--loader end-->
<!-- main start  -->
<div id="main">
	<!-- header start  -->
	<header class="main-header">
        <?php get_template_part('template-parts/header/header-section'); ?>              
        <?php get_template_part('template-parts/header/menu-section'); ?>              
	</header>
	<!-- header end -->
	<!-- wrapper  -->	
	<div id="wrapper">	