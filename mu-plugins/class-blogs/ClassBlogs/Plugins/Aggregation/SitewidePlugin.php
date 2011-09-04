<?php

/**
 * An abstract base calss for a plugin that deals with sitewide data
 *
 * @package Class Blogs
 * @since 0.1
 */
abstract class ClassBlogs_Plugins_Aggregation_SitewidePlugin extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The names of the sitewide tables
	 *
	 * @var object
	 * @since 0.1
	 */
	public $sw_tables;

	/**
	 * A container for actual posts made on the root blog
	 *
	 * @var array
	 * @since 0.1
	 */
	public $root_blog_posts;

	/**
	 * Resolve the sitewide table names on startup
	 */
	public function __construct()
	{
		parent::__construct();
		$this->sw_tables = ClassBlogs_Plugins_Aggregation_Settings::get_table_names();
	}

	/**
	 * Returns an array of sitewide resources limited globally and by blog
	 *
	 * This is a utility function to filter an existing array of sitewide
	 * resources that indicate their provenance, filtering by both the total
	 * number of allowed resources and then by the total number per blog.
	 *
	 * @param  array $resources    the sitewide resources
	 * @param  int   $max          the number of resources to return
	 * @param  int   $max_per_blog the most resources per blog to allow
	 * @return array               the limited set of passed resources
	 */
	protected function limit_sitewide_resources( $resources, $max, $max_per_blog )
	{
		$subset = array();
		$per_blog = array();
		$total_resources = 0;

		foreach ( $resources as $resource ) {

			// Ignore the resource if our global or per-blog quota has been exceeded for the
			// blog on which it was made
			if ( ! array_key_exists( $resource->from_blog, $per_blog ) ) {
				$per_blog[$resource->from_blog] = 0;
			} else {
				if ( $per_blog[$resource->from_blog] >= $max_per_blog ) {
					continue;
				}
			}

			$subset[] = $resource;

			// Abort if our total resource quota has been exceeded
			$per_blog[$resource->from_blog]++;
			$total_resources++;
			if ( $total_resources >= $max ) {
				break;
			}
		}

		return $subset;
	}

	/**
	 * Sets the correct blog if a sitewide post is being displayed
	 *
	 * If the current post has an attribute indicating which blog it was made on,
	 * it means that it is a sitewide post, and the blog that it exists on should
	 * be made active so that the post's permalinks, tags, etc. can be determined.
	 *
	 * @since 0.1
	 */
	public function use_correct_blog_for_sitewide_post()
	{
		global $post, $wp_rewrite;

		if ( property_exists( $post, 'from_blog' ) ) {

			// Store the original rewrite rules for later
			if ( ! isset( $this->_rewrite ) ) {
				$this->_rewrite = $wp_rewrite;
			}

			// Switch to the post's blog
			restore_current_blog();
			switch_to_blog( $post->from_blog );

			// Generate new rewrite rules for the blog, which will allow things
			// like categories and tags to display properly
			$wp_rewrite = new WP_Rewrite();
		}
	}

	/**
	 * Restores the root blog after the loop has ended
	 *
	 * This needs to be called to prevent the blog from thinking it is on the
	 * blog on which the last sitewide post displayed was made, and that its
	 * posts are all sitewide.
	 *
	 * @since 0.1
	 */
	public function reset_blog_on_loop_end()
	{
		global $wp_query, $wp_rewrite;
		restore_current_blog();
		if ( isset( $this->root_blog_posts ) ) {
			$wp_query->posts = $this->root_blog_posts;
		}
		if ( isset( $this->_rewrite ) ) {
			$wp_rewrite = $this->_rewrite;
		}
	}

	/**
	 * Prevents sitewide post IDs from conflicting with pages or posts on the
	 * blog that is displaying them.
	 *
	 * This function should be called whenever a sitewide plugin overrides the
	 * posts for a given page using the `the_posts` filter.  Since one or more
	 * of the sitewide posts might have an ID that is the same as a post or page
	 * on the blog that is displaying them, this function gives every sitewide
	 * post an invalid ID.  It also keeps a record of the actual post IDs, which
	 * can be used to restore the sitewide posts' IDs when needed, such as when
	 * a theme enters the loop.
	 *
	 * @param  array $posts the posts used to replace the normal page's posts
	 * @return array        the posts with invalid IDs
	 *
	 * @since 0.1
	 */
	public function prevent_sitewide_post_id_conflicts( $posts )
	{
		$this->_sitewide_post_ids = array();
		for ( $i=0; $i < count( $posts ); $i++ ) {
			$this->_sitewide_post_ids[$i] = $posts[$i]->ID;
			$posts[$i]->ID = -1;
		}
		return $posts;
	}

	/**
	 * Restores the correct ID of each sitewide post.
	 *
	 * This is used in conjunction with the `prevent_sitewide_post_id_conflicts`
	 * function to give a post back its proper ID when needed, such as when
	 * a theme is in the loop.
	 *
	 * @since 0.1
	 */
	public function restore_sitewide_post_ids()
	{
		// If the conflict-preventing function has yet to be run, abort early
		if ( empty( $this->_sitewide_post_ids ) ) {
			return;
		}

		// Give each sitewide post back its proper ID
		global $wp_query;
		for ( $i=0; $i < count( $wp_query->posts ); $i++ ) {
			$wp_query->posts[$i]->ID = $this->_sitewide_post_ids[$i];
		}
	}
}

?>
