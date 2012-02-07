<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Utils.php' );

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
	/** Registers hooks to add the student pseudonym admin page. */
	public function __construct()
	{
		parent::__construct();

		// Add the pseudonym page to any student blog's admin side
		if ( ClassBlogs_Utils::on_student_blog_admin() ) {
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
			__( 'Use A Pseudonym', 'classblogs' ),
			__( 'Use A Pseudonym', 'classblogs' ),
			'manage_options',
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
		$first_name = $current_user->user_firstname;
		$last_name = $current_user->user_lastname;
		$username = $current_user->user_login;
		$blog_url = home_url();

		// Validate the pseudonym
		$username_valid = true;
		if ( $_POST ) {

			// Apply the pseudonym to the user and their blog if the username
			// does not conflict with an existing user or blog
			check_admin_referer( $this->get_uid() );
			$username = ClassBlogs_Utils::sanitize_user_input( $_POST['new_username'] );
			$first_name = ClassBlogs_Utils::sanitize_user_input( $_POST['new_first_name'] );
			$last_name = ClassBlogs_Utils::sanitize_user_input( $_POST['new_last_name'] );
			$username_valid = $this->_validate_username( $username );
			if ( $username_valid ) {
				global $blog_id;
				$current_user = wp_get_current_user();
				$this->_apply_pseudonym( $current_user->ID, $blog_id, $username, $first_name, $last_name );

				// Get information on the applied pseudonym
				$blog_url = home_url();
				$new_info = array(
					__( 'First Name', 'classblogs' ) => esc_html( $first_name ),
					__( 'Last Name', 'classblogs' ) => esc_html( $last_name ),
					__( 'Username', 'classblogs' ) => esc_html( $username ),
					__( 'Blog URL', 'classblogs' ) => sprintf( '<a href="%1$s">%1$s</a>', esc_url( $blog_url ) ) );

				// Display the updated information to the user
				$message = array( __( 'You are now blogging using a pseudonym.  Your new user information is as follows.', 'classblogs' ), '<dl>' );
				foreach ( $new_info as $key => $value ) {
					$message[] = sprintf( '<dt><strong>%s</strong></dt><dd>%s</dd>',
						$key, $value );
				}
				$message[] = '</dl>';
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
		<h2><?php _e( 'Use a Pseudonym', 'classblogs' ); ?></h2>

		<h3><?php _e( 'User Information', 'classblogs' ); ?></h3>

		<p id="student-pseudonym-instructions">
			<?php _e( 'You can use the form below to change how you appear on the blog if you do not wish for your real name to be associated with the content that you create.', 'classblogs' ); ?>
		</p>

		<form method="post" action="">

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'New Username', 'classblogs' ); ?></th>
					<td>
						<input type="text" name="new_username" id="new-username" value="<?php echo esc_attr( $username );  ?>" /><br />
						<label for="new-username"><?php _e( 'Changing this will change the username that you use to access your blog and the URL at which it can be found.', 'classblogs' ); ?></label>
						<hr />
						<label for="new-username">
							<?php printf( __( 'Your current username is %s', 'classblogs' ), '<strong>' . esc_html( $username ) . '</strong>' ); ?>
						</label><br />
						<label for="new-username">
							<?php printf( __( 'Your current blog URL is %s', 'classblogs' ), sprintf( '<a href="%1$s">%1$s</a>', esc_url( $blog_url ) ) ); ?>
						</label><br />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'New First Name', 'classblogs' ); ?></th>
					<td>
						<input type="text" name="new_first_name" id="new-first-name" value="<?php echo esc_attr( $first_name );  ?>"/><br />
						<label for="new-first-name">
							<?php printf( __( 'Your current first name is %s', 'classblogs' ), '<strong>' . esc_html( $first_name ) . '</strong>' ); ?>
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'New Last Name', 'classblogs' ); ?></th>
					<td>
						<input type="text" name="new_last_name" id="new-last-name" value="<?php echo esc_attr( $last_name );  ?>" /><br />
						<label for="new-last-name">
							<?php printf( __( 'Your current last name is %s', 'classblogs' ), '<strong>' . esc_html( $last_name ) . '</strong>' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php wp_nonce_field( $this->get_uid() ); ?>
			<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Use this Pseudonym', 'classblogs' ); ?>" /></p>
		</form>

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

		// If the user name will not change, skip validation
		if ( $username === wp_get_current_user()->user_login ) {
			return true;
		}

		// Check for conflicts with other users and blogs
		global $wpdb;
		$valid = validate_username( $username );
		if ( $valid ) {
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
	 * @param  string $new_first_name the student's new first name
	 * @param  string $new_last_name  the student's new last name
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _apply_pseudonym( $user_id, $blog_id, $new_username, $new_first_name, $new_last_name )
	{
		global $wpdb;
		switch_to_blog( $blog_id );
		$old_url = trailingslashit( home_url() );

		// Update the student's username and their display-name information
		$full_name = "$new_first_name $new_last_name";
		update_user_meta( $user_id, 'first_name', $new_first_name );
		update_user_meta( $user_id, 'last_name', $new_last_name );
		update_user_meta( $user_id, 'nickname', $new_first_name );
		wp_update_user( array(
			'ID' => $user_id,
			'user_nicename' => str_replace( ' ', '-', $new_username ),
			'display_name' =>  $full_name ) );
		$wpdb->update(
			$wpdb->users,
			array( 'user_login' => $new_username ),
			array( 'ID' => $user_id ),
			array( '%s' ),
			array( '%d' ) );
		update_option( 'blogname', $full_name );

		// Update the student's blog URL
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

		restore_current_blog();
	}
}

ClassBlogs::register_plugin(
	'student_pseudonym',
	'ClassBlogs_Plugins_StudentPseudonym',
	__( 'Student Pseudonym', 'classblogs' ),
	__( 'Adds a page to the Users group on the admin side of any student blog that allows them to quickly change their username, blog URL and display name.', 'classblogs' )
);

?>
