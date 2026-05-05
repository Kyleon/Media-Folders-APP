<?php $kotlis_options = get_option('kotlis'); ?>
<?php
if ( post_password_required() ) {
	return;
}
?>
		
<?php
	
	if ( have_comments() ) : ?>
	<?php
	global $kotlis_comment_meta_text, $kotlis_comment_meta_text2, $kotlis_comment_meta_text3;
	if (!empty($kotlis_options['translet_opt_10'])):
	$kotlis_comment_meta_text= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_10',''));;
	else: 
	$kotlis_comment_meta_text='One comment on';
	endif;
	if(!empty($kotlis_options['translet_opt_11'])):
	$kotlis_comment_meta_text2= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_11',''));;
	else: 
	$kotlis_comment_meta_text2='Comment on';
	endif;
	if (!empty($kotlis_options['translet_opt_12'])):
	$kotlis_comment_meta_text3= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_12',''));;
	else: 
	$kotlis_comment_meta_text3='Comments on';
	endif;
	?>
		<h6 id="comments-title">
			<?php
			$comment_count = get_comments_number();
			if ( 1 === $comment_count ) {
				printf(
					
					esc_html_e( ''.$kotlis_comment_meta_text.' &ldquo;%1$s&rdquo;', 'kotlis' ),
					'<span>' . get_the_title() . '</span>'
				);
			} else {
				printf( 
					esc_html( _nx( '%1$s '.$kotlis_comment_meta_text2.' &ldquo;%2$s&rdquo;', '%1$s '.$kotlis_comment_meta_text3.' &ldquo;%2$s&rdquo;', $comment_count, 'comments title', 'kotlis' ) ),
					number_format_i18n( $comment_count ),
					'<span>' . get_the_title() . '</span>'
				);
			}
			?>
		</h6>		
		
		<!-- .comments-title -->

		<?php the_comments_navigation(); ?>
		
		<ul class="commentlist clearafix">
			
			<?php
				wp_list_comments( array(
					'callback'   => 'kotlis_comment',
					'short_ping' => true,
				) );
			?>
		</ul><!-- .comment-list -->
		<div class="clearfix"></div>
		
		<?php the_comments_navigation();
		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() ) : ?>
			<p class="no-comments"><?php if (!empty($kotlis_options['translet_opt_13'])):?><?php echo esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_13',''));?><?php else: ?><?php esc_html_e( 'Comments are closed.', 'kotlis' ); ?><?php endif;?></p>
		
		<?php
		endif;
	endif; // Check for have_comments().
	
	global $kotlis_comment_your_name, $kotlis_comment_your_email, $kotlis_comment_your_comment, $kotlis_comment_send_commen;
	if (!empty($kotlis_options['translet_opt_14'])):
	$kotlis_comment_your_name= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_14',''));;
	else: 
	$kotlis_comment_your_name='Your Name';
	endif;
	if (!empty($kotlis_options['translet_opt_15'])):
	$kotlis_comment_your_email= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_15',''));;
	else: 
	$kotlis_comment_your_email='Your Email';
	endif;
	if (!empty($kotlis_options['translet_opt_16'])):
	$kotlis_comment_your_comment= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_16',''));;
	else: 
	$kotlis_comment_your_comment='Your Comment';
	endif;
	if (!empty($kotlis_options['translet_opt_17'])):
	$kotlis_comment_send_comment= esc_html(kotlis_AfterSetupTheme::return_thme_option('translet_opt_17',''));;
	else: 
	$kotlis_comment_send_comment='Add Comment';
	endif;
	
		 $kotlis_args = array(
		'fields' => apply_filters(
		'comment_form_default_fields', array(
			
			'author' =>'<div class="row"><div class="col-md-6">' . '<input id="author"  placeholder="'. $kotlis_comment_your_name .'*" name="author" type="text" value="' .
				esc_attr( $commenter['comment_author'] ) . '" size="40"/>'.
				
				'</div>'
				,
			'email'  => '<div class="col-md-6">' . '<input id="email" placeholder="'.$kotlis_comment_your_email.'*" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) .
				'" size="40"/>'  .
				
				'</div></div>',
			
		)
		),
		'comment_field' => '' .
		'<textarea id="comment" class="form-control" name="comment" cols="40" rows="3" placeholder="'.$kotlis_comment_your_comment.'*" aria-required="true"></textarea>' .
		'',
		'comment_notes_after' => '<button class="btn float-btn flat-btn color-bg">'.$kotlis_comment_send_comment.'</button>',
		'title_reply' => '<div class="comment-title-area crunchify-text"> <h3> <span>' . esc_html__( 'Leave a Reply', 'kotlis' ) . '</span>'.'</h3></div>'
		
	);
	comment_form($kotlis_args);
	?>
	



