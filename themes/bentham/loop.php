
<?php bentham_show_navigation( 'above' ); ?>

<?php
	// Display a page-not-found message if no posts are found
	if ( ! have_posts() ) {
		bentham_show_no_posts_message();
	}
?>

<?php /* Display any posts found as a list */ ?>
<?php while ( have_posts() ) : the_post(); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<h2 class="title">
			<a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
			<?php bentham_show_edit_link(); ?>
		</h2>

		<?php bentham_show_author_and_date(); ?>

		<div class="content">
			<?php the_excerpt(); ?>
		</div>

		<div class="interaction">
			<span class="comments">
				<?php comments_popup_link( __( 'Leave a comment', 'bentham' ), __( '1 Comment', 'bentham' ), __( '% Comments', 'bentham' ) ); ?>
			</span>
		</div>

		<?php bentham_show_taxonomy(); ?>

	</div>

<?php endwhile; ?>

<?php bentham_show_navigation( 'below' ); ?>
