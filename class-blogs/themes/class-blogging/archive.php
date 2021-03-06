<?php get_header(); ?>
<?php get_sidebar(); ?>

<div id="content">

	<?php

		// Get a a post so that we can determine which archive we're viewing
		if ( have_posts() ) {
			the_post();
		}

		// Display a title based on the archive type
		if ( is_day() ) {
			$title = __( 'Day', 'classblogging' );
			$filter = get_the_date();
		} elseif ( is_month() ) {
			$title = __( 'Month', 'classblogging' );
			$filter = get_the_date( 'F Y' );
		} elseif ( is_year() ) {
			$title = __( 'Year', 'classblogging' );
			$filter = get_the_date( 'Y' );
		} elseif ( is_author() ) {
			$title = __( 'Author', 'classblogging' );
			$filter = get_the_author();
		} else {
			$title = __( 'Archives', 'classblogging' );
			$filter = "";
		}
		classblogging_show_archive_page_title( $title, $filter );

		// Display the loop, rewinding our posts so that it can function properly
		rewind_posts();
		get_template_part( 'loop', 'archive' );
	?>

</div>

<?php get_footer(); ?>
