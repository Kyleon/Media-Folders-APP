<?php if (get_post_meta($post->ID,'rnr_ns_home_intro_car_new_opt',true)=='st2'){ ?>
<?php get_template_part('template-parts/intro/custom-carousel');?>
<?php } else { ?>
		<!--content -->	
		<div class="content full-height  hidden-item no-mob-hidden">
			<!-- fw-carousel-wrap -->
			<div class="fw-carousel-wrap fsc-holder">
				<!-- fw-carousel  -->
				<?php if (get_post_meta($post->ID,'rnr_md_car_slideshow_speed',true)):?>
				<?php $kotlis_slider_speed = get_post_meta($post->ID,'rnr_md_car_slideshow_speed',true);?>
				<?php else: ?>
				<?php $kotlis_slider_speed = '1400';?>
				<?php endif;?>
				<div class="fw-carousel  fs-gallery-wrap fl-wrap full-height lightgallery" data-mousecontrol="true" data-slider-speed="<?php echo esc_attr($kotlis_slider_speed);?>" data-slider-autoplay="false">
					<div class="swiper-container">
						<div class="swiper-wrapper">
							<!-- swiper-slide-->  
							<?php global $post, $post_id;?>
							<?php $kotlis_showpost= get_post_meta($post->ID, 'rnr_portfolio-post-show-home', true);$kotlis_categoryname= get_post_meta($post->ID, 'rnr_portfolio-post-cat-home', true);$kotlis_postoffset= get_post_meta($post->ID, 'rnr_portfolio-post-offset-home', true);$kotlis_paged=(get_query_var('paged'))?get_query_var('paged'):1;
							$kotlis_loop = new WP_Query( array( 'post_type' => 'portfolio', 'posts_per_page'=>$kotlis_showpost, 'portfolio_category'=> $kotlis_categoryname, 'offset' => $kotlis_postoffset, 'paged'=>$kotlis_paged ) ); ?>
							<?php while ( $kotlis_loop->have_posts() ) : $kotlis_loop->the_post();?>
							<?php if (has_post_thumbnail( $post->ID ) ):
								$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), '' );
								$kotlis_image_title = get_the_title( get_post_thumbnail_id( $post->ID ), '' );
								$kotlis_image_caption = wp_get_attachment_caption( get_post_thumbnail_id( $post->ID ), '' );
								?>
							<div class="swiper-slide hov_zoom">
								<img  src="<?php echo esc_url($kotlis_image[0]);?>"   alt="<?php the_title();?>">
								<?php if (get_post_meta($post->ID,'rnr_video_portpost_vid_url',true)):?>
								<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_video_portpost_vid_url',true));?>" class="box-media-zoom   popup-image" data-sub-html="<h4><?php echo esc_attr($kotlis_image_title);?></h4><p><?php echo esc_attr($kotlis_image_caption);?></p>"><i class="fal fa-play"></i></a>
								<?php else : ?>
								<a href="<?php echo esc_url($kotlis_image[0]);?>" class="box-media-zoom   popup-image" data-sub-html="<h4><?php echo esc_attr($kotlis_image_title);?></h4><p><?php echo esc_attr($kotlis_image_caption);?></p>"><i class="fal fa-search"></i></a>
								<?php endif;?>
								<div class="thumb-info">
									<h3><a href="<?php the_permalink();?>"><?php the_title();?></a></h3>
									<p>
										<?php the_excerpt(); ?>
									</p>
								</div>
							</div>
							<!-- swiper-slide end-->  
							<?php endif;?>
							<?php endwhile;
							wp_reset_postdata();?> 							
							
							<!-- swiper-slide-->  
							<?php if (get_post_meta($post->ID,'rnr_portfolio_home_tcustom_button_url',true)):?>
							<div class="swiper-slide swiper-link-wrap hov_zoom">
								<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_portfolio_home_tcustom_button_url',true));?>" class="swiper-link"><span><?php echo esc_html(get_post_meta($post->ID,'rnr_portfolio_home_tcustom_button_text',true));?></span></a>
							</div>
							<?php endif;?>
							<!-- swiper-slide end-->                                     
						</div>
					</div>
				</div>
				<!-- fw-carousel end -->
			</div>
			<!--slider-counter-->
			<div class="slider-counter_wrap">
				<div class="fw-carousel-counter"></div>
			</div>
			<!--slider-counter end-->
			<!--bottom-panel-->
			<div class="bottom-panel">
				<div class="bottom-panel-column bottom-panel-column_left">
					<?php if (get_post_meta($post->ID,'rnr_portfolio_home_scroll_swipe',true)=='no'){ ?>
					<?php } else { ?>	
					<div class="scroll-down-wrap">
						<div class="mousey">
							<div class="scroller"></div>
						</div>
						<span><?php if (get_post_meta($post->ID,'rnr_portfolio_home_translet_opt3',true)):?><?php echo esc_html(get_post_meta($post->ID,'rnr_portfolio_home_translet_opt3',true));?> <?php else : ?><?php esc_html_e('Scroll down or  Swipe','kotlis');?><?php endif;?></span>
					</div>
					<?php } ?>				
					<div class="fs-controls_wrap">
						<div class="fw_cb fw-carousel-button-prev"><i class="fal fa-angle-left"></i></div>
						<div class="fw_cb fw-carousel-button-next"><i class="fal fa-angle-right"></i></div>
					</div>
				</div>
				<div class="bottom-panel-column bottom-panel-column_right">
					<div class="half-scrollbar">
						<div class="hs_init"></div>
					</div>
				</div>
			</div>
			<!--bottom-panel end-->
		</div>
		<!--content end-->	
	<?php };?>