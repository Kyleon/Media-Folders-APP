<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_template_part('header-portfolio-cat'); ?>
		<!-- content -->	
		<div class="content">
			<?php if (kotlis_AfterSetupTheme::return_thme_option('portfolio_scroll_swipe_show')!='no'){ ?>			
			<!-- bottom-filter-wrap -->
			<div class="bottom-filter-wrap hor-filter-wrap">			   
				<div class="scroll-down-wrap">
					<div class="mousey">
						<div class="scroller"></div>
					</div>
					<span><?php if (!empty($kotlis_options['portfolio_page_translet_scroll'])): ?><?php echo esc_html(($kotlis_options['portfolio_page_translet_scroll']));?> <?php else : ?><?php esc_html_e('Scroll down to Discover','kotlis');?><?php endif;?></span>
				</div>
			</div>
			<?php } ?>	
			<!-- bottom-filter-wrap end -->
			<div class="ff_panel-conainer fl-wrap">
				<!-- portfolio start -->
				<div class="gallery-items big-padding   four-column fl-wrap vis-thumb-info lightgallery">
					<?php global $loop; 
						$args = array_merge( $wp_query->query, array( 'post_type' => 'portfolio', 'posts_per_page'=>-1, ) );
						query_posts( $args );
					?>	
					<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
				
						<?php $kotlis_portfolio_category = wp_get_post_terms($post->ID,'portfolio_category');?>
						<?php 
							$kotlis_class = ""; 
							$kotlis_categories = ""; 
							foreach ($kotlis_portfolio_category as $kotlis_item) {
								$kotlis_class.=esc_attr($kotlis_item->slug . ' ');
								$kotlis_categories.='<a>';
								$kotlis_categories.=esc_attr($kotlis_item->name . '  ');
								$kotlis_categories.='</a>';
							}?>
							<?php if (has_post_thumbnail( $post->ID ) ):
						$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'kotlis_portfolio_image_gallery_car' );
						$kotlis_image_poup = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), '' );?>						
							<div class="gallery-item <?php echo esc_attr($kotlis_class);?>">
								<div class="grid-item-holder hov_zoom">
								    <?php if ($kotlis_options['portfolio_hover_st'] == 'st2') {?>
								        <a href="<?php the_permalink();?>"><img  src="<?php echo esc_url($kotlis_image[0]);?>"  alt="<?php the_title();?>"></a>
									<?php } else { ?>
								        <img  src="<?php echo esc_url($kotlis_image[0]);?>"  alt="<?php the_title();?>">
									<?php } ?>	
									<?php if ($kotlis_options['portfolio_hover_st'] != 'st2') {?>
										<?php if (get_post_meta($post->ID,'rnr_video_portpost_vid_url',true)):?>
										<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_video_portpost_vid_url',true));?>" class="box-media-zoom   popup-image"><i class="fal fa-play"></i></a>
										<?php else : ?>
										<a href="<?php echo esc_url($kotlis_image[0]);?>" class="box-media-zoom   popup-image"><i class="fal fa-search"></i></a>
										<?php endif;?>   
									<?php } ?>	
									<div class="thumb-info">
										<h3><a href="<?php the_permalink();?>"><?php the_title();?></a></h3>
										<p>
										<?php if ( has_excerpt( $post->ID ) ) { ?>
											<?php the_excerpt(); ?>
										<?php } else { ?>
											<?php
												$kotlis_excerpt= substr(strip_tags($post->post_content), 0, 58);
												update_post_meta(get_the_ID(), 'kotlis_excerpt', $kotlis_excerpt);
													echo esc_html($kotlis_excerpt);
											?>
										<?php } ?>	
										</p>
									</div>
								</div>														  	
							</div>
							<!-- gallery-item end-->
						<?php endif;?>
					<?php  endwhile; endif; wp_reset_postdata(); ?>                                        
				</div>
				<!-- portfolio end -->
			</div>
		</div>
		<!--content end-->	
<?php get_footer(); ?>		