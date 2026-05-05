<?php $kotlis_options = get_option('kotlis');?>
<?php
/*Template Name:Home Page Template*/
 get_header();
?>
<?php while ( have_posts() ) : the_post(); ?>
	<?php if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st2'){ ?>
	<?php get_template_part('template-parts/intro/multi-slideshow');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st3'){ ?>
	<?php get_template_part('template-parts/intro/full-carousel');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st5'){ ?>
	<?php get_template_part('template-parts/intro/full-image');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st6'){ ?>
	<?php get_template_part('template-parts/intro/full-slider');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st7'){ ?>
	<?php get_template_part('template-parts/intro/slideshow');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st8'){ ?>
	<?php get_template_part('template-parts/intro/vimeo');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_intro_sc_opt',true)=='st4'){ ?>
	<?php get_template_part('template-parts/intro/rev');?>	
	<?php } else { ?>
	<?php get_template_part('template-parts/intro/slider-details');?>
	<?php } ;?>
<?php endwhile; ?>
<?php wp_reset_postdata();?>

<?php get_footer(); ?>	