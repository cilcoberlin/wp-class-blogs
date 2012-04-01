<?php get_header(); ?>
<?php get_sidebar(); ?>

<div id="content">

	<?php
		bentham_show_archive_page_title(
			_x( 'Tag', 'noun', 'bentham' ),
			single_tag_title( '', false ) );
		get_template_part( 'loop', 'tag' );
	?>

</div>

<?php get_footer(); ?>
