<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Utils.php' );
ClassBlogs::require_cb_file( 'WordPress.php' );

/**
 * A plugin that allows a student to blog using a pseudonym.
 *
 * This provides an admin page available to any students that allows them to
 * change their blog URL, their username and their display name in order to
 * blog under a pen name.  If they choose to change their username, their blog's
 * URL is also updated to match the change, provided that it doesn't conflict
 * with an existing user or blog.
 *
 * @package ClassBlogs_Plugins
 * @subpackage StudentPseudonym
 * @since 0.1
 */
class ClassBlogs_Plugins_StudentPseudonym extends ClassBlogs_BasePlugin
{
	/**
	 * The default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.4
	 */
	protected $default_options = array(
		'changed_users' => array()
	);

	/** Registers hooks to add the student pseudonym admin page. */
	public function __construct()
	{
		parent::__construct();
		add_action( 'plugins_loaded', array( $this, '_register_hooks' ) );
	}

	/**
	 * Register hooks to show the admin page when a student who has not yet
	 * changed their username is logged in to the admin side.
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _register_hooks()
	{
		$current_user = wp_get_current_user();
		if ( $current_user->ID > 0 && ClassBlogs_Utils::on_student_blog_admin() &&
		     ! array_key_exists( $current_user->user_login, $this->get_option( 'changed_users' ) ) ) {
			add_action( 'admin_menu', array( $this, '_add_admin_page' ) );
		}
	}

	/**
	 * Adds the pseudonym admin page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _add_admin_page( $admin )
	{
		add_users_page(
			__( 'Change Username', 'classblogs' ),
			__( 'Change Username', 'classblogs' ),
			'edit_posts',
			$this->get_uid(),
			array( $this, '_admin_page' ) );
	}

	/**
	 * Handles the logic to display the pseudonym admin page to a student.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _admin_page()
	{
		// Get information on the user and their blog URL
		$current_user = wp_get_current_user();
		$username = $current_user->user_login;
		$blog_url = home_url();

		// Validate the pseudonym
		$username_valid = true;
		if ( $_POST ) {

			// Apply the pseudonym to the user and their blog if the username
			// does not conflict with an existing user or blog
			check_admin_referer( $this->get_uid() );
			$username = ClassBlogs_Utils::sanitize_user_input( $_POST['new_username'] );
			$username_valid = $this->_validate_username( $username );
			if ( $username_valid ) {
				global $blog_id;
				$current_user = wp_get_current_user();
				$this->_apply_pseudonym( $current_user->ID, $blog_id, $username );

				// Display the updated information to the user
				$blog_url = home_url();
				$message = array( __( 'You successfully changed your username.  Your new user information is as follows.', 'classblogs' ), '<p>' );
				$message[] = sprintf( '<strong>%s</strong><br />%s<br /><br />',
					__( 'Username', 'classblogs'),
					esc_html( $username ) );
				if ( ClassBlogs_Utils::is_multisite() ) {
					$message[] = sprintf( '<strong>%s</strong><br />%s<br /><br />',
						__( 'Blog URL', 'classblogs'),
						sprintf( '<a href="%1$s">%1$s</a>', esc_url( $blog_url ) ) );
				}
				$message[] = '</p>';
				ClassBlogs_Admin::show_admin_message( implode( "\n", $message ) );
			}

			// If there are errors, show them to the user
			else {
				if ( ! $username ) {
					$error = __( 'You cannot have a blank username.', 'classblogs' );
				} else {
					$error = sprintf( __( 'The username %s is invalid or conflicts with another user or blog.  Please choose a different username.', 'classblogs' ),
						'<strong>' . esc_html( $username ) . '</strong>' );
				}
				ClassBlogs_Admin::show_admin_error( $error );
			}
		}
?>

	<div class="wrap">

		<div id="icon-users" class="icon32"></div>
		<h2><?php _e( 'Change Username', 'classblogs' ); ?></h2>

		<p id="student-pseudonym-instructions">
			<?php _e( 'If you have already changed your display name but still wish for there to be no trace of your actual identity on the blog, you can use this page to change the username that you use to log in to the blog.', 'classblogs' ); ?>
		</p>

		<form method="post" action="" id="cb-username-form">

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'New Username', 'classblogs' ); ?></th>
					<td>
						<input type="text" name="new_username" id="new-username" /><br />
						<label for="new-username"><?php _e( 'Changing this will change the username that you use to access your blog and the URL at which it can be found.', 'classblogs' ); ?></label>
						<hr />
						<label for="new-username">
							<?php printf( __( 'Your current username is %s', 'classblogs' ), '<strong>' . esc_html( $current_user->user_login ) . '</strong>' ); ?>
						</label><br />
						<?php if ( ClassBlogs_Utils::is_multisite() ): ?>
							<label for="new-username">
								<?php printf( __( 'Your current blog URL is %s', 'classblogs' ), sprintf( '<a href="%1$s">%1$s</a>', esc_url( $blog_url ) ) ); ?>
							</label>
						<?php endif; ?>
					</td>
				</tr>
			</table>

			<?php wp_nonce_field( $this->get_uid() ); ?>
			<p class="submit"><input class="button-primary" type="submit" name="Submit" value="<?php _e( 'Change my Username', 'classblogs' ); ?>" /></p>
			<strong style="color: #a00">You will only be able to change your username once</strong>

		</form>

		<script type="text/javascript">
			jQuery("#cb-username-form").submit(function() {
				return confirm( "<?php _e( 'You can only change your username once.  Are you sure that you wish to change it now?', 'classblogs' ); ?>" );
			});
		</script>

	</div>
<?php
	}

	/**
	 * Verifies that the username is valid and doesn't conflict with other blogs.
	 *
	 * @param  string $username the new username to use as a pseudonym
	 * @return bool             whether or not the username is valid
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _validate_username( $username )
	{
		// Abort if the username is blank
		if ( ! $username ) {
			return false;
		}

		// Check for conflicts with other users and blogs
		global $wpdb;
		$valid = validate_username( $username ) && ! username_exists( $username );
		if ( $valid && ClassBlogs_Utils::is_multisite() ) {
			$site = get_current_site();
			$new_path = trailingslashit( $site->path . $username );
			$valid = ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->blogs WHERE path=%s", $new_path ) );
		}
		return $valid;
	}

	/**
	 * Applies the student's chosen pseudonym.
	 *
	 * This updates the student's user and blog information, then attempts to
	 * update any references to the old URL, such as those used by media embedded
	 * into posts on the blog whose URL is being changed.
	 *
	 * The inputs for this function will have already been validated by another
	 * method, so it can be assumed that they are valid.
	 *
	 * @param  int    $user_id        the student's user ID
	 * @param  int    $blog_id        the ID of the student's primary blog
	 * @param  string $new_username   the student's new username
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _apply_pseudonym( $user_id, $blog_id, $new_username )
	{
		global $wpdb;

		// Update the student's username
		$wpdb->update(
			$wpdb->users,
			array( 'user_login' => $new_username ),
			array( 'ID' => $user_id ),
			array( '%s' ),
			array( '%d' ) );

		// Deal with the implications of the updated username in multisite
		if ( ClassBlogs_Utils::is_multisite() ) {
			ClassBlogs_WordPress::switch_to_blog( $blog_id );
			$old_url = trailingslashit( home_url() );

			// Update the blog URL to reflect their new username
			$site = get_current_site();
			$new_path = trailingslashit( $site->path . $new_username );
			$new_url = 'http://' . $site->domain . $new_path;
			update_option( 'siteurl', $new_url );
			update_option( 'home', $new_url );
			update_blog_details( $blog_id, array( 'path' => $new_path ) );
			delete_option( 'rewrite_rules' );

			// Replace any occurrences of the old URL in the blog's posts
			$referring_posts = $wpdb->get_results( "
				SELECT ID, post_content FROM $wpdb->posts
				WHERE post_content LIKE '%%" . like_escape( $old_url ) . "%%' " );
			foreach ( $referring_posts as $post ) {
				$wpdb->update(
					$wpdb->posts,
					array( 'post_content' => str_replace( $old_url, $new_url, $post->post_content ) ),
					array( 'ID' => $post->ID ),
					array( '%s' ),
					array( '%d' ) );
			}
			ClassBlogs_WordPress::restore_current_blog();
		}

		// Flag that the user has changed their username
		$changed = $this->get_option( 'changed_users' );
		$changed[$new_username] = true;
		$this->update_option( 'changed_users', $changed );
	}
}

ClassBlogs::register_plugin(
	'student_pseudonym',
	'ClassBlogs_Plugins_StudentPseudonym',
	__( 'Student Pseudonym', 'classblogs' ),
	__( 'Adds a student admin page that allows them to change their username.', 'classblogs' )
);

?>
