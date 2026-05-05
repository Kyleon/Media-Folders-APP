<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_header();?>
		
		<?php if (get_post_meta($post->ID,'rnr_wr_port_dt_opt',true)=='st1'){ ?> 
        <?php get_template_part('template-parts/portfolio-details/gallery-des');?>
		<?php }
		else if (get_post_meta($post->ID,'rnr_wr_port_dt_opt',true)=='st2'){ ?> 
        <?php get_template_part('template-parts/portfolio-details/carousel');?>
		<?php }
		else if (get_post_meta($post->ID,'rnr_wr_port_dt_opt',true)=='st3'){ ?> 
        <?php get_template_part('template-parts/portfolio-details/gallery-full');?>
		<?php }
		else if (get_post_meta($post->ID,'rnr_wr_port_dt_opt',true)=='st4'){ ?> 
        <?php get_template_part('template-parts/portfolio-details/slider');?>
		<?php }
		else  { ?>
		<?php get_template_part('template-parts/portfolio-details/gallery-des');?>
		<?php }?>
		
<?php get_footer(); ?>