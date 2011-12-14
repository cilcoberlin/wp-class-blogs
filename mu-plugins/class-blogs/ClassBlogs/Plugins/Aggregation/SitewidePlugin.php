<?php

/**
 * The base class for any plugin that deals with sitewide data.
 *
 * This provides descended classes with a few utility methods to manipulate
 * sitewide data, a set of methods that are used to properly inject sitewide
 * data into the normal WordPress loop, some methods that allow for easy
 * management of cached sitewide data, and a list of the names of the tables
 * used to track sitewide data.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewidePlugin
 * @since 0.1
 */
abstract class ClassBlogs_Plugins_Aggregation_SitewidePlugin extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The names of the sitewide tables.
	 *
	 * This works similarly to WordPress's `$wpdb` global, with each table name
	 * being available through a short term that is a property of the `$tables`
	 * object.  The available table keys and their natures are as follows:
	 *
	 *     posts     - a master list of any published posts on any blog on the site
	 *     comments  - a master list of all comments left on any blog on the site
	 *     tags      - a list of all tags used across the site with usage counts
	 *     tag_usage - a record of which posts are using which tags
	 *
	 * @access protected
	 * @var object
	 * @since 0.2
	 */
	protected $sw_tables;

	/**
	 * A container for actual posts made on the root blog.
	 *
	 * This is used as part of the logic that injects the sitewide post data
	 * into WordPress loop.  The basic idea is that the sitewide data is injected
	 * when the loop start, at which point a copy of the actual posts that would
	 * appear on the page is made.  When the loop is finished, these posts are
	 * restored, which allows other components of the page, such as widgets
	 * or navigation, to display properly.
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $root_blog_posts;

	/**
	 * Resolve the sitewide table names on startup.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->sw_tables = ClassBlogs_Plugins_Aggregation_Settings::get_table_names();
	}

	/**
	 * Returns an array of sitewide resources limited globally and by blog.
	 *
	 * This is a utility function to filter an existing array of sitewide
	 * resources that indicate their provenance, filtering by both the total
	 * number of allowed resources and then by the total number per blog.
	 *
	 * @param  array $resources    the sitewide resources
	 * @param  int   $max          the number of resources to return
	 * @param  int   $max_per_blog the most resources per blog to allow
	 * @return array               the limited set of passed resources
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function limit_sitewide_resources( $resources, $max, $max_per_blog )
	{
		$subset = array();
		$per_blog = array();
		$total_resources = 0;

		foreach ( $resources as $resource ) {

			// Ignore the resource if our global or per-blog quota has been exceeded for the
			// blog on which it was made
			if ( ! array_key_exists( $resource->cb_sw_blog_id, $per_blog ) ) {
				$per_blog[$resource->cb_sw_blog_id] = 0;
			} else {
				if ( $per_blog[$resource->cb_sw_blog_id] >= $max_per_blog ) {
					continue;
				}
			}

			$subset[] = $resource;

			// Abort if our total resource quota has been exceeded
			$per_blog[$resource->cb_sw_blog_id]++;
			$total_resources++;
			if ( $total_resources >= $max ) {
				break;
			}
		}

		return $subset;
	}

	/**
	 * Filters sitewide data that involves a user ID and a datetime by these fields.
	 *
	 * This mainly applies to sitewide data that closely resembles core WordPress
	 * data, such as the comments and posts, both of which contain information
	 * about the originating user and a creation datetime.
	 *
	 * @param  string $table      the name of the table on which to make a query
	 * @param  string $id_field   the name of the field that stores a user ID
	 * @param  int    $user_id    the ID of the user who created the content
	 * @param  string $date_field the optional name of the field that holds datetime information
	 * @param  object $start_dt   an optional DateTime after which to find data
	 * @param  object $end_dt     an optional DateTime before which to find data
	 * @return array              a list of database results
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function filter_sitewide_resources( $table, $id_field, $user_id, $date_field=null, $start_dt=null, $end_dt=null )
	{
		global $wpdb;

		// Build our base query by user ID and add in any optional parameters
		// required to make the datetime search work properly
		$query = "SELECT * FROM $table WHERE $id_field=%d";
		$params = array( $user_id );
		if ( $date_field ) {
			if ( $start_dt ) {
				$query .= " AND $date_field >= %s";
				$params[] = $start_dt->format( 'YmdHis' );
			}
			if ( $end_dt ) {
				$query .= " AND $date_field <= %s";
				$params[] = $end_dt->format( 'YmdHis' );
			}
		}
		array_unshift( $params, $query );

		// Get the filtered posts using our assembled query
		return $wpdb->get_results( call_user_func_array(
			array( $wpdb, 'prepare') , $params ) );
	}

	/**
	 * Sets the correct blog if a sitewide post is being displayed.
	 *
	 * If the current post has an attribute indicating which blog it was made on,
	 * it means that it is a sitewide post, and the blog that it exists on should
	 * be made active so that the post's permalinks, tags, etc. can be determined.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function use_correct_blog_for_sitewide_post()
	{
		global $post, $wp_rewrite;

		if ( property_exists( $post, 'cb_sw_blog_id' ) ) {

			// Store the original rewrite rules for later
			if ( ! isset( $this->_rewrite ) ) {
				$this->_rewrite = $wp_rewrite;
			}

			// Switch to the post's blog
			restore_current_blog();
			switch_to_blog( $post->cb_sw_blog_id );

			// Generate new rewrite rules for the blog, which will allow things
			// like categories and tags to display properly
			$wp_rewrite = new WP_Rewrite();
		}
	}

	/**
	 * Restores the root blog after the loop has ended.
	 *
	 * This needs to be called to prevent the blog from thinking it is on the
	 * blog on which the last sitewide post displayed was made, which will result
	 * in display and URL-resolution issues.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function reset_blog_on_loop_end()
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
	 * @access protected
	 * @since 0.2
	 */
	protected function prevent_sitewide_post_id_conflicts( $posts )
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
	 * @access protected
	 * @since 0.2
	 */
	protected function restore_sitewide_post_ids()
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
