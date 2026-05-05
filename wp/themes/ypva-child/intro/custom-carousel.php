		<!--content -->	
		<div class="content full-height  hidden-item no-mob-hidden">
			<!-- fw-carousel-wrap -->
			<div class="fw-carousel-wrap fsc-holder">
				<!-- fw-carousel  -->
				<?php if (get_post_meta($post->ID,'rnr_md_cus_car_slideshow_speed',true)):?>
				<?php $kotlis_slider_speed = get_post_meta($post->ID,'rnr_md_cus_car_slideshow_speed',true);?>
				<?php else: ?>
				<?php $kotlis_slider_speed = '1400';?>
				<?php endif;?>
				<div class="fw-carousel  fs-gallery-wrap fl-wrap full-height lightgallery" data-mousecontrol="true" data-slider-speed="<?php echo esc_attr($kotlis_slider_speed);?>" data-slider-autoplay="<?php echo esc_attr(get_post_meta($post->ID,'rnr_wr_cus_car_slideshow_autoplay',true));?>">
					<div class="swiper-container">
						<div class="swiper-wrapper">
							<!-- swiper-slide-->  
									<?php
									$kotlis_cus_car_main_opt = rwmb_meta( 'rnr_md_po_car_cus_gallery' );
									if ( ! empty( $kotlis_cus_car_main_opt ) ) {
									foreach ( $kotlis_cus_car_main_opt as $kotlis_cus_car_main_opts ) { ;?>
									<?php $kotlis_intro_title = isset( $kotlis_cus_car_main_opts['rnr_md_car_cus_gallery_intro_title_opt'] ) ? $kotlis_cus_car_main_opts['rnr_md_car_cus_gallery_intro_title_opt'] : ''; ?>
									<?php $kotlis_intro_subtitle = isset( $kotlis_cus_car_main_opts['rnr_md_car_cus_gallery_intro_sub_title_opt'] ) ? $kotlis_cus_car_main_opts['rnr_md_car_cus_gallery_intro_sub_title_opt'] : ''; ?>
									<?php $kotlis_intro_popup = isset( $kotlis_cus_car_main_opts['rnr_md_car_cus_intro_pop_video_opt'] ) ? $kotlis_cus_car_main_opts['rnr_md_car_cus_intro_pop_video_opt'] : ''; ?>
									<?php $kotlis_intro_buttonurl = isset( $kotlis_cus_car_main_opts['rnr_md_car_cus_intro_buttonurl_opt'] ) ? $kotlis_cus_car_main_opts['rnr_md_car_cus_intro_buttonurl_opt'] : ''; ?>
									<?php $kotlis_image_ids = isset( $kotlis_cus_car_main_opts['rnr_md_po_car_cus_gallery_img'] ) ? $kotlis_cus_car_main_opts['rnr_md_po_car_cus_gallery_img'] : array();
									foreach ( $kotlis_image_ids as $kotlis_image_id ) {
									$kotlis_image = RWMB_Image_Field::file_info( $kotlis_image_id, array( 'size' => '' ) ); ?>
							<div class="swiper-slide hov_zoom">
								<img  src="<?php echo esc_url(($kotlis_image['url']));?>"   alt="<?php echo esc_attr(($kotlis_image['title']));?>">
								<?php if ( !empty( $kotlis_intro_popup ) ) { ?>
								<a href="<?php echo esc_url($kotlis_intro_popup);?>" class="box-media-zoom   popup-image" <?php if(get_post_meta($post->ID,'rnr_custom_carousel_info_description_opt',true)=='st2'){ ?>data-sub-html="<h4><?php echo esc_attr(($kotlis_image['title']));?></h4><p><?php echo esc_attr(($kotlis_image['caption']));?></p>"<?php } ;?>><i class="fal fa-play"></i></a>
								<?php } else { ?>
								<a href="<?php echo esc_url(($kotlis_image['url']));?>" class="box-media-zoom   popup-image" <?php if (get_post_meta($post->ID,'rnr_custom_carousel_info_description_opt',true)=='st2'){ ?>data-sub-html="<h4><?php echo esc_attr(($kotlis_image['title']));?></h4><p><?php echo esc_attr(($kotlis_image['caption']));?></p>"<?php } ;?>><i class="fal fa-search"></i></a>
								<?php } ;?>
								<div class="thumb-info">
								<?php if ( !empty( $kotlis_intro_title ) ) { ?>
                                <h3><a href="<?php if ( !empty( $kotlis_intro_buttonurl ) ) { ?><?php echo esc_url($kotlis_intro_buttonurl);?><?php } else { ?>#<?php } ?>"><?php echo esc_html($kotlis_intro_title);?></a></h3>
								<?php } ?>
									
									<p><?php echo esc_html($kotlis_intro_subtitle);?></p>
								</div>
							</div>
							<!-- swiper-slide end-->  
							<?php } ?>
							<?php } } ;?>						
							
							<!-- swiper-slide-->  
							<?php if (get_post_meta($post->ID,'rnr_portfolio_cus_home_tcustom_button_url',true)):?>
							<div class="swiper-slide swiper-link-wrap hov_zoom">
								<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_portfolio_cus_home_tcustom_button_url',true));?>" class="swiper-link"><span><?php echo esc_html(get_post_meta($post->ID,'rnr_portfolio_cus_home_tcustom_button_text',true));?></span></a>
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
					<?php if (get_post_meta($post->ID,'rnr_portfolio_cus_home_scroll_swipe',true)=='no'){ ?>
					<?php } else { ?>	
					<div class="scroll-down-wrap">
						<div class="mousey">
							<div class="scroller"></div>
						</div>
						<span><?php if (get_post_meta($post->ID,'rnr_portfolio_cus_home_translet_opt3',true)):?><?php echo esc_html(get_post_meta($post->ID,'rnr_portfolio_cus_home_translet_opt3',true));?> <?php else : ?><?php esc_html_e('Scroll down or  Swipe','kotlis');?><?php endif;?></span>
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