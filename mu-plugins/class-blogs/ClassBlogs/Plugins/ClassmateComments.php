<?php

/**
 * The classmate comments plugin
 *
 * This automatically approves any comments left by a logged-in student on the
 * blog of another student in the class for which the class blog exists.
 *
 * @package ClassBlogs_Plugins
 * @subpackage ClassmateComments
 * @since 0.1
 */
class ClassBlogs_Plugins_ClassmateComments extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * Registers the auto-approval comment hook
	 */
	public function __construct()
	{
		add_action( 'wp_insert_comment', array( $this, '_approve_classmate_comments' ), 10, 2 );
	}

	/**
	 * Automatically approve any comments left by a classmate
	 *
	 * @param int    $id      the database ID of the comment
	 * @param object $comment the saved comment object
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _approve_classmate_comments( $id, $comment )
	{
		if ( ! $comment->comment_approved ) {
			if ( $comment->user_id || get_user_by_email( $comment->comment_author_email ) ) {
				$comment->comment_approved = 1;
				wp_update_comment( (array) $comment );
			}
		}
	}
}

ClassBlogs::register_plugin( 'classmate_comments', new ClassBlogs_Plugins_ClassmateComments() );

?>
