<?php get_header(); ?>
<?php get_sidebar(); ?>

	<?php
		// If we have access to the class-blogs plugin's student post aggregation
		// functions, use the custom index page, or just show a post list
		if ( classblogging_student_posts_available() ) {
			$content_class = 'student-posts';
			$loop_type = 'students';
		} else {
			$content_class = 'main-posts';
			$loop_type = 'index';
		}
	?>

	<div id="content" class="<?php echo $content_class; ?>">
		<?php get_template_part( 'loop', $loop_type ); ?>
	</div>

<?php get_footer(); ?>
