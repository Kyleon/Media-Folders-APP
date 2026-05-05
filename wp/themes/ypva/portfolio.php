<?php $kotlis_options = get_option('kotlis');?>
<?php
/*Template Name:Portfolio Page Template*/
?>
 
	<?php if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st2'){ ?> 
        <?php get_template_part('template-parts/portfolio/row-2');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st3'){ ?> 
        <?php get_template_part('template-parts/portfolio/row-3');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st4'){ ?> 
        <?php get_template_part('template-parts/portfolio/row-1');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st5'){ ?> 
        <?php get_template_part('template-parts/portfolio/boxed-grid');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st6'){ ?> 
        <?php get_template_part('template-parts/portfolio/column-grid');?>
	<?php } else if (get_post_meta($post->ID,'rnr_wr_portfolio_pagetype',true)=='st7'){ ?> 
        <?php get_template_part('template-parts/portfolio/column-grid-2');?>
	<?php } else  { ?>
		<?php get_template_part('template-parts/portfolio/row-2');?>
	<?php }?>
