<?php

/**
 * An aggregator that keeps track of any posts, comments and tags used on the site.
 *
 * This aggregator first creates tables to hold sitewide data.  Once the tables
 * exists, it watches for changes to post, comment and tag data on all blogs on
 * the site.  Whenever changes are detected, the sitewide tables are updated to
 * match the changed data.
 *
 * Whenever the tables are modified, a full sync of all sitewide data is performed.
 * Depending on the size of the site, this can take a few seconds.  Once the
 * initial sync has been done, however, a much faster diff-style synchronization
 * is performed that only updates content that has changed.  If the sitewide
 * content no longer seems to be in sync with the actual content on the site's
 * blogs, an admin can manually resync the tables through the plugin's admin page.
 *
 * By default, every blog on the site is watched for changes.  However, using the
 * plugin's admin page, certain blogs can be blacklisted, or a small group of
 * blogs can be whitelisted, allowing fine control over which data is aggregated.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage Aggregator
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_Aggregator extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * Default options for the aggregator.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'aggregation_enabled' => true,
		'excluded_blogs'      => array(),
		'tables_created'      => false
	);

	/**
	 * Determines table names and conditionally registers WordPress hooks.
	 */
	public function __construct() {

		parent::__construct();

		// If aggregation is enabled, initialize the aggregator hooks
		if ( $this->get_option( 'aggregation_enabled' ) ) {
			$this->_initialize_aggregation_hooks();
		}
	}

	/**
	 * Initializes the post and comment hooks required to aggregate data.
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
		add_action( 'save_post', array( $this, '_track_single_post' ) );
		add_action( 'wp_update_comment_count', array( $this, '_track_single_post' ) );

		// Comment actions
		add_action( 'comment_post', array( $this, '_track_single_comment' ) );
		add_action( 'wp_set_comment_status', array( $this, '_track_single_comment' ), 10, 2 );
	}

	/**
	 * Monitors updates to all post tables and updates the sitewide posts table
	 * based on the status of the post.
	 *
	 * This does not actually perform any modifications to the sitewide posts
	 * table, as such actions are delegated to various other functions that
	 * handle addition, deletion or modification.
	 *
	 * @param int $post_id the ID of the post being modified
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _track_single_post( $post_id )
	{
		global $blog_id;

		// Abort early if the post is a revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// If the post is being published, update its sitewide record, and remove
		// it from the sitewide table if it is no longer visible to the public,
		// either due to a change of status or outright deletion
		$this->clear_site_cache();
		switch ( get_post_status( $post_id ) ) {
			case 'publish':
				$this->_update_sw_post( $blog_id, $post_id );
				break;
			case 'auto-draft':
			case 'draft':
			case 'future':
			case 'pending':
			case 'private':
				$this->_delete_sw_post( $blog_id, $post_id );
				break;
		}
	}

	/**
	 * Monitors updates to all comments tables and updates the sitewide comments
	 * table based on the status of the comment.
	 *
	 * This does not actually perform any modifications to the sitewide comments
	 * table, as such actions are delegated to various other functions that
	 * handle addition, deletion or modification.
	 *
	 * @param int    $comment_id the ID of the comment being modified
	 * @param string $status     the optional new comment status
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _track_single_comment( $comment_id, $status = "" )
	{
		global $blog_id;
		$this->clear_site_cache();

		// Since this function may be called be either the comment_posted hook
		// or the wp_set_comment_status hook, we might receive the actual status
		// of the post in the $status arg, or we might need to look it up using
		// the post's ID, if no status information was passed
		if ( ! $status ) {
			$status = wp_get_comment_status( $comment_id );
		}

		// If the comment is being made public, update its sitewide record,
		// and remove it if it is no longer publicly visible
		switch ( $status ) {
			case 'approve':
			case 'approved':
				$this->_update_sw_comment( $blog_id, $comment_id );
				break;
			default:
				$this->_delete_sw_comment( $blog_id, $comment_id );
		}
	}

	/**
	 * Adds or updates a record of a post to the sitewide posts table.
	 *
	 * In addition to modifying the record for the post, this also tracks the
	 * tags used by the post, updating those in the sitewide tags tables.
	 *
	 * @param int $blog_id the ID of the blog on which the post was made
	 * @param int $post_id the post's ID on its blog
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _update_sw_post( $blog_id, $post_id )
	{

		global $wpdb;
		switch_to_blog( $blog_id );

		// Update the post's record in the sitewide table
		$this->_copy_sw_object( $blog_id, $post_id, 'ID', $wpdb->posts, $this->sw_tables->posts );

		// Get a list of the tags used by the current post and those that have
		// been added to the list of sitewide tags
		$local_tags = $local_tag_slugs = $sw_tag_slugs = array();
		foreach ( wp_get_post_tags( $post_id ) as $local_tag ) {
			$local_tags[$local_tag->slug] = $local_tag;
			$local_tag_slugs[] = $local_tag->slug;
		}
		$sw_tags = $wpdb->get_results( $wpdb->prepare( "
			SELECT t.slug
			FROM {$this->sw_tables->tag_usage} AS tu, {$this->sw_tables->tags} AS t
			WHERE tu.post_id=%d AND tu.blog_id=%d AND tu.uses_tag=t.term_id",
			$post_id, $blog_id ) );
		foreach ( $sw_tags as $sw_tag ) {
			$sw_tag_slugs[] = $sw_tag->slug;
		}

		// Create or modify records for tags that need to be added
		$add_slugs = array_diff( $local_tag_slugs, $sw_tag_slugs );
		foreach ( $add_slugs as $add_slug ) {

			// See if a record of the tag already exists
			$tag = $local_tags[$add_slug];
			$sw_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT term_id FROM {$this->sw_tables->tags} WHERE slug=%s",
				$add_slug ) );

			// Create a new record for the tag if it doesn't exist or update
			// the usage count for an existing tag record
			if ( $sw_id ) {
				$wpdb->query( $wpdb->prepare( "
					UPDATE {$this->sw_tables->tags}
					SET count=count+1
					WHERE term_id=%d",
					$sw_id ) );
			} else {
				$wpdb->insert(
					$this->sw_tables->tags,
					array(
						'name' => $tag->name,
						'slug' => $tag->slug,
						'count' => 1
					),
					array( '%s', '%s', '%d' ) );
				$sw_id = $wpdb->insert_id;
			}

			// Create the actual tag-usage record
			$wpdb->query( $wpdb->prepare(
				"INSERT INTO {$this->sw_tables->tag_usage} (post_id, uses_tag, blog_id) VALUES (%d, %d, %d)",
				$post_id, $sw_id, $blog_id ) );
		}

		// Remove any unused tags, dropping the usage count, clearing the usage
		// record, and removing any now-unused tags from the sitewide table
		$drop_slugs = array_diff( $sw_tag_slugs, $local_tag_slugs );
		foreach ( $drop_slugs as $drop_slug ) {
			$sw_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT term_id FROM {$this->sw_tables->tags} WHERE slug=%s",
				$drop_slug ) );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$this->sw_tables->tags} SET count=count-1 WHERE term_id=%d",
				$sw_id ) );
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$this->sw_tables->tag_usage}
				WHERE post_id=%d AND blog_id=%d AND uses_tag=%d",
				$post_id, $blog_id, $sw_id ) );
		}
		if ( ! empty( $drop_slugs ) ) {
			$this->_remove_unused_sitewide_tags();
		}

		restore_current_blog();
	}

	/**
	 * Removes a record of a post from the sitewide posts table.
	 *
	 * This will also remove any record of tag usage associated with the post.
	 *
	 * @param int $blog_id the ID of the blog on which the post was made
	 * @param int $post_id the post's ID on its blog
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _delete_sw_post( $blog_id, $post_id )
	{
		global $wpdb;

		// Remove the post from the sitewide table
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->sw_tables->posts} WHERE ID=%d AND cb_sw_blog_id=%d",
			$post_id, $blog_id ) );

		// Remove any record of tags used by the post
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$this->sw_tables->tags} AS t, {$this->sw_tables->tag_usage} AS tu
			SET t.count=t.count-1
			WHERE tu.post_id=%d AND tu.blog_id=%d AND t.term_id=tu.uses_tag",
			$post_id, $blog_id ) );
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$this->sw_tables->tag_usage}
			WHERE post_id=%d AND blog_id=%d",
			$post_id, $blog_id ) );
		$this->_remove_unused_sitewide_tags();
	}

	/**
	 * Creates or modifies a sitewide record of a comment left on a post.
	 *
	 * @param int $blog_id    the ID of the blog on which the comment was made
	 * @param int $comment_id the ID of the comment on its blog
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _update_sw_comment( $blog_id, $comment_id )
	{
		global $wpdb;
		switch_to_blog( $blog_id );
		$this->_copy_sw_object( $blog_id, $comment_id, 'comment_ID', $wpdb->comments, $this->sw_tables->comments );
		restore_current_blog();
	}

	/**
	 * Deletes a record of a comment from the sitewide table.
	 *
	 * @param int $blog_id    the ID of the blog on which the comment was made
	 * @param int $comment_id the ID of the comment on its blog
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _delete_sw_comment( $blog_id, $comment_id )
	{
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->sw_tables->comments} WHERE comment_ID=%d AND cb_sw_blog_id=%d",
			$comment_id, $blog_id ) );
	}

	/**
	 * A utility function to remove any sitewide tags whose usage count is zero.
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _remove_unused_sitewide_tags()
	{
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$this->sw_tables->tags} WHERE count <= 0" ) );
	}

	/**
	 * Creates a copy of a WordPress object in the sitewide table.
	 *
	 * This is used to abstract the logic for creating a copy of posts and
	 * comments, which share a very similar structure.  If the given item
	 * does not exist in the destination sitewide table, a record is created.
	 * If a record already exists, it is updated with the current version
	 * of the item that is being copied.
	 *
	 * @param int    $blog_id     the ID of the blog on which the data exists
	 * @param int    $item_id     the ID of the data being copied
	 * @param string $id_field    the name of the table's ID field
	 * @param string $source      the name of the source table providing the data
	 * @param string $destination the name of the table to receive the copy
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _copy_sw_object( $blog_id, $item_id, $id_field, $source, $destination )
	{
		global $wpdb;
		$shared_fields = $this->_get_sw_shadow_fields( $source, $destination );

		// See if a record already exists for the item in the sitewide table
		$sw_record = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $destination WHERE $id_field=%d AND cb_sw_blog_id=%d",
			$item_id, $blog_id ) );

		// If the item does not yet exist in the sitewide table, create a record
		if ( empty( $sw_record ) ) {
			$insert_fields = $select_fields = $shared_fields;
			$insert_fields[] = 'cb_sw_blog_id';
			$select_fields[] = $blog_id;
			$wpdb->query( $wpdb->prepare(
				sprintf( "INSERT INTO %s (%s) SELECT %s FROM %s WHERE %s=%%d",
					$destination,
					implode( ", ", $insert_fields ),
					implode( ", ", $select_fields ),
					$source,
					$id_field ),
				$item_id ) );
		}

		// If the item already exists, update its sitewide record
		if ( ! empty( $sw_record ) ) {
			$set_calls = array();
			foreach ( $shared_fields as $field ) {
				$set_calls[] = sprintf( 'd.%1$s=s.%1$s', $field );
			}
			$wpdb->query( $wpdb->prepare(
				sprintf( 'UPDATE %1$s AS s, %2$s AS d SET %3$s WHERE s.%4$s=%%d AND d.%4$s=%%d AND d.cb_sw_blog_id=%%d',
					$source,
					$destination,
					implode( ", ", $set_calls ),
					$id_field ),
				$item_id,
				$item_id,
				$blog_id ) );
		}
	}

	/**
	 * Create the tables needed to store sitewide data.
	 *
	 * This simply applies the table schemata defined in Aggregation/Schemata.php to the
	 * sitewide table names defined in Aggregation/Settings.php.
	 *
	 * @return bool whether or not the tables were synced after creation
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _create_tables()
	{
		global $wpdb;
		$modified = false;

		// Create each table from its schema and track whether or not any
		// modification of the database occurred
		$table_specs = array(
			array( $this->sw_tables->comments, ClassBlogs_Plugins_Aggregation_Schemata::get_comments_schema() ),
			array( $this->sw_tables->posts, ClassBlogs_Plugins_Aggregation_Schemata::get_posts_schema() ),
			array( $this->sw_tables->tags, ClassBlogs_Plugins_Aggregation_Schemata::get_tags_schema() ),
			array( $this->sw_tables->tag_usage, ClassBlogs_Plugins_Aggregation_Schemata::get_tag_usage_schema() )
		);
		foreach ( $table_specs as $spec ) {
			$modified |= $spec[1]->apply_to_table( $spec[0] );
		}

		// If any tables were modified or created, flag this and sync the sitewide data
		if ( $modified ) {
			$this->update_option( 'tables_created', true );
			$this->_sync_tables();
		}
		return $modified;
	}

	/**
	 * Returns a list of all blog IDs that whose data can be aggregated.
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
			ClassBlogs_Utils::get_all_blog_ids(),
			$this->get_option( 'excluded_blogs' ) );
	}

	/**
	 * Get a list of fields that should be copied from the source table to
	 * the destination table.
	 *
	 * This is used when copying data from tables such as the posts or comments
	 * table, which have very similar sitewide equivalents.  This function
	 * produces a list of fields that the sitewide table can receive, which helps
	 * prevent errors if the core WordPress tables are modified either as part
	 * of a planned upgrade or due to an irresponsible plugin.
	 *
	 * @param  string $source      the source table name
	 * @param  string $destination the destination table name
	 * @return array               a list of fields that can be safely copied from
	 *                             the source table to the destination table
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _get_sw_shadow_fields( $source, $destination )
	{
		$source_fields = ClassBlogs_Schema::get_table_column_names( $source );
		$dest_fields = ClassBlogs_Schema::get_table_column_names( $destination );
		return array_intersect( $dest_fields, $source_fields );
	}

	/**
	 * Copies data from the source table to the destination table.
	 *
	 * This is used to copy WordPress tables that have almost identical sitewide
	 * tables created to store their contents, such as posts or comments.  This
	 * can take an optional WHERE clause via $where, which will be added to
	 * the end of the SQL statement.
	 *
	 * If a WHERE clause is provided in $where, this function checks for any
	 * additional parameters passed and uses those to fill in any placeholder
	 * values in the WHERE clause.
	 *
	 * @param  int    $blog_id     the ID of the blog using the source table
	 * @param  string $source      the name of the source table
	 * @param  string $destination the name of the destination table
	 * @param  string $where       an optional WHERE clause to limit the data
	 * @param  mixed  $v,...       multiple optional params used for string
	 *                             substitution in the WHERE clause
	 * @return bool                whether or not the copy succeeded
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _copy_table( $blog_id, $source, $destination, $where = "" )
	{
		global $wpdb;

		// Build a list of fields to copy over, adding in the from-blog column
		$fields = $this->_get_sw_shadow_fields( $source, $destination );
		$insert_fields = $fields;
		$insert_fields[] = 'cb_sw_blog_id';
		$select_fields = $fields;
		$select_fields[] = $blog_id;

		// Assemble the base copy statement
		if ( $where ) {
			$where = 'WHERE ' . $where;
		}
		$prep_args = array(
			sprintf( "INSERT INTO %s (%s) SELECT %s FROM %s %s",
				$destination,
				implode( ", ", $insert_fields ),
				implode( ", ", $select_fields ),
				$source,
				$where ) );

		// Add in the WHERE-clause variables if a WHERE statement was passed
		if ( $where ) {
			$num_args = func_num_args();
			if ( $num_args > 4 ) {
				$args = func_get_args();
				for ( $i = 4; $i < $num_args; $i++ ) {
					$prep_args[] = $args[$i];
				}
			}
		}

		// Perform the actual copying of data
		$wpdb->query( call_user_func_array( array( $wpdb, 'prepare' ), $prep_args ) );
	}

	/**
	 * Aggregates all sitewide posts, comments and tags into separate tables.
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _sync_tables()
	{
		global $wpdb;
		$tag_counts = $tag_usage = $tag_origins = array();
		$blogs = $this->_get_usable_blog_ids();

		// Drop all data before proceeding
		$this->_clear_tables();
		$this->clear_site_cache();

		// Export the post, comment and tag data for each blog to our tables
		foreach ( $blogs as $blog_id ) {

			switch_to_blog( $blog_id );

			// Populate the posts table, ignoring any default posts
			$this->_copy_table(
				$blog_id,
				$wpdb->posts,
				$this->sw_tables->posts,
				"post_status = 'publish' AND post_type = 'post' AND post_title <> %s",
				ClassBlogs_Plugins_Aggregation_Settings::FIRST_POST_TITLE );

			// Populate the comments table, ignoring any default comments
			$this->_copy_table(
				$blog_id,
				$wpdb->comments,
				$this->sw_tables->comments,
				"comment_author <> %s",
				ClassBlogs_Plugins_Aggregation_Settings::FIRST_COMMENT_AUTHOR );

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
					INSERT INTO {$this->sw_tables->tags} (name, slug, count)
					VALUES (%s, %s, %s)",
					$meta['name'], $slug, $meta['count'] ) );
		}

		// Update the records of tag usage in the sitewide table
		foreach ( $blogs as $blog_id) {

			// Ignore the current blog if it has no tags registered
			if ( ! array_key_exists( $blog_id, $tag_origins ) ) {
				continue;
			}

			// Add any tag usage records on the current blog to the sitewide
			// master list, linking the tag with its ID in the sitewide table
			switch_to_blog( $blog_id );
			foreach ( $wpdb->get_results( "SELECT * FROM $wpdb->term_relationships" ) as $tag ) {
				if ( in_array( $tag->term_taxonomy_id, array_keys( $tag_origins[$blog_id] ) ) ) {
					$sw_tag_id = $wpdb->get_var( $wpdb->prepare( "
						SELECT term_id FROM {$this->sw_tables->tags}
						WHERE slug = %s",
						$tag_origins[$blog_id][$tag->term_taxonomy_id] ) );
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO {$this->sw_tables->tag_usage} (post_id, uses_tag, blog_id)
						VALUES (%s, %s, %s)",
						$tag->object_id, $sw_tag_id, $blog_id ) );
				}
			}
			restore_current_blog();
		}
	}

	/**
	 * Removes all data from the sitewide tables.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _clear_tables()
	{
		global $wpdb;
		foreach ( $this->sw_tables as $name => $table ) {
			$wpdb->query( "DELETE FROM $table" );
		}
	}

	/**
	 * Enables the admin interface for the sitewide aggregation features.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Sitewide Data Options', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Returns an array of excluded blog IDs from the POST data.
	 *
	 * @param  array $post the POST data from the admin form
	 * @return array       an array of blog IDs to exclude from aggregation
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _parse_excluded_blog_list( $post )
	{
		$blogs = array();
		foreach ( $post as $key => $value ) {
			if ( preg_match( '/^exclude_blog_(\d+)$/', $key, $matches ) ) {
				if ( 'on' == $value ) {
					$blogs[] = absint( $matches[1] );
				}
			}
		}
		return $blogs;
	}

	/**
	 * Handles the admin page logic for the plugin.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _admin_page()
	{

		// Perform an update action of some sort
		if ( $_POST ) {
			check_admin_referer( $this->get_uid() );

			// If we're just refreshing the sitewide data, do so now
			if ( array_key_exists( 'Refresh', $_POST ) ) {
				$synced = $this->_create_tables();
				if ( ! $synced ) {
					$this->_sync_tables();
				}
				ClassBlogs_Admin::show_admin_message( __( 'The sitewide data has been refreshed.', 'classblogs' ) );
			}

			// Otherwise update the plugin options
			else {
				$this->update_option( 'excluded_blogs', $this->_parse_excluded_blog_list( $_POST ) );
				$this->update_option( 'aggregation_enabled', $_POST['aggregation_enabled'] == 'enabled' );
				$this->_sync_tables();
				ClassBlogs_Admin::show_admin_message( __( 'Your options have been updated.', 'classblogs' ) );
			}
		}
?>
		<div class="wrap">

			<h2><?php _e( 'Sitewide Data Options', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This page allows you to manage the options for collecting sitewide data on posts, comments and tags from all the blogs on this site.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

				<h3><?php _e( 'Data Sources', 'classblogs' ); ?></h3>

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
		foreach ( ClassBlogs_Utils::get_all_blog_ids() as $blog_id ) {
			$details = get_blog_details( $blog_id, true );
			printf( '<li><input type="checkbox" id="%1$s" name="%1$s" %2$s /> <label for="%1$s"><strong>%3$s</strong> ( <a href="%4$s">%4$s</a> )</label></li>',
				'exclude_blog_' . $blog_id,
				( array_search( $blog_id, $excluded_blogs ) !== false ) ? 'checked="checked"' : "",
				$details->blogname,
				esc_url( $details->siteurl ) );
		}

		?>
							</ul>
						</td>
					</tr>

				</table>

				<h3><?php _e( 'Refresh Sitewide Data', 'classblogs' ); ?></h3>

				<p><?php _e(
					sprintf( 'If you find that the sitewide data does not accurately reflect the data in each blog, you can click the %1$s button below to rebuild the sitewide data tables.',
						'<strong>' . __( 'Refresh Sitewide Data', 'classblogs' ) . '</strong>' ), 'classblogs' ); ?></p>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit">
					<input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Sitewide Data Options', 'classblogs' ); ?>" />
					<input type="submit" name="Refresh" value="<?php _e( 'Refresh Sitewide Data', 'classblogs' ); ?>" />
				</p>

			</form>
		</div>
<?php
	}
}

ClassBlogs::register_plugin( 'sitewide_aggregator', new ClassBlogs_Plugins_Aggregation_Aggregator() );

?>
