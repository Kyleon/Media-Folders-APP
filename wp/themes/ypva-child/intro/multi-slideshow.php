		<!--content -->	
		<div class="content full-height">
			<!--home-main_container-->	
			<div class="home-main_container">
				<!--follow-wrap-->
				<?php if (get_post_meta($post->ID,'rnr_wr_intro_multi_slideshow_social_dis',true)=='st2'){ ?>
				<div class="follow-wrap">
					<div class="follow-wrap_title">
						<span>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_sc_title',true))):?>
						<?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_sc_title',true)); ?>
						<?php else : ?>
						<?php esc_html_e('Follow','kotlis');?>
						<?php endif;?>
						</span> <i class="fal fa-arrow-right"></i>
					</div>
					<div class="clearfix"></div>
					<ul>						
						<?php $kotlis_social_con = rwmb_meta( 'rnr_md_multi_slideshow_social_title_opt' );
							if ( ! empty( $kotlis_social_con ) ) {
							foreach ( $kotlis_social_con as $kotlis_social_cons ) { ;?>
							<?php $kotlis_intro_text_social = isset( $kotlis_social_cons['rnr_md_multi_slideshow_social_title'] ) ? $kotlis_social_cons['rnr_md_multi_slideshow_social_title'] : ''; ?>
							<?php $kotlis_intro_url_social = isset( $kotlis_social_cons['rnr_md_multi_slideshow_social_title_url'] ) ? $kotlis_social_cons['rnr_md_multi_slideshow_social_title_url'] : ''; ?>
							<?php if ( !empty( $kotlis_intro_text_social ) ) { ?>
							<?php if ( !empty( $kotlis_intro_url_social ) ) { ?>
							<li><a href="<?php echo esc_url($kotlis_intro_url_social);?>" target="_blank"><i class="<?php echo esc_attr($kotlis_intro_text_social);?>"></i></a> </li>
						<?php } ?> <?php } ?> <?php } } ;?>						
					</ul>
				</div>
			    <?php } else { ?>
				<?php } ;?>
				<!--follow-wrap end-->
				<!--home-main_title-->
				<div class="home-main_title">
					<div class="home-main_title_item">
					    <?php if (( get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_subtitle',true))):?>
						<h4><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_subtitle',true)); ?></h4>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_title',true))):?>
						<h2><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_title',true)); ?></h2>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_content',true))):?>
						<p><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_multi_slideshow_content',true)); ?> </p>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_intro_opt_multi_slideshow_custom_button_text',true))):?>
						<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_bl_intro_opt_multi_slideshow_custom_button_url',true)); ?>" class="btn fl-btn"><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_intro_opt_multi_slideshow_custom_button_text',true)); ?></a>
						<?php endif;?>
					</div>
				</div>
				<div class="slide-progress-wrap"><div class="slide-progress"></div></div>
				<!--home-main_title end-->
				<div class="slider-mask"></div>
				<!--multi-slideshow-wrap_1 -->
				<div class="multi-slideshow-wrap_1 ms-wrap">
					<div class="full-height fl-wrap">
						<!--ms-container-->
						<?php if (get_post_meta($post->ID,'rnr_md_multi_slideshow_speed',true)):?>
						<?php $kotlis_slider_speed = get_post_meta($post->ID,'rnr_md_multi_slideshow_speed',true);?>
						<?php else: ?>
						<?php $kotlis_slider_speed = '3500';?>
						<?php endif;?>
						<div class="multi-slideshow_1 ms-container fl-wrap full-height">
							<div class="swiper-container" data-slider-speed="<?php echo esc_attr($kotlis_slider_speed);?>">
								<div class="swiper-wrapper">
									<?php $kotlis_images = rwmb_meta( 'rnr_bl_multi_slide_1','type=image&size=' );
									foreach ( $kotlis_images as $kotlis_image ){ ?>
									<!--ms_item-->
									<div class="swiper-slide">
										<div class="ms_item fl-wrap kenburns ">
											<div class="bg" data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
											<!--div class="overlay"></div-->
										</div>
									</div>
									<!--ms_item end-->
									<?php } ;?> 									                                               
								</div>
							</div>
						</div>
						<!--ms-container end-->
					</div>
				</div>
				<!--multi-slideshow-wrap_1 end-->
				<!--multi-slideshow-wrap_2-->
				<div class="multi-slideshow-wrap_2">
					<div class="full-height fl-wrap">
						<!--ms-container-->
						<div class="multi-slideshow_2 ms-container fl-wrap full-height">
							<div class="swiper-container">
								<div class="swiper-wrapper">
									<?php $kotlis_images = rwmb_meta( 'rnr_bl_multi_slide_2','type=image&size=' );
									foreach ( $kotlis_images as $kotlis_image ){ ?>
									<!--ms_item-->
									<div class="swiper-slide">
										<div class="ms_item2 fl-wrap kenburns ">
											<div class="bg" data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
											<div class="overlay"></div>
										</div>
									</div>
									<!--ms_item end-->
									<?php } ;?>								                                               
								</div>
							</div>
						</div>
						<!--ms-container end-->                
					</div>
				</div>
				<!--multi-slideshow-wrap_2 end-->
				<!--multi-slideshow-wrap_3-->
				<div class="multi-slideshow-wrap_3">
					<div class="full-height fl-wrap">
						<!--ms-container-->
						<div class="multi-slideshow_3 ms-container fl-wrap full-height">
						
							<div class="swiper-container" dir="rtl">
								<div class="swiper-wrapper">
									<?php $kotlis_images = rwmb_meta( 'rnr_bl_multi_slide_3','type=image&size=' );
									foreach ( $kotlis_images as $kotlis_image ){ ?>
									<!--ms_item-->
									<div class="swiper-slide">
										<div class="ms_item3 fl-wrap kenburns ">
											<div class="bg" data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
											<div class="overlay"></div>
										</div>
									</div>
									<!--ms_item end-->
									<?php } ;?>									`                                                
								</div>
							</div>
						</div>
						<!--ms-container end-->   
					</div>
				</div>
				<!--multi-slideshow-wrap_3 end-->
			</div>
			<!--home-main_container end-->	
		</div>
		<!--content end-->	