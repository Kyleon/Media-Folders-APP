		<!--content -->	
		<div class="content full-height">
			<!--home-main_container-->	
			<div class="home-main_container">
				<!--follow-wrap-->
				<?php if (get_post_meta($post->ID,'rnr_wr_intro_fullscreen_image_social_dis',true)=='st2'){ ?>
				<div class="follow-wrap">
					<div class="follow-wrap_title">
						<span>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_sc_title',true))):?>
						<?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_sc_title',true)); ?>
						<?php else : ?>
						<?php esc_html_e('Follow','kotlis');?>
						<?php endif;?>
						</span> <i class="fal fa-arrow-right"></i>
					</div>
					<div class="clearfix"></div>
					<ul>						
						<?php $kotlis_social_con = rwmb_meta( 'rnr_md_fullscreen_image_social_title_opt' );
							if ( ! empty( $kotlis_social_con ) ) {
							foreach ( $kotlis_social_con as $kotlis_social_cons ) { ;?>
							<?php $kotlis_intro_text_social = isset( $kotlis_social_cons['rnr_md_fullscreen_image_social_title'] ) ? $kotlis_social_cons['rnr_md_fullscreen_image_social_title'] : ''; ?>
							<?php $kotlis_intro_url_social = isset( $kotlis_social_cons['rnr_md_fullscreen_image_social_title_url'] ) ? $kotlis_social_cons['rnr_md_fullscreen_image_social_title_url'] : ''; ?>
							<?php if ( !empty( $kotlis_intro_text_social ) ) { ?>
							<?php if ( !empty( $kotlis_intro_url_social ) ) { ?>
							<li><a href="<?php echo esc_url($kotlis_intro_url_social);?>" target="_blank"><i class="<?php echo esc_attr($kotlis_intro_text_social);?>"></i></a> </li>
						<?php } ?> <?php } ?> <?php } } ;?>						
					</ul>
				</div>
			    <?php } else { ?>
				<?php } ;?>					
				<!--follow-wrap end-->
				<!--hero-decor-let-->   
				<?php if (get_post_meta($post->ID,'rnr_wr_intro_fullscreen_image_right_dis',true)=='st2'){ ?>
				<div class="hero-decor-let">
				    <?php
					$kotlis_right_con = rwmb_meta( 'rnr_md_fullscreen_image_right_side_title_opt' );
					if ( ! empty( $kotlis_right_con ) ) {
					foreach ( $kotlis_right_con as $kotlis_right_cons ) { ;?>					
					<?php $kotlis_intro_text_right = isset( $kotlis_right_cons['rnr_md_fullscreen_image_right_side_title'] ) ? $kotlis_right_cons['rnr_md_fullscreen_image_right_side_title'] : ''; ?>
					<?php $kotlis_intro_url_right = isset( $kotlis_right_cons['rnr_md_fullscreen_image_right_side_title_url'] ) ? $kotlis_right_cons['rnr_md_fullscreen_image_right_side_title_url'] : ''; ?>
					<?php if ( !empty( $kotlis_intro_text_right ) ) { ?>
					<?php if ( !empty( $kotlis_intro_url_right ) ) { ?>
					<div><?php echo esc_html($kotlis_intro_url_right);?> <span><?php echo esc_html($kotlis_intro_text_right);?></span></div>
				   <?php } ?><?php } ?> <?php } } ;?>
				</div>
			    <?php } else { ?>
				<?php } ;?>				
				<!--hero-decor-let end-->
				<!--home-main_title-->
				<div class="home-main_title">
					<div class="home-main_title_item">
					    <?php if (( get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_subtitle',true))):?>
						<h4><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_subtitle',true)); ?></h4>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_title',true))):?>
						<h2><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_title',true)); ?></h2>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_content',true))):?>
						<p><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_home_fullscreen_image_content',true)); ?> </p>
						<?php endif;?>
						<?php if (( get_post_meta($post->ID,'rnr_bl_intro_opt_fullscreen_image_custom_button_text',true))):?>
						<a href="<?php echo esc_url(get_post_meta($post->ID,'rnr_bl_intro_opt_fullscreen_image_custom_button_url',true)); ?>" class="btn fl-btn"><?php echo esc_html(get_post_meta($post->ID,'rnr_bl_intro_opt_fullscreen_image_custom_button_text',true)); ?></a>
						<?php endif;?>
					</div>
				</div>
				<!--home-main_title end-->
				<div class="slider-mask"></div>
				<!--bg image-->
				<?php $kotlis_images = rwmb_meta( 'rnr_bl_fullscreen_image_slide','type=image&size=' );
					foreach ( $kotlis_images as $kotlis_image ){ ?>
				<div class="bg" data-bg="<?php echo esc_url(($kotlis_image['url']));?>"></div>
				<?php } ;?>
				<div class="overlay"></div>
				<!--bg image end-->
			</div>
			<!--home-main_container end-->	
		</div>
		<!--content end-->	