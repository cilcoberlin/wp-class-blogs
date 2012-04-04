<?php get_header(); ?>
<?php get_sidebar(); ?>

<div id="content">

	<?php
		classblogging_show_archive_page_title(
			_x( 'Tag', 'noun', 'classblogging' ),
			single_tag_title( '', false ) );
		get_template_part( 'loop', 'tag' );
	?>

</div>

<?php get_footer(); ?>
