<?php $kotlis_options = get_option('kotlis'); ?>
<?php get_header();?>
		<!--content -->	
		<div class="content full-height">
			<!--home-main_container-->	
			<div class="home-main_container error-page">
				<!--home-main_title-->
				<div class="home-main_title">
					<div class="home-main_title_item">
					    <h4>
					    <?php if (!empty($kotlis_options['error-page-sbtitle'])): ?>
							<?php echo esc_html(($kotlis_options['error-page-sbtitle']));?>
						<?php else:?>
							<?php esc_html_e('Error','kotlis');?>
						<?php endif;?>
						</h4>
						<h2>
						<?php if (!empty($kotlis_options['error-page-title'])): ?>
						    <?php echo esc_html(($kotlis_options['error-page-title']));?>
						<?php else:?>
						    <?php esc_html_e('404','kotlis');?>
						<?php endif;?>
                        </h2>
						<p>
						<?php if (!empty($kotlis_options['error-page-subtitle'])): ?>      
							<?php echo esc_html(($kotlis_options['error-page-subtitle']));?>
						<?php else:?>   
							<?php esc_html_e('The page you were looking for, could not be found.','kotlis');?>	
						<?php endif;?>	
						</p>

					</div>
				</div>
				<!--home-main_title end-->
				<div class="slider-mask"></div>
				<!--bg image-->
				<div class="bg" data-bg="<?php echo esc_url(kotlis_AfterSetupTheme::return_thme_option('errorpic','url'));?>"></div>
				<div class="overlay custom-overlay"></div>
				<!--bg image end-->
			</div>
			<!--home-main_container end-->	
		</div>
		<!--content end-->	
<?php get_footer(); ?>	