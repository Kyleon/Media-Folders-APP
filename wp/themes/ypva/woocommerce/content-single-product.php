<?php $kotlis_options = get_option('kotlis'); ?>
<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<!-- content -->	
<div class="content">
<?php $kotlis_shop_back = kotlis_AfterSetupTheme::return_thme_option('shopheaderimgdt','url');?>
<!-- column-image  -->	
                    <div class="column-image">
                        <div class="bg"  data-bg="<?php echo esc_url($kotlis_shop_back);?>"></div>
                        <div class="overlay"></div>
                        <div class="column-title">
                            <h2><?php if(!empty($kotlis_options['shoptitledt'])):?><?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('shoptitledt',''));?><?php else :?><?php esc_html_e('My Shop','kotlis');?><?php endif;?></h2>
                            <h3><?php echo esc_attr(kotlis_AfterSetupTheme::return_thme_option('shopsubtitledt',''));?></h3>
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
                    <div class="column-wrapper single-content-section">
                        <!--fixed-bottom-content -->	
                        <div class="fixed-bottom-content">
                            <!-- pagination   -->
                            <div class="content-nav-fixed">
                                <ul>
							<li>
								<?php $kotlis_previous_post = get_previous_post();
								$kotlis_url = is_object( $kotlis_previous_post ) ? get_permalink( $kotlis_previous_post->ID ) : '';
								$kotlis_title = is_object( $kotlis_previous_post ) ? get_the_title( $kotlis_previous_post->ID ) : '';
								if ($kotlis_previous_post) { 
								$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $kotlis_previous_post->ID ), 'kotlis_blog_pagination' );
								}
								?>
								<?php  if ($kotlis_previous_post) { ?>										
								<a href="<?php echo esc_url( $kotlis_url ) ?>" class="ln"><i class="fal fa-long-arrow-left"></i><span><?php if(!empty($kotlis_options['translet_opt_18'])):?><?php echo esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_18',''));?> - <?php else: ?><?php esc_html_e('Prev - ','kotlis');?><?php endif;?> <strong> <?php echo esc_html( $kotlis_title ) ?></strong></span></a>
								<?php  if ($kotlis_image) { ?>
								<div class="content-nav_mediatooltip cnmd_leftside"><img  src="<?php echo esc_url($kotlis_image[0]);?>"   alt="<?php echo esc_html( $kotlis_title ) ?>"></div>
								<?php } ;?>
								<?php } else { ?>
								
								<a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="ln">
									
								<i class="fal fa-long-arrow-left"></i><span><strong> 
								<?php if(!empty($kotlis_options['shop-page-nopost'])): ?>
								   <?php echo esc_html(($kotlis_options['shop-page-nopost']));?>
								<?php else:?>
									<?php esc_html_e('Back To Shop','kotlis');?>
								<?php endif;?>												
								</strong></span></a>
								<?php } ;?>
							</li>
							<li>
								<?php $kotlis_next_post = get_next_post();
								$kotlis_url = is_object( $kotlis_next_post ) ? get_permalink( $kotlis_next_post->ID ) : '';
								$kotlis_title = is_object( $kotlis_next_post ) ? get_the_title( $kotlis_next_post->ID ) : ''; 
								if ($kotlis_next_post) {
								$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $kotlis_next_post->ID ), 'kotlis_blog_pagination' );
								}
								?>
								<?php if ($kotlis_next_post) {?>										
								<a href="<?php echo esc_url( $kotlis_url ) ?>" class="rn"><span ><?php if(!empty($kotlis_options['translet_opt_20'])):?><?php echo esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_20',''));?> - <?php else: ?><?php esc_html_e('Next - ','kotlis');?> <?php endif;?><strong> <?php echo esc_html( $kotlis_title ) ?></strong></span> <i class="fal fa-long-arrow-right"></i></a>
								<?php  if ($kotlis_image) { ?>
								<div class="content-nav_mediatooltip cnmd_rightside"><img  src="<?php echo esc_url($kotlis_image[0]);?>"   alt="<?php echo esc_html( $kotlis_title ) ?>"></div>
								<?php } ;?>
								<?php } else { ?>
								
								<a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="rn">
								
								<span><strong> 
								<?php if(!empty($kotlis_options['shop-page-nopost'])): ?>
								   <?php echo esc_html(($kotlis_options['shop-page-nopost']));?>
								<?php else:?>
									<?php esc_html_e('Back To Shop','kotlis');?>
								<?php endif;?>												
								</strong></span> <i class="fal fa-long-arrow-right"></i></a>
								<?php } ;?>										
							</li>
						</ul>
                            </div>
                        </div>
                        <!--fixed-bottom-content end  -->	
                        <!--section  -->	
                         <section class="single-content-section">
                            <div class="container small-container">
                                <!-- post -->
                                <div class="post fl-wrap fw-post single-post ">
								<?php 
								/**
								 * Hook: woocommerce_before_single_product.
								 *
								 * @hooked wc_print_notices - 10
								 */
								do_action( 'woocommerce_before_single_product' );

								if ( post_password_required() ) {
									echo get_the_password_form(); // WPCS: XSS ok.
									return;
								}
								?>
								<h2><span><?php the_title()?></span></h2>
								<ul class="blog-title-opt">
								<li><?php the_time( get_option( 'date_format' ) ); ?></li>
								<?php if(!get_post_meta(get_the_ID(), 'product_cat', true)):
								$kotlis_post_category = wp_get_post_terms($post->ID,'product_cat');?>
								<?php if($kotlis_post_category):?>
								<li> <?php esc_html_e(' - ','kotlis');?></li>
								<ul class="post-categories">
								<?php  foreach($kotlis_post_category as $kotlis_post_cat):?><li><a href="<?php echo get_category_link($kotlis_post_cat->term_id); ?>"><?php echo esc_html($kotlis_post_cat->name);?></a></li><?php endforeach;?>
								</ul>
								<?php endif;?>
								<?php endif;?>	
								<li> <?php esc_html_e(' - ','kotlis');?> </li>
								<li><a href="#"><span class="author_avatar"> <?php
									// Display author avatar
									echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( '', 50 ) ); ?>	</span><?php the_author();?></a></li>
							</ul>
							<div class="clear"></div>
								<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<?php
	/**
	 * Hook: woocommerce_before_single_product_summary.
	 *
	 * @hooked woocommerce_show_product_sale_flash - 10
	 * @hooked woocommerce_show_product_images - 20
	 */
	do_action( 'woocommerce_before_single_product_summary' );
	?>

	<div class="blog-text fl-wrap">
		<?php
		/**
		 * Hook: woocommerce_single_product_summary.
		 *
		 * @hooked woocommerce_template_single_title - 5
		 * @hooked woocommerce_template_single_rating - 10
		 * @hooked woocommerce_template_single_price - 10
		 * @hooked woocommerce_template_single_excerpt - 20
		 * @hooked woocommerce_template_single_add_to_cart - 30
		 * @hooked woocommerce_template_single_meta - 40
		 * @hooked woocommerce_template_single_sharing - 50
		 * @hooked WC_Structured_Data::generate_product_data() - 60
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
	

	<?php
	/**
	 * Hook: woocommerce_after_single_product_summary.
	 *
	 * @hooked woocommerce_output_product_data_tabs - 10
	 * @hooked woocommerce_upsell_display - 15
	 * @hooked woocommerce_output_related_products - 20
	 */
	do_action( 'woocommerce_after_single_product_summary' );
	?>
	</div>
</div>
                                    
                                </div>
                                <!-- post end-->
                            </div>
                        </section>
                        <!--section end  -->	
                        <!--footer -->			
                        <?php get_template_part('template-parts/footer-copyrights'); ?>
                        <!--footer end  -->	
                    </div>
                    <!-- column-wrapper -->	



<?php do_action( 'woocommerce_after_single_product' ); ?>
</div>