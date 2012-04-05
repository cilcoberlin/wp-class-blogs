<?php

// Since this theme manually gets a list of sitewide posts if any are available,
// we want to prevent the sitewide posts code from executing, as, if sitewide
// data isn't available, we won't be missing out on its normal overriding of
// the standard front-page post list
define( 'CLASS_BLOGS_SHOW_SITEWIDE_POSTS_ON_FRONT_PAGE', false);

/**
 * Performs setup functions for the theme
 *
 * @since 0.1
 */
function classblogging_setup()
{
	add_editor_style();
	add_theme_support( 'automatic-feed-links' );

	// Configure the customizable image header
	define( 'NO_HEADER_TEXT', true );
	define( 'HEADER_TEXTCOLOR', '' );
	define( 'HEADER_IMAGE', '%s/images/default-header.png' );
	define( 'HEADER_IMAGE_WIDTH', 222 );
	define( 'HEADER_IMAGE_HEIGHT', 108 );
	add_custom_image_header( '', 'classblogging_admin_header_style' );

	// Set the content width
	global $content_width;
	if( !isset( $content_width ) ) {
		$content_width = 720;
	}

	// Register navigation menu
	register_nav_menus(
		array(
		  'primary_nav' => __( 'Primary Navigation', 'classblogging' ),
		)
	);

	// Enable i18n functionality
	load_theme_textdomain( 'classblogging', get_template_directory() . '/languages' );
	$locale = get_locale();
	$locale_file = get_template_directory() . "/languages/$locale.php";
	if ( is_readable( $locale_file ) ) {
		require_once( $locale_file );
	}
}

/**
 * Provides styling for the theme's header-image admin page
 *
 * @since 0.1
 */
function classblogging_admin_header_style()
{
?>
<style type="text/css">
	#headimg {
		min-height: <?php echo HEADER_IMAGE_HEIGHT; ?> !important;
	}
</style>
<?php
}

/**
 * Initializes the widgetized areas in the theme
 *
 * @since 0.1
 */
function classblogging_widgets_init()
{
	register_sidebar( array(
		'name' => __( 'Sidebar', 'classblogging' ),
		'id' => 'sidebar',
		'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}

/**
 * Determines whether or not sitewide student post data can be accessed
 *
 * This data is accessible if this theme is run in conjunction with the class
 * blogs must-use plugin and that plugin's sitewide posts feature is enabled.
 *
 * @return bool whether student post data can be accessed
 *
 * @since 0.1
 */
function classblogging_student_posts_available()
{
	$available = false;
	if ( class_exists( 'ClassBlogs' ) ) {
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$sitewide_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
		$available = ! empty( $sitewide_posts ) && ! empty( $sitewide_comments );
	}
	return $available;
}

/**
 * Outputs markup showing the user that no posts were found for their query
 *
 * @since 0.1
 */
function classblogging_show_no_posts_message()
{
?>
	<h1 id="page-title"><?php _e( 'No Posts Found', 'classblogging' ); ?></h1>
	<div class="content no-posts">
		<p><?php _e( 'No posts were found.  You can try searching for posts using the form below.', 'classblogging' ); ?></p>
		<?php get_search_form(); ?>
	</div>
<?php
}

/**
 * Outputs markup showing the page title for an archive page
 *
 * @param string $title  the title of the archive page
 * @param string $filter the name of the archive filter
 *
 * @since 0.1
 */
function classblogging_show_archive_page_title( $title, $filter )
{
	global $page, $paged;
	$current_page = max( $page, $paged );

	$title_parts = array( $title );
	if ( $filter ) {
		$title_parts[] = sprintf( '<strong class="filter">%s</strong>', $filter );
	}
	if ( $current_page > 1 ) {
		$title_parts[] = sprintf( __( 'Page %d', 'classblogging' ), $current_page );
	}
	printf( '<h1 id="page-title">%s</h1>', implode( ' &ndash; ', $title_parts ) );
}

/**
 * Outputs markup for showing post navigation
 *
 * @param string $class an optional CSS class to assign to the navigation wrapper
 *
 * @since 0.1
 */
function classblogging_show_navigation( $class = "" )
{
	global $wp_query;
	if (  $wp_query->max_num_pages > 1 ) {
?>
		<div class="navigation <?php echo $class; ?>">
			<div class="previous"><?php next_posts_link( __( 'Older posts', 'classblogging' ) ); ?></div>
			<div class="next"><?php previous_posts_link( __( 'Newer posts', 'classblogging' ) ); ?></div>
		</div>
<?php
	}
}

/**
 * Outputs markup for showing comment-page navigation links
 *
 *  @since 0.1
 */
function classblogging_show_comment_navigation()
{
	echo '<div class="comment-navigation">';
	paginate_comments_links();
	echo '</div>';
}

/**
 * Outputs markup to show a post's author and date
 *
 * @since 0.1
 */
function classblogging_show_author_and_date()
{
?>
	<dl class="meta">
		<dt class="key"><?php echo _x( 'Posted', 'post creation date', 'classblogging' ); ?></dt>
		<dd class="value"><?php echo get_the_date(); ?></dd>
		<dt class="key"><?php echo _x( 'Author', 'post author name', 'classblogging' ); ?></dt>
		<dd class="value"><?php echo get_the_author(); ?></dd>
	</dl>
<?php
}

/**
 * Outputs markup to show a post's categories or tags
 *
 * @param string $name   the name of the taxonomy group
 * @param string $markup the markup for the taxonomy group
 *
 * @access private
 * @since 0.1
 */
function _classblogging_show_taxonomy_group( $name, $markup )
{
	if ( $markup ) {
		printf( '<h4 class="type">%s</h4><div class="values">%s</div>',
			$name, $markup );
	}
}

/**
 * Outputs markup to show a post's taxonomy information
 *
 * @since 0.1
 */
function classblogging_show_taxonomy()
{
?>
	<div class="taxonomy">
		<?php
			_classblogging_show_taxonomy_group(
				__( 'Categories', 'classblogging' ),
				get_the_category_list() );
			_classblogging_show_taxonomy_group(
				_x( 'Tags', 'plural noun', 'classblogging' ),
				get_the_tag_list( '', ' ', '' ) );
		?>
	</div>
<?php
}

/**
 * Outputs markup to show the "edit post" link
 *
 * @since 0.1
 */
function classblogging_show_edit_link() {
	edit_post_link( __( 'Edit Post', 'classblogging' ), "", "" );
}

/**
 * Gets a list of posts made by each user on the blog
 *
 * The returned posts are sorted in descending order by the published date of
 * the first post in each user's list of posts.
 *
 * @return array a list of posts grouped by user
 *
 * @since 0.1
 */
function classblogging_get_posts_by_user()
{
	$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
	return $sitewide_posts->get_posts_by_user();
}

/**
 * Returns the number of comments that a student has left
 *
 * @param  int    $user_id the user ID of a student
 * @return string          the number of comments left by the student
 */
function classblogging_get_total_comments_for_student( $user_id )
{
	$sitewide_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
	return $sitewide_comments->get_total_comments_for_student( $user_id );
}

/**
 * Returns the URL of a student's blog
 *
 * @param  int    $user_id the user ID of a student
 * @return string          the URL of the student's blog, or a blank string
 *
 * @since 0.1
 */
function classblogging_get_blog_url_for_student( $user_id )
{
	return ClassBlogs_Students::get_blog_url_for_student( $user_id );
}

/**
 * Returns at most the requested number of words from the given text
 *
 * @param  string $content    text for which to make an excerpt
 * @param  int    $word_count the maximum number of words to use
 * @return string             the requested number of words of the text
 *
 * @since 0.1
 */
function classblogging_get_post_excerpt( $content, $word_count )
{
	$content = strip_shortcodes( strip_tags( $content ) );
	$words = preg_split( '/\s+/', $content );
	if ( count( $words ) <= $word_count ) {
		return $content;
	} else {
		$excerpt = join( ' ', array_slice( $words, 0, $word_count ) );
		if ( '.' == substr( $excerpt, -1) ) {
			$excerpt = substr( $excerpt, 0, -1 );
		}
		return $excerpt . '&hellip;';
	}
}

/**
 * Makes any page created on the blog be closed for commenting by default.
 *
 * @param  string $new  the new status of a post or page
 * @param  string $old  the old status of a post or page
 * @param  object $post a post or page instance
 *
 * @since 0.1
 */
function classblogging_close_new_page_comments( $new, $old, $post )
{
	global $wpdb;
	if ( $post->post_type == 'page' && $new == 'publish' && $old != $new ) {
		wp_update_post(
			array( 'ID' => $post->ID, 'comment_status' => 'closed' ) );
	}
}

/**
 * Switches to a blog with the given ID.
 *
 * This switches to the given blog when in multisite mode, or does nothing
 * when running a normal installation with only a single blog defined.
 *
 * @param int $blog_id the ID of a blog
 *
 * @since 0.4
 */
function classblogging_switch_to_blog( $blog_id )
{
	if ( function_exists( 'switch_to_blog' ) ) {
		switch_to_blog( $blog_id );
	}
}

/**
 * Restores the current blog.
 *
 * This restores the current blog when in multisite mode, or does nothing
 * when running a normal installation where the current blog is static.
 *
 * @since 0.4
 */
function classblogging_restore_current_blog()
{
	if ( function_exists( 'restore_current_blog' ) ) {
		restore_current_blog();
	}
}

// Register setup functions with WordPress hooks
add_action( 'after_setup_theme',      'classblogging_setup' );
add_action( 'transition_post_status', 'classblogging_close_new_page_comments', 100, 3 );
add_action( 'widgets_init',           'classblogging_widgets_init' );

?>
