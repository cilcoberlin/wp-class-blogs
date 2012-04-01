<?php

ClassBlogs::require_cb_file( 'BasePlugin.php' );

/**
 * A plugin that adds a link to the bottom of the account-activation email that
 * will allow a user to create a gravatar associated with their email address.
 *
 * @package ClassBlogs_Plugins
 * @subpackage GravatarSignup
 * @since 0.1
 */
class ClassBlogs_Plugins_GravatarSignup extends ClassBlogs_BasePlugin
{

	/**
	 * The URL at which a user can sign up for a Gravatar account.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _GRAVATAR_SIGNUP_URL = 'http://en.gravatar.com/site/signup/';

	/**
	 * Registers the necessary plugin hooks with WordPress.
	 */
	public function __construct()
	{

		parent::__construct();

		// Adds a link to sign up for a Gravatar account to the welcome email
		add_filter( 'update_welcome_email', array( $this, '_update_new_blog_email' ), 100, 6 );
		add_filter( 'update_welcome_user_email', array( $this, '_update_new_user_email' ), 100, 4 );
	}

	/**
	 * Updates the contents of a new-user welcome email.
	 *
	 * @param  string $email    the text of the welcome email
	 * @param  int    $user_id  the database ID of the newly created user
	 * @param  string $password the user's password
	 * @param  object $meta
	 * @return string           the new-user email with a Gravatar signup link
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _update_new_user_email( $email, $user_id, $password, $meta )
	{
		return $this->_add_gravatar_signup_link_to_message( $user_id, $email );
	}

	/**
	* Updates the contents of a new-blog welcome email.
	*
	* @param  string $email    the text of the welcome email
	* @param  int    $blog_id  the database ID of the newly created blog
	* @param  int    $user_id  the database ID of the newly created user
	* @param  string $password the user's password
	* @param  string title     the title of the new blog
	* @param  object $meta
	* @return string           the new-blog email with a Gravatar signup link
	*
	* @access private
	* @since 0.1
	*/
	public function _update_new_blog_email( $email, $blog_id, $user_id, $password, $title, $meta )
	{
		return $this->_add_gravatar_signup_link_to_message( $user_id, $email );
	}

	/**
	 * Adds the gravatar signup link to a welcome message.
	 *
	 * The added link will appear, along with a message, at the very bottom
	 * of the welcome email.
	 *
	 * @param  int    $user_id the ID of a WordPress user
	 * @param  string $message the text of a welcome email sent to a user
	 * @return string          the message with a Gravatar signup link added
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _add_gravatar_signup_link_to_message( $user_id, $message )
	{

		// Get the user, aborting if none can be found
		$user_data = get_userdata( $user_id );
		if ( empty( $user_data ) ) {
			return $message;
		}

		// Add the messge and link to the Gravatar signup page to the email
		$parts = array(
			"\n",
			__( 'To keep track of your posts on the class blog, you should configure a Gravatar, which is an image of your choosing that will appear next to any posts or comments that you create.  You can sign up for a Gravatar for free by visiting the following URL:', 'classblogs' ),
			$this->_get_gravatar_signup_url( $user_data ),
			"\n" );
		return $message . implode( "\n", $parts );
	}

	/**
	 * Returns the URL at which the given user can sign up for a gravatar.
	 *
	 * @param  object $user an instance of a WordPress user
	 * @return string       the URL at which the user can sign up for a Gravatar
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_gravatar_signup_url( $user )
	{
		return self::_GRAVATAR_SIGNUP_URL . urlencode($user->user_email);
	}
}

ClassBlogs::register_plugin(
	'gravatar_signup',
	'ClassBlogs_Plugins_GravatarSignup',
	__( 'Gravatar Signup', 'classblogs' ),
	__( 'Adds a link for the user to sign up for a gravatar to each account activation email sent out.', 'classblogs' )
);

?>
