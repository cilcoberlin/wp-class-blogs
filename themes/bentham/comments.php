
<?php if ( comments_open() || have_comments() ): ?>

<div id="comments">

	<?php /* Prevent unauthorized access to the comments on a protected post */ ?>
	<?php if ( post_password_required() ) { ?>
		<p class="protected-warning"><?php _e( 'This post is password protected. Enter the password to view any comments.', 'bentham' ); ?></p>
		</div>
	<?php return; } ?>

	<?php /* Display the verbose number of comments */ ?>
	<?php if ( get_comments_number() && comments_open() ): ?>
	<h3 class="comments-header">
		<?php
			printf( _n( 'One Comment', '%1$s Comments', get_comments_number(), 'bentham' ),
				number_format_i18n( get_comments_number() ) );
		?>
	</h3>
	<?php endif; ?>

	<?php /* Show a list of comments if any have been left*/ ?>
	<?php if ( have_comments() ): ?>

		<?php bentham_show_comment_navigation(); ?>

		<ol class="comment-list">
			<?php
				wp_list_comments( array(
					'avatar_size' => 54,
					'reply_text'  => _x( 'Reply', 'comment reply link', 'bentham' ) ) );
			?>
		</ol>

		<?php bentham_show_comment_navigation(); ?>

	<?php endif; ?>

	<?php /* Show the comments form if comments are open on the post */ ?>
	<?php
		if ( comments_open() ) {
			comment_form();
		}
	?>

</div>

<?php endif; ?>
