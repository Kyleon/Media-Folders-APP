<?php $kotlis_options = get_option('kotlis'); ?>
		<!--share-wrapper-->
		<div class="share-wrapper">
			<div class="share-container fl-wrap  isShare"></div>
		</div>
		<!--share-wrapper end-->
	</div>
	<!-- wrapper end -->
	<!-- sidebar -->
	<div class="sb-overlay"></div>
	<?php if ( is_active_sidebar( 'sidebar-3' ) ) : ?>
	<div class="hiiden-sidebar-wrap outsb">
		<?php dynamic_sidebar( 'sidebar-3' ); ?>  
	</div>
	<?php endif; ?>
	<!-- sidebar end -->
	<!-- cursor-->
	<div class="element">
		<div class="element-item"></div>
	</div>
	<!-- cursor end-->     
</div>
<!-- Main end -->

<?php wp_footer(); ?>
</body>
</html>