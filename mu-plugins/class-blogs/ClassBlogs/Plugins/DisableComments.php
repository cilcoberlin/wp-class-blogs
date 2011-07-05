<?php

/**
 * The comment-disabling plugin
 *
 * This provides a network-admin menu option that allows a user to disable
 * commenting on all blogs that are part of the class blog.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_DisableComments extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The base name of the table used to store pre-closing comment statuses
	 *
	 * @access private
	 * @var string
	 */
	const _COMMENTS_TABLE_BASE = "old_comments";

    /**
     * Default options for the plugin
     *
     * @access protected
     * @var array
     */
    protected static $default_options = array(
		'comments_disabled' => false
    );

    /**
     * The full name of the database table used to store comment statuses
     *
     * @access private
     * @var string
     */
    private $_comments_table;

    /**
     * Registers hooks to add a sitewide commenting page to the network admin
     */
    function __construct() {

    	parent::__construct();

		$this->_comments_table = ClassBlogs_Utils::make_table_name( self::_COMMENTS_TABLE_BASE );

    	if ( is_admin() ) {
    		add_action( 'network_admin_menu', array( $this, '_configure_admin_interface' ) );
    	}
    }

    /**
     * Creates a table for storing post comment statuses
     *
     * This table holds a record of the comment status of every post on the
     * site before sitewide comment disablign occurs.  This table is used to
     * restore posts to their original comment status if commenting is reenabled.
     *
     * @access private
     * @since 0.1
     */
    private function _maybe_create_comments_table()
    {
        global $wpdb;

        //  Create the comments table and clear all comment status records
        $wpdb->query( "
        	CREATE TABLE IF NOT EXISTS $this->_comments_table (
        	blog_id BIGINT(20) UNSIGNED NOT NULL,
        	post_id BIGINT(20) UNSIGNED NOT NULL,
        	comment_status VARCHAR(20) NOT NULL)" );
        $wpdb->query( "DELETE FROM $this->_comments_table" );
    }

    /**
     * Reenables comments if they have been disabled across the site
     *
     * @since 0.1
     */
    function reenable_comments()
    {

		global $wpdb, $current_blog;

		$statuses = $wpdb->get_results( $wpdb->prepare( "
			SELECT blog_id, post_id, comment_status
			FROM $this->_comments_table
			ORDER BY blog_id" ) );

		// Restore the comment status of any posts to what they were before
		// comments were disabled across the site
		$on_blog = $current_blog->blog_id;
		foreach ( $statuses as $status ) {
			if ( $on_blog != $status->blog_id ) {
				switch_to_blog( $status->blog_id );
			}
			$wpdb->query( $wpdb->prepare( "
				UPDATE $wpdb->posts SET comment_status = %s WHERE ID = %d",
				$status->comment_status, $status->post_id ) );
		}

		restore_current_blog();
    }

    /**
     * Disables commenting on all current and future posts for all site blogs
     *
     * @since 0.1
     */
    public function disable_comments()
    {
    	global $wpdb;
		$this->_maybe_create_comments_table();

		foreach ( $this->get_all_blog_ids() as $blog_id ) {

			switch_to_blog( $blog_id );

			//  Take note of the pre-closing comment status of all posts on the blog
			foreach ( $wpdb->get_results( "SELECT ID, comment_status FROM $wpdb->posts WHERE post_status = 'publish'" ) as $post ) {
				$wpdb->query( $wpdb->prepare( "
					INSERT INTO $this->_comments_table
					(blog_id, post_id, comment_status)
					VALUES (%d, %d, %s)",
					$blog_id, $post->ID, $post->comment_status ) );
			}

			//  Set comments on all posts on the blog to closed
			$wpdb->query( "UPDATE $wpdb->posts SET comment_status = 'closed' WHERE post_status = 'publish'" );

			restore_current_blog();
		}
    }

	/**
	 * Configures the network admin page
	 *
	 * @since 0.1
	 */
	public function _configure_admin_interface()
	{
		if ( is_super_admin() ) {
			$admin = ClassBlogs_Admin::get_admin();
			$admin->add_admin_page( $this->get_uid(), __( 'Disable Commenting', 'classblogs' ), array( $this, 'admin_page' ) );
		}
	}

	/**
	 * Handles the admin page logic for the sitewide posts plugin
	 *
	 * @since 0.1
	 */
	public function admin_page()
	{

		// Change the state of sitewide commenting if switching
		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$old_closed = $this->get_option( 'comments_disabled' );
			$new_closed = $_POST['comment_status'] === 'disabled';
			$this->update_option( 'comments_disabled', $new_closed );

			if ( $old_closed != $new_closed ) {
				if ( $new_closed ) {
					$this->disable_comments();
				} else {
					$this->reenable_comments();
				}
			}

			echo '<div id="message" class="updated fade"><p>' . __( 'Your sitewide commenting options have been updated', 'classblogs' ) . '</p></div>';
		}
?>
		<div class="wrap">

			<h2><?php _e( 'Disable Commenting', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This page allows you to disable commenting on every blog on this site, which includes both the root blog and the student blogs.  Any new posts will have commenting disabled by default, and all existing posts will not be able to receive comments.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Commenting on All Blogs is', 'classblogs' ); ?></th>
							<td>
								<input type="radio" name="comment_status" value="enabled" id="comments-enabled" <?php if ( ! $this->get_option( 'comments_disabled' ) ): ?>checked="checked"<?php endif; ?> />
								<label for="comments-enabled"><?php _e( 'Enabled', 'classblogs' ); ?></label>
								<input type="radio" name="comment_status" value="disabled" id="comments-disabled" <?php if ( $this->get_option( 'comments_disabled' ) ): ?>checked="checked"<?php endif; ?> />
								<label for="comments-disabled"><?php _e( 'Disabled', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Update Sitewide Commenting Options', 'classblogs' ); ?>" /></p>
			</form>
		</div>
<?php
	}
}

ClassBlogs::register_plugin( 'disable_comments', new ClassBlogs_Plugins_DisableComments() );

?>
