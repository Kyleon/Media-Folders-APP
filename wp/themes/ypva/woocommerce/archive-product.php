<?php $kotlis_options = get_option('kotlis'); ?>
<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );
?>

<?php if ( is_active_sidebar( 'sidebar-4' ) ) : ?>
<?php /**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

?>
<div class="content">
<div class="fl-wrap single-header single-content-section">
			<section class="img-section">
					
						<?php $kotlis_shop_back = kotlis_AfterSetupTheme::return_thme_option('shopheaderimg','url');
						$kotlis_dot="'";
						?>
						<?php if ( is_product_category() ){
						global $wp_query;
						$kotlis_cat = $wp_query->get_queried_object();
						$kotlis_thumbnail_id = get_term_meta( $kotlis_cat->term_id, 'thumbnail_id', true );
						$kotlis_image = wp_get_attachment_url( $kotlis_thumbnail_id );
						if ( $kotlis_image ) {
						echo '<div class="bg"  data-bg="'.$kotlis_image.'" ></div>';
						}
						else {
						echo '<div class="bg"  data-bg="'.$kotlis_shop_back.'" ></div>';
						}
						} else { ?>
                        <div class="bg "  data-bg="<?php echo esc_url($kotlis_shop_back);?>" ></div>
						<?php } ;?>
					
					
				    <div class="overlay"></div>                        
					<div class="container">
						<div class="img-section-title">
							<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
							<h2><?php woocommerce_page_title(); ?></h2>
							<?php endif; ?>
							<?php if ( is_product_category() ){ ?>
							<h3><?php
									/**
									 * Hook: woocommerce_archive_description.
									 *
									 * @hooked woocommerce_taxonomy_archive_description - 10
									 * @hooked woocommerce_product_archive_description - 10
									 */
									do_action( 'woocommerce_archive_description' );
									?>
							</h3>
							<?php } else {?>
							<?php if(!empty($kotlis_options['shopsubtitle'])):?>
							<h3><?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('shopsubtitle',''));?></h3>
							<?php endif;?>
							<?php } ;?>
						  				
						</div>
					</div>
				</section>

<section class="single-content-section">
	<div class="container">
	<div class="posts-wrap">
<?php
if ( woocommerce_product_loop() ) {

	/**
	 * Hook: woocommerce_before_shop_loop.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 *
	 * @hooked woocommerce_pagination - 10
	 */
	do_action( 'woocommerce_after_shop_loop' );
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */
	do_action( 'woocommerce_no_products_found' );
}

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
//do_action( 'woocommerce_sidebar' );

//get_footer( 'shop' ); 
?>
</div>
<!-- posts_wrap end-->
						<!-- sidebar-posts_wrap-->
						<?php if ( is_active_sidebar( 'sidebar-4' ) ) : ?>
						<div class="sidebar-posts_wrap">
						    <?php dynamic_sidebar( 'sidebar-4' ); ?>                                                            
						</div>
						<?php endif; ?>
						 <!-- sidebar-posts_wrap end-->
</div>
</section>
<!--section end  -->	
<?php get_template_part('template-parts/footer-copyrights2'); ?> 
</div>
</div>


<?php else : ?>
<?php /**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

?>
<div class="content">
<!-- column-image  -->	
			<div class="column-image">
							
				<?php $kotlis_shop_back = kotlis_AfterSetupTheme::return_thme_option('shopheaderimg','url');
						$kotlis_dot="'";
						?>
						<?php if ( is_product_category() ){
						global $wp_query;
						$kotlis_cat = $wp_query->get_queried_object();
						$kotlis_thumbnail_id = get_term_meta( $kotlis_cat->term_id, 'thumbnail_id', true );
						$kotlis_image = wp_get_attachment_url( $kotlis_thumbnail_id );
						if ( $kotlis_image ) {
						echo '<div class="bg"  data-bg="'.$kotlis_image.'" ></div>';
						}
						else {
						echo '<div class="bg"  data-bg="'.$kotlis_shop_back.'" ></div>';
						}
						} else { ?>
                        <div class="bg "  data-bg="<?php echo esc_url($kotlis_shop_back);?>" ></div>
						<?php } ;?>
				<div class="overlay"></div>
				<div class="column-title">
					
					<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>
							<h2><?php woocommerce_page_title(); ?></h2>
							<?php endif; ?>
							<?php if ( is_product_category() ){ ?>
							<h3><?php
									/**
									 * Hook: woocommerce_archive_description.
									 *
									 * @hooked woocommerce_taxonomy_archive_description - 10
									 * @hooked woocommerce_product_archive_description - 10
									 */
									do_action( 'woocommerce_archive_description' );
									?>
							</h3>
							<?php } else {?>
							<?php if(!empty($kotlis_options['shopsubtitle'])):?>
							<h3><?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('shopsubtitle',''));?></h3>
							<?php endif;?>
							<?php } ;?>
				   				
				</div>
			    					
				<div class="column-notifer">
					<div class="scroll-down-wrap transparent_sdw">
						<div class="mousey">
							<div class="scroller"></div>
						</div>
						<span><?php if(!empty($kotlis_options['blog_page_translet_scroll'])): ?><?php echo esc_html(($kotlis_options['blog_page_translet_scroll']));?> <?php else : ?><?php esc_html_e('Scroll down to Discover','kotlis');?><?php endif;?></span>
					</div>
				</div>
				
				<div class="fixed-column-dec"></div>
			</div>
			<!-- column-image end  -->
			<!-- column-wrapper -->	
			<div class="column-wrapper  single-content-section">

				<!--section  -->	
				<section class="single-content-section">
					<div class="container small-container">
						<?php
if ( woocommerce_product_loop() ) {

	/**
	 * Hook: woocommerce_before_shop_loop.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */
	do_action( 'woocommerce_no_products_found' );
}

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
//do_action( 'woocommerce_sidebar' );

//get_footer( 'shop' ); 
?>
					</div>
					<!-- fixed-bottom-content-->	
					<div class="fixed-bottom-content">
					<?php if ( woocommerce_product_loop() ) { ?>
						<!-- pagination   -->
						<?php /**
						 * Hook: woocommerce_after_shop_loop.
						 *
						 * @hooked woocommerce_pagination - 10
						 */
						do_action( 'woocommerce_after_shop_loop' );
						?>
						<?php } ;?>
						<!-- pagination  end -->
					</div>
					<!-- fixed-bottom-content end -->						
				</section>
				<!--section end  -->	
				<?php get_template_part('template-parts/footer-copyrights'); ?> 
			</div>
			<!-- column-wrapper -->	
</div>
<?php endif;?>
