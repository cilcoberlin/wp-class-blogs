<?php

/**
 * The class blogs sitewide post, comment and tag aggregator
 *
 * This creates tables to hold sitewide data, and watches for updates to post,
 * comment and tag data on all blogs on the site.  Whenever changes are detected,
 * the sitewide tables updated to match the changed data.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_Aggregator extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * Default options for the aggregator
	 *
	 * @access protected
	 * @var array
	 */
	protected static $default_options = array(
		'aggregation_enabled' => true,
		'excluded_blogs'      => array(),
		'tables_created'      => false
	);

	/**
	 * The names of the sitewide tables
	 *
	 * @var object
	 * @since 0.1
	 */
	public $tables;

	/**
	 * Determines table names and conditionally registers WordPress hooks
	 */
	public function __construct() {

		parent::__construct();

		// Get the names of the sitewide tables
		$this->tables = ClassBlogs_Plugins_Aggregation_Settings::get_table_names();

		// Enable the admin interface
		if ( is_admin() ) {
			add_action( 'network_admin_menu', array( $this, 'configure_admin_interface' ) );
		}

		// If aggregation is enabled, initialize the aggregator hooks
		if ( $this->get_option( 'aggregation_enabled' ) ) {
			$this->_initialize_aggregation_hooks();
		}
	}

	/**
	 * Initializes the post and comment hooks required to aggregate data
	 *
	 * This aggregator works by listening for new posts and comments.  Since
	 * updating a new post will also update the tags used by that post, this
	 * allows us to catch changes in the sitewide tags as well.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _initialize_aggregation_hooks() {

		// If the tables have yet to be created, do so now
		if ( ! $this->get_option( 'tables_created' ) ) {
			$this->_create_tables();
		}

		// Post modification actions that also catch new tags usages
		add_action( 'save_post', array( $this, 'update_aggregation_data' ) );
		add_action( 'deleted_post', array( $this, 'update_aggregation_data' ) );
		add_action( 'transition_post_status', array( $this, 'update_aggregation_data' ), 10, 3 );

		// Comment actions
		add_action( 'comment_post', array( $this, 'update_aggregation_data' ), 10, 2 );
		add_action( 'wp_set_comment_status', array( $this, 'update_aggregation_data' ), 10, 2 );
		add_action( 'wp_update_comment_count', array( $this, 'update_aggregation_data' ), 10, 3 );
	}

	/**
	 * Updates the sitewide data tables when changes to the tracked data are made
	 *
	 * The arguments passed are simply placeholder values to allow this to play
	 * nicely with the WordPress actions that will be calling this.
	 *
	 * @since 0.1
	 */
	public function update_aggregation_data( $one = null, $two = null, $three = null )
	{
		$this->sync_tables();
	}

	/**
	 * Creates a copy of a WordPress table for sitewide aggregation
	 *
	 * This builds a table structured like the source WordPress table and then
	 * alters the table slightly, removing the unique auto-incrementing primary
	 * key and adding a column to keep track of the original blog from which
	 * the data came.
	 *
	 * @param string $source      the name of the WordPress table to clone
	 * @param string $destination the name of the new table
	 * @param string $key         the name of the WordPress table's primary key
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _clone_wp_table( $source, $destination, $key )
	{
		global $wpdb;

		$wpdb->query( "CREATE TABLE IF NOT EXISTS $destination LIKE $source" );
		$wpdb->query( "ALTER TABLE $destination CHANGE $key $key BIGINT(20) UNSIGNED NOT NULL, DROP PRIMARY KEY" );
		$wpdb->query( "ALTER TABLE $destination ADD from_blog BIGINT(20) NOT NULL" );
		// TODO: add a non-primary key
		//$wpdb->query( "ALTER TABLE $destination ADD KEY 'from_blog' ('from_blog')" );
	}

	/**
	 * A convenience function that creates a table for sitewide aggregation
	 *
	 * @param string $name    the name of the table to create
	 * @param array  $columns an array of SQL column declarations
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _create_sitewide_table( $name, $columns )
	{
		global $wpdb;

		$wpdb->query( sprintf("CREATE TABLE IF NOT EXISTS $name (%s) %s",
			join( ", ", $columns ),
			( DB_CHARSET ) ? 'DEFAULT CHARSET=' . DB_CHARSET : "" ) );
	}

	/**
	 * Creates the tables needed to store sitewide data
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _create_tables()
	{
		global $wpdb;

		// Create copies of the posts and comments table customized to hold
		// information on sitewide posts and comments
		$this->_clone_wp_table( $wpdb->posts, $this->tables->posts, 'ID' );
		$this->_clone_wp_table( $wpdb->comments, $this->tables->comments, 'comment_ID' );

		//  Create the tag and tag usage tables
		$this->_create_sitewide_table(
			$this->tables->tags,
			array(
				'term_id BIGINT(20) AUTO_INCREMENT NOT NULL PRIMARY KEY',
				'name VARCHAR(200) NOT NULL',
				'slug VARCHAR(200) NOT NULL',
				'count BIGINT(20) NOT NULL DEFAULT 0' ) );
		$this->_create_sitewide_table(
			$this->tables->tag_usage,
			array(
				'post_id BIGINT(20) UNSIGNED NOT NULL',
				'uses_tag BIGINT(20) NOT NULL',
				'from_blog BIGINT(20) NOT NULL' ) );

		// Populate the tables with sitewide data
		$this->update_option( 'tables_created', true );
		$this->sync_tables();
	}

	/**
	 * Removes all data from the sitewide tables
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _clear_tables()
	{
		global $wpdb;
		foreach ( $this->tables as $name => $table ) {
			$wpdb->query( "DELETE FROM $table" );
		}
	}

	/**
	 * Returns a list of all blog IDs that whose data can be aggregated
	 *
	 * This takes an initial list of all blog IDs on the site and then removes
	 * any IDs that appear in the exclusion list.
	 *
	 * @return array a list of all usable blog IDs
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_usable_blog_ids()
	{
		return array_diff(
			$this->get_all_blog_ids(),
			$this->get_option( 'excluded_blogs' ) );
	}

	/**
	 * Aggregates all sitewide posts, comments and tags into separate tables
	 *
	 * @since 0.1
	 */
	public function sync_tables()
	{
		global $wpdb;
		$tag_counts = $tag_usage = $tag_origins = array();
		$blogs = $this->_get_usable_blog_ids();

		// Drop all data before proceeding
		$this->_clear_tables();

		// Export the post, comment and tag data for each blog to our tables
		foreach ( $blogs as $blog_id ) {

			switch_to_blog( $blog_id );

			// Populate the posts table, ignoring any default posts
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO {$this->tables->posts}
				SELECT p.*, %d FROM $wpdb->posts AS p
				WHERE p.post_status = 'publish' AND p.post_type = 'post' AND p.post_title <> %s",
				$blog_id, ClassBlogs_Plugins_Aggregation_Settings::FIRST_POST_TITLE ) );

			// Populate the comments table, ignoring any default comments
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO {$this->tables->comments}
				SELECT c.*, %d FROM $wpdb->comments AS c
				WHERE c.comment_author <> %s AND comment_approved = '1'",
				$blog_id, ClassBlogs_Plugins_Aggregation_Settings::FIRST_COMMENT_AUTHOR ) );

			// Add all tags used by the blog to a master list
			$used_tags = $wpdb->get_results( $wpdb->prepare( "
				SELECT t.name, t.slug, tt.count, tt.term_taxonomy_id
				FROM $wpdb->terms AS t, $wpdb->term_taxonomy AS tt
				WHERE t.term_id = tt.term_id AND tt.taxonomy = %s",
				ClassBlogs_Plugins_Aggregation_Settings::TAG_TAXONOMY_NAME ) );
			foreach ( $used_tags as $tag ) {

				// Keep track of the originating blog for the tag
				$tag_origins[$blog_id][$tag->term_taxonomy_id] = $tag->slug;

				// Update the tag's usage count
				if ( ! isset ( $tag_usage[$tag->slug] ) ) {
					$tag_usage[$tag->slug] = array(
						'count' => 0,
						'name'  => $tag->name );
				}
				$tag_usage[$tag->slug]['count'] += $tag->count;
			}

			restore_current_blog();
		}

		//  Add the master list of tags to the sitewide table
		foreach ( $tag_usage as $slug => $meta ) {
			if ( $meta['count'] > 0 )
				$wpdb->query( $wpdb->prepare( "
					INSERT INTO {$this->tables->tags} (name, slug, count)
					VALUES (%s, %s, %s)",
					$meta['name'], $slug, $meta['count'] ) );
		}

		// Update the records of tag usage in the sitewide table
		foreach ( $blogs as $blog_id) {

			// Ignore the current blog if it has no tags registered
			if ( ! array_key_exists( $blog_id, $tag_origins ) ) {
				continue;
			}

			switch_to_blog( $blog_id );

			// Add any tag usage records on the current blog to the sitewide
			// master list, linking the tag with its ID in the sitewide table
			foreach ( $wpdb->get_results( "SELECT * FROM $wpdb->term_relationships" ) as $tag ) {
				if ( in_array( $tag->term_taxonomy_id, array_keys( $tag_origins[$blog_id] ) ) ) {

					$sw_tag_id = $wpdb->get_var( $wpdb->prepare( "
						SELECT term_id FROM {$this->tables->tags}
						WHERE slug = %s",
						$tag_origins[$blog_id][$tag->term_taxonomy_id] ) );

					$wpdb->query( $wpdb->prepare( "
						INSERT INTO {$this->tables->tag_usage} (post_id, uses_tag, from_blog)
						VALUES (%s, %s, %s)",
						$tag->object_id, $sw_tag_id, $blog_id ) );
				}
			}

			restore_current_blog();
		}
	}

	/**
	 * Enables the admin interface for the sitewide aggregation features
	 *
	 * @since 0.1
	 */
	public function configure_admin_interface()
	{
		if ( is_super_admin() ) {
			$admin = ClassBlogs_Admin::get_admin();
			$admin->add_admin_page( $this->get_uid(), __( 'Sitewide Data Options', 'classblogs' ), array( $this, 'admin_page' ) );
		}
	}

	/**
	 * Returns an array of excluded blog IDs from the POST data
	 *
	 * @param  array $post the POST data from the admin form
	 * @return array       an array of blog IDs to exclude from aggregation
	 *
	 * @since 0.1
	 */
	public function parse_excluded_blog_list( $post )
	{
		$blogs = array();
		foreach ( $post as $key => $value ) {
			if ( preg_match( '/^exclude_blog_(\d+)$/', $key, $matches ) ) {
				if ( 'on' == $value ) {
					$blogs[] = $matches[1];
				}
			}
		}
		return $blogs;
	}

	/**
	 * Handles the network admin page logic for the plugin
	 *
	 * @since 0.1
	 */
	public function admin_page()
	{

		// Update the plugin options
		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$this->update_option( 'excluded_blogs', $this->parse_excluded_blog_list( $_POST ) );
			$this->update_option( 'aggregation_enabled', $_POST['aggregation_enabled'] == 'enabled' );

			// Since the list of excluded blogs may have changed, resync the
			// sitewide tables after updating the options
			$this->sync_tables();

			echo '<div id="message" class="updated fade"><p>' . __( 'Your options have been updated.', 'classblogs' ) . '</p></div>';
		}

?>
		<div class="wrap">

			<h2><?php _e( 'Sitewide Data Options', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This page allows you to manage the options for collecting sitewide data on posts, comments and tags from all the blogs on this site.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

				<table class="form-table">

					<tr valign="top">
						<th scope="row"><?php _e( 'Aggregation Enabled', 'classblogs' ); ?></th>
						<td>
							<input type="radio" name="aggregation_enabled" id="aggregation-enabled" value="enabled" <?php if ( $this->get_option( 'aggregation_enabled' ) ): ?>checked="checked"<?php endif ?> />
							<label for="aggregation-enabled"><?php _e( 'Enabled', 'classblogs' ); ?></label>
							<input style="margin-left: 1em;" type="radio" name="aggregation_enabled" id="aggregation-disabled" value="disabled" <?php if ( ! $this->get_option( 'aggregation_enabled' ) ): ?>checked="checked"<?php endif ?> />
							<label for="aggregation-disabled"><?php _e( 'Disabled', 'classblogs' ); ?></label>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><?php _e( 'Excluded Blogs', 'classblogs' ); ?></th>
						<td>
							<ul>
<?php

		// Display a checkbox for every blog, selecting it if the blog is
		// currently on the exclusion blacklist
		$excluded_blogs = $this->get_option( 'excluded_blogs' );
		foreach ( $this->get_all_blog_ids() as $blog_id ) {
			$details = get_blog_details( $blog_id, true );
			printf( '<li><input type="checkbox" id="%1$s" name="%1$s" %2$s /> <label for="%1$s"><strong>%3$s</strong> ( <a href="%4$s">%4$s</a> )</label></li>',
				'exclude_blog_' . $blog_id,
				( array_search( $blog_id, $excluded_blogs ) !== false ) ? 'checked="checked"' : "",
				$details->blogname,
				$details->siteurl );
		}

		?>
							</ul>
						</td>
					</tr>

				</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Update Sitewide Data Options', 'classblogs' ); ?>" /></p>

			</form>
		</div>
<?php
	}
}

$plugin = new ClassBlogs_Plugins_Aggregation_Aggregator();

?>
