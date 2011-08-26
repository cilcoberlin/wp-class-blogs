<?php

/**
 * The new-user configuration plugin
 *
 * This primarily attempts to create a sensible first and last name for a user
 * from their email address.
 *
 * @package Class Blogs
 * @subpackage NewUserConfigurationPlugin
 * @since 0.1
 */
class ClassBlogs_Plugins_NewUserConfiguration extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * A regular expression representing valid name-part separators
	 *
	 * @access private
	 * @var string
	 */
	const _NAME_PARTS_SEPARATORS = '/[\._]/';

	/**
	 * Registers the necessary plugin hooks with WordPress
	 */
	public function __construct()
	{

		parent::__construct();

		// Update a user's info when they are added
		add_action( 'user_register', array( $this, 'update_user_info' ) );
	}

	/**
	 * Updates the user's information once they have been added to WordPress
	 *
	 * @param int $user_id the database ID of the newly created user
	 *
	 * @since 0.1
	 */
	public function update_user_info( $user_id )
	{
		$this->_update_user_names( $user_id );
	}

	/**
	 * Updates a user's first and last name, nickname and display name
	 *
	 * This tries to intelligently create a first and last name for a newly
	 * added user based upon their email address, and then create a full name
	 * from these names, which is used as their nickname and display name.
	 *
	 * @param int $user_id the database ID of the newly created user
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _update_user_names( $user_id )
	{

		// Perform no customization if we're adding the initial admin account
		if ( 1 == $user_id ) { return; }

		$user_data = get_userdata( $user_id );
		if ( ! empty( $user_data ) ) {

			// Build the user's name from their email address
			$name_parts = $this->get_name_parts_from_email( $user_data->user_email );
			$first_name = $name_parts['first'];
			$last_name  = $name_parts['last'];
			$full_name  = "$first_name $last_name";

			// Set a user's nickname and first and last name
			update_user_meta( $user_id, 'first_name', $first_name );
			update_user_meta( $user_id, 'last_name', $last_name );
			update_user_meta( $user_id, 'nickname', $full_name );

			// Set the user's public display name to be their full name
			global $wpdb;
			$wpdb->query( $wpdb->prepare(
				"UPDATE $wpdb->users SET display_name = %s WHERE ID = %d",
				$full_name, $user_id ) );
		}
	}

	/**
	 * Gets a user's first and last name from an email address
	 *
	 * This looks for what appear to be parts of a name, which are assumed to
	 * be separated by either a period or an underscore.  Hyphens are assumed
	 * to be part of a hyphenated first or last name.
	 *
	 * If they have what appears to be a first.last@example.com address, their
	 * first name will be "First" and their last name will be "Last".
	 *
	 * If they have what appears to be a first-other.middle-name.other.last@example.com
	 * address, their first name will be "First-Other" and their last name will be
	 * "Middle-Name Other Last".
	 *
	 * Any other email address format will be assumed to be in a flast@example.com
	 * format, being the first letter of their first name and an arbitrary number
	 * of letters of their last name.  In this case, their first name will be "F."
	 * and their last will be "Last".
	 *
	 * @param  string $email a user's full email address
	 * @return array         a two-item array containing a string of the user's
	 *                       first name in the 'first' key and a string of the
	 *                       user's last name in the 'last' key
	 *
	 * @since 0.1
	 */
	public function get_name_parts_from_email( $email )
	{
		// Get the parts of the user's name from their email address,
		// counting any period-separated strings as name parts
		$name_parts = preg_split( self::_NAME_PARTS_SEPARATORS, preg_replace( '/\@.*\.\w+$/', "", strtolower( $email ) ) );

		// Create a user's first and last name from the available name parts,
		// making a full first and last name from an array with multiple
		// values, or faking a first name from a guessed first initial if no
		// name parts were found in the email
		if ( count( $name_parts ) > 1 ) {
			$first_name = array_shift( $name_parts );
			$last_name  = join( " ", $name_parts );
		} else {
			$base_name  = preg_replace( '/[^\w]/', "", $name_parts[0] );
			$first_name = substr( $base_name, 0, 1 ) . '.';
			$last_name  = substr( $base_name, 1 );
		}

		// Return a titlecased version of the first and last name
		return array(
			'first' => $this->_titlecase_name( $first_name ),
			'last'  => $this->_titlecase_name( $last_name ) );
	}

	/**
	 * Titlecases a user's first or last name
	 *
	 * This applies a standard titlecase filter to the given name, and attempts
	 * to titlecase each part of a multi-part name.  For example, if this
	 * function were to receive a name of "middle-other last", a hypothetical
	 * user's last name, it would return "Middle-Other Last".
	 *
	 * @param  string $name a lowercase name as guessed from a user's email,
	 *                      which might contain numbers or symbols
	 * @return string       a titlecased version of the name
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _titlecase_name( $name )
	{
		$name = preg_replace( '/[^a-z\-\s\.]/', "", $name );
		$name = preg_replace( '/\s{2,}/', " ", $name );
		return preg_replace_callback( '/(^\w)|(\-\w)|(\s\w)/', array( $this, '_titlecase_name_parts' ), $name );
	}

	/**
	 * Titlecases parts of names as found by a name part regex
	 *
	 * This is the callback function used to titlecase name parts found by the
	 * _titlecase_name function.  When given a name of "first", it returns "First".
	 *
	 * @param  array $name_matches a matches array as returned by a
	 *                             preg_replace_callback function, with the
	 *                             array containing strings
	 * @return string              a titlecased version of the matched string
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _titlecase_name_parts( $name_matches )
	{
		return strtoupper( $name_matches[0] );
	}
}

ClassBlogs::register_plugin( 'new_user_configuration', new ClassBlogs_Plugins_NewUserConfiguration() );

?>
