
<?php classblogging_show_navigation( 'above' ); ?>

<?php
	// Display a page-not-found message if no posts are found
	if ( ! have_posts() ) {
		classblogging_show_no_posts_message();
	}
?>

<?php /* Display any posts found as a list */ ?>
<?php while ( have_posts() ) : the_post(); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<div class="post-info">
			<?php
				classblogging_show_edit_link();
				classblogging_show_author_and_date();
			 ?>
			<h2 class="title">
				<a href="<?php the_permalink(); ?>" rel="bookmark">
					<?php
						$post_title = get_the_title();
						if ( $post_title ) {
							echo $post_title;
						} else {
							printf( '( %s )', __( 'Unnamed Post', 'classblogging' ) );
						}
					?>
				</a>
			</h2>
		</div>

		<div class="content">
			<?php the_excerpt(); ?>
		</div>

		<div class="interaction">
			<span class="comments">
				<?php comments_popup_link( __( 'Leave a comment', 'classblogging' ), __( '1 Comment', 'classblogging' ), __( '% Comments', 'classblogging' ) ); ?>
			</span>
		</div>

		<?php classblogging_show_taxonomy(); ?>

	</div>

<?php endwhile; ?>

<?php classblogging_show_navigation( 'below' ); ?>
