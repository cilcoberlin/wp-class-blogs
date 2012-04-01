
<?php
	if ( have_posts() ):
		while ( have_posts() ):
			the_post();
?>

	<?php bentham_show_navigation( 'above' ); ?>

	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php /* Show the title with the possible page number */ ?>
		<div class="post-info">
			<?php
				bentham_show_edit_link();
				if ( ! is_page() ) {
					bentham_show_author_and_date();
				}
			?>
			<h1 class="title">
				<?php

					global $page, $paged;
					$current_page = max( $page, $paged );

					the_title();
					if ( $current_page > 1 ) {
						echo ' &ndash; ';
						printf( __( 'Page %d', 'bentham' ), $current_page );
					}
				?>
			</h1>
		</div>

		<div class="content">
			<?php the_content(); ?>
			<?php wp_link_pages( 'before=<div class="page-links"><strong>' . _x( 'Pages', 'single post page list header', 'bentham' ) . '</strong> &after=</div>' ); ?>
		</div>

		<?php
			if ( ! post_password_required() ) {
				bentham_show_taxonomy();
			}
		?>
	</div>

	<?php bentham_show_navigation( 'above' ); ?>

	<?php comments_template( '', true ); ?>

<?php
		endwhile;
	endif;
?>

<div id="page-push"></div>
