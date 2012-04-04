<?php
	get_header();
	get_sidebar();

	echo '<div id="content">';
		classblogging_show_no_posts_message();
	echo '</div>';

	get_footer();
?>
