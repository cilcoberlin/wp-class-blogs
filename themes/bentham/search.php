<?php get_header(); ?>
<?php get_sidebar(); ?>

<?php /* Show a list of search results or a no-posts-found message */ ?>
<div id="content">
	<?php
		if ( have_posts() ) {
			bentham_show_archive_page_title(
				_x( 'Search Results', 'search results page title', 'bentham' ),
				get_search_query() );
			get_template_part( 'loop', 'search' );
		} else {
			bentham_show_no_posts_message();
		}
	?>
</div>

<?php get_footer(); ?>
