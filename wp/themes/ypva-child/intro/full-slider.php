		<!--content -->	
		<div class="content full-height">
			<!--home-main_container-->	
			<div class="home-main_container">
				<!--follow-wrap-->
				<?php if (get_post_meta($post->ID,'rnr_wr_intro_full_slider_social_dis',true)=='st2'){ ?>
				<div class="follow-wrap">
					<div class="follow-wrap_title">
						<span>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_full_slider_sc_title',true))):?>
						<?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_full_slider_sc_title',true)); ?>
						<?php else : ?>
						<?php esc_html_e('Follow','kotlis');?>
						<?php endif;?>
						</span> <i class="fal fa-arrow-right"></i>
					</div>
					<div class="clearfix"></div>
					<ul>						
						<?php $kotlis_social_con = rwmb_meta( 'rnr_md_full_slider_social_title_opt' );
							if ( ! empty( $kotlis_social_con ) ) {
							foreach ( $kotlis_social_con as $kotlis_social_cons ) { ;?>
							<?php $kotlis_intro_text_social = isset( $kotlis_social_cons['rnr_md_full_slider_social_title'] ) ? $kotlis_social_cons['rnr_md_full_slider_social_title'] : ''; ?>
							<?php $kotlis_intro_url_social = isset( $kotlis_social_cons['rnr_md_full_slider_social_title_url'] ) ? $kotlis_social_cons['rnr_md_full_slider_social_title_url'] : ''; ?>
							<?php if ( !empty( $kotlis_intro_text_social ) ) { ?>
							<?php if ( !empty( $kotlis_intro_url_social ) ) { ?>
							<li><a href="<?php echo esc_url($kotlis_intro_url_social);?>" target="_blank"><i class="<?php echo esc_attr($kotlis_intro_text_social);?>"></i></a> </li>
						<?php } ?> <?php } ?> <?php } } ;?>						
					</ul>
				</div>
			    <?php } else { ?>
				<?php } ;?>
				<!--follow-wrap end-->
				<div class="full-height fl-wrap hidden-item">
					<!--fs-slider-wrap-->	
					<div class="fs-slider-wrap full-height fl-wrap">
						<div class="fs-slider lightgallery full-height fl-wrap" data-mousecontrol2="true">
						<?php if (get_post_meta($post->ID,'rnr_md_full_slideshow_speed',true)):?>
						<?php $kotlis_slider_speed = get_post_meta($post->ID,'rnr_md_full_slideshow_speed',true);?>
						<?php else: ?>
						<?php $kotlis_slider_speed = '2500';?>
						<?php endif;?>
							<div class="swiper-container" data-slider-speed="<?php echo esc_attr($kotlis_slider_speed);?>">
								<div class="swiper-wrapper" >
									<?php
									$kotlis_full_slide_text_opt = rwmb_meta( 'rnr_md_po_full_gallery' );
									if ( ! empty( $kotlis_full_slide_text_opt ) ) {
									foreach ( $kotlis_full_slide_text_opt as $kotlis_full_slide_text_opts ) { ;?>
									
									<?php $kotlis_intro_title = isset( $kotlis_full_slide_text_opts['rnr_md_gallery_full_intro_title_opt'] ) ? $kotlis_full_slide_text_opts['rnr_md_gallery_full_intro_title_opt'] : ''; ?>
									
									<?php $kotlis_intro_subtitle = isset( $kotlis_full_slide_text_opts['rnr_md_gallery_full_intro_sub_title_opt'] ) ? $kotlis_full_slide_text_opts['rnr_md_gallery_full_intro_sub_title_opt'] : ''; ?>
									
									<?php $kotlis_intro_buttontxt = isset( $kotlis_full_slide_text_opts['rnr_md_intro_full_buttontxt_opt'] ) ? $kotlis_full_slide_text_opts['rnr_md_intro_full_buttontxt_opt'] : ''; ?>
									
									<?php $kotlis_intro_buttonurl = isset( $kotlis_full_slide_text_opts['rnr_md_intro_full_buttonurl_opt'] ) ? $kotlis_full_slide_text_opts['rnr_md_intro_full_buttonurl_opt'] : ''; ?>
									
									<?php $kotlis_image_ids = isset( $kotlis_full_slide_text_opts['rnr_md_po_full_gallery_img'] ) ? $kotlis_full_slide_text_opts['rnr_md_po_full_gallery_img'] : array();
									foreach ( $kotlis_image_ids as $kotlis_image_id ) {
									$kotlis_image = RWMB_Image_Field::file_info( $kotlis_image_id, array( 'size' => '' ) ); ?>
									<!-- swiper-slide-->
									<div class="swiper-slide hov_zoom">
										<div class="fs-slider-item fl-wrap">
											<div class="bg"  data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
											<div class="overlay"></div>
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
												<a href="<?php echo esc_url($kotlis_intro_buttonurl);?>" class="btn fl-btn"><?php echo esc_html($kotlis_intro_buttontxt);?></a>
												<?php } ?>
												<?php } ?>
											</div>
											<a href="<?php echo esc_url(($kotlis_image['url']));?>" class="box-media-zoom   popup-image" <?php if(get_post_meta($post->ID,'rnr_custom_opt_slider_info_description_opt',true)=='st2'){ ?>data-sub-html="<h4><?php echo esc_attr(($kotlis_image['title']));?></h4><p><?php echo esc_attr(($kotlis_image['caption']));?></p>"<?php } ;?>><i class="fal fa-search"></i></a>
										</div>
									</div>
									<!-- swiper-slide-->
									<?php } ?>
									<?php } } ;?> 
								</div>
							</div>
						</div>
					</div>
					<!-- fs-slider-wrap end-->
					<div class="ss-slider-cont hero-slider-button-prev ss-slider-cont-prev"><i class="fal fa-long-arrow-left"></i></div>
					<div class="ss-slider-cont hero-slider-button-next ss-slider-cont-next"><i class="fal fa-long-arrow-right"></i></div>					
					<div class="hero-slider-wrap_pagination hlaf-slider-pag hlaf-slider-pag_fs"></div>
					<!-- home-slider-counter-wrap-->
					<div class="home-slider-counter-wrap">
						<div class="swiper-counter">
							<div id="current"><?php esc_html_e('1','kotlis');?></div>
							<div id="total"></div>
						</div>
					</div>
					<!-- home-slider-counter-wrap end-->
				</div>
			</div>
			<!--home-main_container end-->
		</div>
		<!--content end-->	