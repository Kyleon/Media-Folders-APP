<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_header();?>
	<?php if (kotlis_AfterSetupTheme::return_thme_option('blogtyle')=='st2'){ ?>
	    <?php get_template_part('template-parts/cat/blog-left');?>
	<?php } else if (kotlis_AfterSetupTheme::return_thme_option('blogtyle')=='st3'){ ?>
	    <?php get_template_part('template-parts/cat/blog-side-block');?>
	<?php } else { ?>
	    <?php get_template_part('template-parts/cat/blog-right');?>
	<?php } ;?>
<?php get_footer(); ?>	