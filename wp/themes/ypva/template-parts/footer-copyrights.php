<?php $kotlis_options = get_option('kotlis'); ?>
		<footer class="min-footer fl-wrap content-animvisible">
			<div class="container small-container">
				<div class="footer-inner fl-wrap">
					<!-- policy-box-->
					<div class="policy-box">
						<?php $kotlis_copy = kotlis_AfterSetupTheme::return_thme_option('copyright');
								echo do_shortcode($kotlis_copy);
							?>
					</div>
					<!-- policy-box end-->
					<?php if (kotlis_AfterSetupTheme::return_thme_option('totop')!='no'){ ?>					   
					    <a href="#" class="to-top-btn to-top">
						<?php if (!empty($kotlis_options['to-top-title'])): ?>
						   <?php echo esc_html(($kotlis_options['to-top-title']));?>
						<?php else:?>
							<?php esc_html_e('Back to top ','kotlis');?>
						<?php endif;?>						   						
						<i class="fal fa-long-arrow-up"></i></a>						
					<?php };?>	
				</div>
			</div>
		</footer>