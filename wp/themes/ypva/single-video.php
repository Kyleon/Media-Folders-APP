<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_header();?>
<?php if (have_posts()) : while ( have_posts() ) : the_post();?>
<?php if (has_post_thumbnail( $post->ID ) ):
$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), '' );?>
<?php endif;?>
		<!-- content -->	
		<div class="content">
			<!-- column-image  -->	
			<div class="column-image">
			
                <?php $kotlis_back_images = rwmb_meta( 'rnr_video_column_grid_details_sidebar_image','type=image&size=' ); ?>
				<?php if ( ! empty( $kotlis_back_images ) ) { ?>
				<?php foreach ( $kotlis_back_images as $kotlis_back_image ){ ?>			
				<div class="bg"  data-bg="<?php echo esc_url(($kotlis_back_image['url']));?>"></div>
				<?php } ;?>
				<?php } else { ?>
				<div class="bg"  data-bg="<?php echo esc_url($kotlis_image[0]);?>"></div>
				<?php } ;?>
				<div class="overlay"></div>
				<div class="column-title">
					<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_details_sidebar_title',true))):?>
					<h2><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_details_sidebar_title',true)); ?></h2>
					<?php else: ?>
					<h2><?php the_title();?></h2>
					<?php endif;?>				
					<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_details_sidebar_subtitle',true))):?>
					<h3><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_details_sidebar_subtitle',true)); ?></h3>
					<?php endif;?>	
				</div>
			    <?php if (get_post_meta($post->ID,'rnr_video_column_grid_details_scroll_swipe',true)!='no'){ ?>				
				<div class="column-notifer">
					<div class="scroll-down-wrap transparent_sdw">
						<div class="mousey">
							<div class="scroller"></div>
						</div>
						<span><?php if (get_post_meta($post->ID,'rnr_video_column_grid_details_translet_scroll',true)):?><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_details_translet_scroll',true));?> <?php else : ?><?php esc_html_e('Scroll down to Discover','kotlis');?><?php endif;?></span>
					</div>
				</div>
				<?php } ?>				
				<div class="fixed-column-dec"></div>
			</div>
			<!-- column-image end  -->	
			<!-- column-wrapper -->	
			<div class="column-wrapper single-content-section">
				<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_project_prev_next',true))!='no'):?>				
				<div class="fixed-bottom-content">
					<!-- pagination   -->
					<div class="content-nav-fixed">
						<ul>
							<li>
								<?php $kotlis_previous_post = get_previous_post();
								$kotlis_url = is_object( $kotlis_previous_post ) ? get_permalink( $kotlis_previous_post->ID ) : '';
								$kotlis_title = is_object( $kotlis_previous_post ) ? wp_trim_words(get_the_title( $kotlis_previous_post->ID ), 3) : '';
								if ($kotlis_previous_post) { 
								$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $kotlis_previous_post->ID ), 'kotlis_blog_pagination' );
								}
								?>
								<?php  if ($kotlis_previous_post) { ?>									
								<a href="<?php echo esc_url( $kotlis_url ) ?>" class="ln"><i class="fal fa-long-arrow-left"></i><span><?php if (!empty($kotlis_options['translet_opt_18'])):?><?php echo esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_18',''));?> - <?php else: ?><?php esc_html_e('Prev - ','kotlis');?><?php endif;?> <strong><?php echo esc_html( $kotlis_title ) ?></strong></span></a>
								<div class="content-nav_mediatooltip cnmd_leftside"><img  src="<?php echo esc_url($kotlis_image[0]);?>"   alt="<?php echo esc_html( $kotlis_title ) ?>"></div>
								<?php } else { ?>
									<?php if (!empty($kotlis_options['video-page-url'])): ?>
									<a href="<?php echo esc_url(($kotlis_options['video-page-url']));?>" class="ln">
									<i class="fal fa-long-arrow-left"></i><span><strong> 
										<?php if (!empty($kotlis_options['video-page-nopost'])): ?>
										   <?php echo esc_html(($kotlis_options['video-page-nopost']));?>
										<?php else:?>
											<?php esc_html_e('Back To Video','kotlis');?>
										<?php endif;?>												
									</strong></span></a>									
									<?php else:?>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ln">
									<i class="fal fa-long-arrow-left"></i><span><strong> 
										<?php if (!empty($kotlis_options['port-page-back-home'])): ?>
										   <?php echo esc_html(($kotlis_options['port-page-back-home']));?>
										<?php else:?>							
										   <?php esc_html_e('Back To Home','kotlis');?>
										<?php endif;?>									
								
									</strong></span></a>
									<?php endif;?>								
								<?php } ;?>
							</li>
							<li>
								<?php $kotlis_next_post = get_next_post();
								$kotlis_url = is_object( $kotlis_next_post ) ? get_permalink( $kotlis_next_post->ID ) : '';
								$kotlis_title = is_object( $kotlis_next_post ) ? wp_trim_words(get_the_title( $kotlis_next_post->ID ), 3) : ''; 
								if ($kotlis_next_post) {
								$kotlis_image = wp_get_attachment_image_src( get_post_thumbnail_id( $kotlis_next_post->ID ), 'kotlis_blog_pagination' );
								}
								?>
								<?php if ($kotlis_next_post) {?>							
								<a href="<?php echo esc_url( $kotlis_url ) ?>" class="rn"><span><?php if(!empty($kotlis_options['translet_opt_20'])):?><?php echo esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_20',''));?> - <?php else: ?><?php esc_html_e('Next - ','kotlis');?> <?php endif;?> <strong><?php echo esc_html( $kotlis_title ) ?></strong></span> <i class="fal fa-long-arrow-right"></i></a>
								<div class="content-nav_mediatooltip cnmd_rightside"><img  src="<?php echo esc_url($kotlis_image[0]);?>"   alt="<?php echo esc_html( $kotlis_title ) ?>"></div>
								<?php } else { ?>
									<?php if (!empty($kotlis_options['video-page-url'])): ?>
									<a href="<?php echo esc_url(($kotlis_options['video-page-url']));?>" class="rn">
									<span><strong> 
										<?php if (!empty($kotlis_options['video-page-nopost'])): ?>
										   <?php echo esc_html(($kotlis_options['video-page-nopost']));?>
										<?php else:?>
											<?php esc_html_e('Back To Video','kotlis');?>
										<?php endif;?>												
									</strong></span> <i class="fal fa-long-arrow-right"></i></a>									
									<?php else:?>
									<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="rn">
									<span><strong> 
										<?php if (!empty($kotlis_options['port-page-back-home'])): ?>
										   <?php echo esc_html(($kotlis_options['port-page-back-home']));?>
										<?php else:?>							
										   <?php esc_html_e('Back To Home','kotlis');?>
										<?php endif;?>									
									</strong></span> <i class="fal fa-long-arrow-right"></i></a>
									<?php endif;?>								
								<?php } ;?>
							</li>
						</ul>
					</div>
				</div>
				<?php endif;?>
				<section id="sec1">
					<div class="container small-container">
						<!-- post -->
						<div class="post fl-wrap fw-post single-post ">
						    <?php if (get_post_meta($post->ID,'rnr_video_column_grid_gallery_title_section',true)!='no'){ ?>
							<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_gallery_title',true))):?>
							<div class="section-title fl-wrap">
							    <?php if (( get_post_meta($post->ID,'rnr_video_column_grid_gallery_title',true))):?>
								<h3><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_gallery_title',true)); ?></h3>
								<?php endif;?>
								<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_gallery_subtitle',true))):?>
								<h4><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_gallery_subtitle',true)); ?></h4>
								<?php endif;?>
								<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_gallery_number',true))):?>
								<div class="section-number"><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_gallery_number',true)); ?></div>
								<?php endif;?>
							</div>
							<?php endif;?>
							<?php } ?>							
							<div class="pr-det-container  ">
								<div class="fl-wrap">
									<!-- blog media -->
									<?php if (get_post_meta($post->ID,'rnr_video_column_grid_gallery_images_section',true)=='no'){ ?>
									<?php } else { ?>									
									<div class="blog-media fl-wrap">
									<?php if(get_post_meta($post->ID,'rnr_md_video_gallery_column_opt',true)!='st3'){ ?>
										<!-- video start -->
									<?php 
									global $kotlisg_gallery_column;
									if(get_post_meta($post->ID,'rnr_md_video_gallery_column_opt',true)=='st2'){ 
									$kotlisg_gallery_column="two-column";
									}
									else {
									$kotlisg_gallery_column="three-column";
									};
									?>
										<div class="gallery-items min-pad   <?php echo esc_attr($kotlisg_gallery_column);?> fl-wrap lightgallery">
											<!-- gallery-item-->
											<?php
											$kotlis_car_slide_opt = rwmb_meta( 'rnr_so_drt_po_gallery' );
											if ( ! empty( $kotlis_car_slide_opt ) ) {
											foreach ( $kotlis_car_slide_opt as $kotlis_car_slide_opts ) { ;?>
											<?php $kotlis_column = isset( $kotlis_car_slide_opts['rnr_md_video_gallery_column'] ) ? $kotlis_car_slide_opts['rnr_md_video_gallery_column'] : ''; ?>
											<?php $kotlis_gallery_pop = isset( $kotlis_car_slide_opts['rnr_ns_video_gallery_video_opt'] ) ? $kotlis_car_slide_opts['rnr_ns_video_gallery_video_opt'] : ''; ?>
											<?php $kotlis_image_ids = isset( $kotlis_car_slide_opts['rnr_video-image-popu'] ) ? $kotlis_car_slide_opts['rnr_video-image-popu'] : array();
											foreach ( $kotlis_image_ids as $kotlis_image_id ) {
											$kotlis_image = RWMB_Image_Field::file_info( $kotlis_image_id, array( 'size' => '' ) ); ?>	
											<?php if ( !empty( $kotlis_gallery_pop ) ) { ?>
											<div class="gallery-item  <?php echo esc_attr($kotlis_column);?>">
												<div class="grid-item-holder hov_zoom">
													<img  src="<?php echo esc_url(($kotlis_image['url']));?>"  alt="<?php echo esc_attr(($kotlis_image['title']));?>">
													<a href="<?php echo esc_html($kotlis_gallery_pop);?>" class="box-media-zoom   popup-image"><i class="fal fa-play"></i></a>                                    
												</div>
											</div>
											<?php } ;?>
											<?php } } } ;?>
											
											<!-- gallery-item end--> 											
										</div>
										<!-- video end -->
										<?php } ;?>
										<?php if (get_post_meta($post->ID,'rnr_md_video_gallery_column_opt',true)=='st3'){ ?>
										<!-- portfolio start -->
										<div class="single-image-wrap fl-wrap lightgallery">
											<!-- gallery-item-->
											<?php
											$kotlis_car_slide_opt = rwmb_meta( 'rnr_so_drt_po_gallery' );
											if ( ! empty( $kotlis_car_slide_opt ) ) {
											foreach ( $kotlis_car_slide_opt as $kotlis_car_slide_opts ) { ;?>
											<?php $kotlis_column = isset( $kotlis_car_slide_opts['rnr_md_video_gallery_column'] ) ? $kotlis_car_slide_opts['rnr_md_video_gallery_column'] : ''; ?>
											<?php $kotlis_gallery_pop = isset( $kotlis_car_slide_opts['rnr_ns_video_gallery_video_opt'] ) ? $kotlis_car_slide_opts['rnr_ns_video_gallery_video_opt'] : ''; ?>
											<?php $kotlis_image_ids = isset( $kotlis_car_slide_opts['rnr_video-image-popu'] ) ? $kotlis_car_slide_opts['rnr_video-image-popu'] : array();
											foreach ( $kotlis_image_ids as $kotlis_image_id ) {
											$kotlis_image = RWMB_Image_Field::file_info( $kotlis_image_id, array( 'size' => '' ) ); ?>	
											<?php if ( !empty( $kotlis_gallery_pop ) ) { ?>											
											<div class="fl-wrap single-image hov_zoom">
												<div class="bg-anim"><span></span></div>
												<img  src="<?php echo esc_url(($kotlis_image['url']));?>"  alt="<?php echo esc_attr(($kotlis_image['title']));?>">
												<a data-src="<?php echo esc_html($kotlis_gallery_pop);?>" class="popup-image box-media-zoom">
												<i class="fa fa-play"></i> 
												</a>												                            
											</div>
											<?php } ;?>
											<?php } } } ;?>
											<!-- gallery-item end-->                       
										</div>
										<!-- portfolio end -->
										<?php } ;?>
									</div>
									<?php } ?>
									<!-- blog media end -->   
									<?php if (get_post_meta($post->ID,'rnr_video_column_grid_content_title_section',true)=='no'){ ?>
									<?php } else { ?>
									<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_content_title',true))):?>
									<div class="section-title fl-wrap">
										<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_content_title',true))):?>
										<h3><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_content_title',true)); ?></h3>
										<?php endif;?>
										<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_content_subtitle',true))):?>
										<h4><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_content_subtitle',true)); ?></h4>
										<?php endif;?>
										<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_content_number',true))):?>
										<div class="section-number"><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_content_number',true)); ?></div>
										<?php endif;?>
									</div>
									<?php endif;?>
									<?php } ?>										
									<span class="separator sep-b"></span>
									<div class="clearfix"></div>
									<p><?php the_content();?></p>
								</div>
								<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_project_info',true))=='yes'):?>
								<div class="caption-wrap fl-wrap ">
									<ul>
										<?php $kotlis_project_info = rwmb_meta( 'rnr_video_column_grid_project_info_main' );
											if ( ! empty( $kotlis_project_info ) ) {
											foreach ( $kotlis_project_info as $kotlis_project_infos ) { ;?>
											<?php $kotlis_video_column_grid_title = isset( $kotlis_project_infos['rnr_video_column_grid_dt_in_title'] ) ? $kotlis_project_infos['rnr_video_column_grid_dt_in_title'] : ''; ?>
											<?php $kotlis_video_column_grid_subtitle = isset( $kotlis_project_infos['rnr_video_column_grid_dt_in_subtitle'] ) ? $kotlis_project_infos['rnr_video_column_grid_dt_in_subtitle'] : ''; ?>
											<?php if ( !empty( $kotlis_video_column_grid_title ) ) { ?>
											<?php if ( !empty( $kotlis_video_column_grid_subtitle ) ) { ?>
											<li>
											   <span><?php echo esc_html($kotlis_video_column_grid_title);?></span>
											   <a href="#"><?php echo esc_html($kotlis_video_column_grid_subtitle);?></a> 
											</li>
										<?php } ?> <?php } ?> <?php } } ;?>	
									</ul>
								</div>
								<?php endif;?>	
								<?php if (( get_post_meta($post->ID,'rnr_video_column_grid_dt_in_button_text',true))):?>
								<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_video_column_grid_dt_in_button_url',true)); ?>" class="btn fl-btn" target="_blank"><?php echo esc_html(get_post_meta($post->ID,'rnr_video_column_grid_dt_in_button_text',true)); ?></a>
								<?php endif;?>								
							</div>
						</div>
						<!-- post end-->
					</div>
				</section>
				<?php get_template_part('template-parts/footer-copyrights');?>
			</div>
			<!-- column-wrapper -->	
		</div>
		<!--content end-->	
<?php endwhile;  endif; wp_reset_postdata(); ?>
<?php get_footer(); ?>