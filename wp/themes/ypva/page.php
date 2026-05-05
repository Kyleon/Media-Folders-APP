<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_header();?>
    <?php if (get_post_meta($post->ID,'rnr_wr_pagetype',true)=='st1'){ ?> 
        <?php get_template_part('template-parts/page/default');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_pagetype',true)=='st2'){ ?> 
        <?php get_template_part('template-parts/page/sideblock');?>
	<?php } else  { ?>
		<?php get_template_part('template-parts/page/default');?>
	<?php }?>
       
  

