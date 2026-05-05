		<!--content -->	
		<div class="content full-height">
			<!-- media-container  -->
			<div class="media-container">
				<!--fs-slider-wrap -->						
				<div class="fs-slider-wrap fs-slider-det full-height fl-wrap">
					<div class="fs-slider lightgallery full-height fl-wrap" data-mousecontrol2="true">
						<?php if (get_post_meta($post->ID,'rnr_md_full_details_slideshow_speed',true)):?>
						<?php $kotlis_slider_speed = get_post_meta($post->ID,'rnr_md_full_details_slideshow_speed',true);?>
						<?php else: ?>
						<?php $kotlis_slider_speed = '2500';?>
						<?php endif;?>
						<div class="swiper-container" data-slider-speed="<?php echo esc_attr($kotlis_slider_speed);?>">
							<div class="swiper-wrapper" >
								<!-- swiper-slide-->
								<?php
								$kotlis_half_slide_text_opt = rwmb_meta( 'rnr_md_po_gallery' );
								if ( ! empty( $kotlis_half_slide_text_opt ) ) {
								foreach ( $kotlis_half_slide_text_opt as $kotlis_half_slide_text_opts ) { ;?>
								
								<?php $kotlis_intro_title = isset( $kotlis_half_slide_text_opts['rnr_md_gallery_intro_title_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_gallery_intro_title_opt'] : ''; ?>
								
								<?php $kotlis_intro_subtitle = isset( $kotlis_half_slide_text_opts['rnr_md_gallery_intro_sub_title_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_gallery_intro_sub_title_opt'] : ''; ?>
								
								<?php $kotlis_intro_video = isset( $kotlis_half_slide_text_opts['rnr_md_intro_poup_vid_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_poup_vid_opt'] : ''; ?>
								
								<?php $kotlis_intro_buttontxt = isset( $kotlis_half_slide_text_opts['rnr_md_intro_buttontxt_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_buttontxt_opt'] : ''; ?>
								
								<?php $kotlis_intro_buttonurl = isset( $kotlis_half_slide_text_opts['rnr_md_intro_buttonurl_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_buttonurl_opt'] : ''; ?>
								
								<?php $kotlis_intro_btn_target = isset( $kotlis_half_slide_text_opts['rnr_custom_dt_slider_info_target_opt'] ) ? $kotlis_half_slide_text_opts['rnr_custom_dt_slider_info_target_opt'] : ''; ?>	
								<?php $kotlis_intro_rotatetext = isset( $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt'] : ''; ?>	
								
								<?php $kotlis_intro_rotatetext2 = isset( $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt2'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt2'] : ''; ?>	
								
								<?php $kotlis_intro_rotatetext3 = isset( $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt3'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt3'] : ''; ?>
								
								<?php $kotlis_intro_rotatetext4 = isset( $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt4'] ) ? $kotlis_half_slide_text_opts['rnr_md_intro_rotate_opt4'] : ''; ?>	

								<?php $kotlis_image_ids = isset( $kotlis_half_slide_text_opts['rnr_md_po_half_gallery_img'] ) ? $kotlis_half_slide_text_opts['rnr_md_po_half_gallery_img'] : array();
								foreach ( $kotlis_image_ids as $kotlis_image_id ) {
								$kotlis_image = RWMB_Image_Field::file_info( $kotlis_image_id, array( 'size' => '' ) ); ?>
								
								<div class="swiper-slide hov_zoom" data-fsslideropt1="<?php echo balanceTags($kotlis_intro_rotatetext);?>" data-fsslideropt2="<?php echo balanceTags($kotlis_intro_rotatetext2);?>" data-fsslideropt3="<?php echo balanceTags($kotlis_intro_rotatetext3);?>" data-fssurl="<?php echo esc_url($kotlis_intro_rotatetext4);?>">
									<div class="fs-slider-item fl-wrap">
										<div class="bg"  data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
										<!--div class="overlay"></div-->
										<div class="fs-slider_align_title">
										    <?php if ( !empty( $kotlis_intro_title ) ) { ?>
											<h2><?php echo esc_html($kotlis_intro_title);?></h2>
											<?php } ?>
											<?php if ( !empty( $kotlis_intro_subtitle ) ) { ?>
											<p><?php echo esc_html($kotlis_intro_subtitle);?> </p>
											<?php } ?>
											<div class="clearfix"></div>
											<?php if ( !empty( $kotlis_intro_buttontxt ) ) { ?>
											<?php if ( !empty( $kotlis_intro_buttonurl ) ) { ?>
											<a href="<?php echo esc_url($kotlis_intro_buttonurl);?>" class="btn fl-btn" target="<?php echo esc_attr($kotlis_intro_btn_target);?>"><?php echo esc_html($kotlis_intro_buttontxt);?></a>
											<?php } ?>
											<?php } ?>
										</div>
										<?php if ( !empty( $kotlis_intro_video ) ) { ?>
										<a href="<?php echo esc_url($kotlis_intro_video);?>" class="box-media-zoom   popup-image" ><i class="fal fa-play"></i></a>
										<?php } else { ?>
										<a href="<?php echo esc_url(($kotlis_image['url']));?>" class="box-media-zoom   popup-image" <?php if (get_post_meta($post->ID,'rnr_custom_dt_slider_info_description_opt',true)=='st2'){ ?>data-sub-html="<h4><?php echo esc_attr(($kotlis_image['title']));?></h4><p><?php echo esc_attr(($kotlis_image['caption']));?></p>"<?php } ;?>><i class="fal fa-search"></i></a>
										<?php } ;?>
									</div>
								</div>
								<!-- swiper-slide-->
								<?php } ?>
								<?php } } ;?>
							</div>
						</div>
					</div>
				</div>
				<!--fs-slider-wrap end -->	
				<div class="hero-slider-wrap_pagination hlaf-slider-pag"></div>
				<!-- hero-slider_details_wrap--> 
				<div class="hero-slider_details_wrap">
					<div class="hero-slider_details fl-wrap">
						<ul>
							<li class="opt-one"></li>
							<li class="opt-two"></li>
							<li class="opt-three"></li>
						</ul>
					</div>
					<a href="" class="hero-slider_details_url ajax"><i class="fal fa-chevron-right"></i></a>
				</div>
				<!-- hero-slider_details_wrap  end --> 
				<!-- hero-slider_control-wrap--> 
				<div class="hero-slider_control-wrap dark-gradient">
				    <?php if (get_post_meta($post->ID,'rnr_wr_intro_slide_scroll_swipe',true)=='st2'){ ?>
					<div class="scroll-down-wrap transparent_sdw">
						<div class="mousey">
							<div class="scroller"></div>
						</div>
						<span>
						<?php if (( get_post_meta($post->ID,'rnr_home_slide_scroll_swipe_title',true))):?>
						    <?php echo esc_html(get_post_meta($post->ID,'rnr_home_slide_scroll_swipe_title',true)); ?>
						<?php else : ?>	
							<?php esc_html_e('Scroll down or  Swipe','kotlis');?>
						<?php endif;?>	
						</span>
					</div>
					<?php } ?>
					<div class="hero-slider_control_item">
						<div class="hero-slider_control hero-slider-button-prev"><i class="fal fa-angle-left"></i></div>
						<div class="hero-slider_control hero-slider-button-next"><i class="fal fa-angle-right"></i></div>
					</div>
				</div>
				<!-- hero-slider_control-wrap end--> 
			</div>
			<!-- media-container   end -->         
			<!-- slider-counter_wrap -->   
			<div class="slider-counter_wrap">
				<div class="swiper-counter">
					<div id="current"><?php esc_html_e('1','kotlis');?></div>
					<div id="total"></div>
				</div>
			</div>
			<!-- slider-counter_wrap   end -->   
		</div>
		<!--content end-->	