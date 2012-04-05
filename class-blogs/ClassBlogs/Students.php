<?php

ClassBlogs::require_cb_file( 'WordPress.php' );

/**
 * Logic for determining student identity and blog URLs.
 *
 * When running WordPress as a single-site installation where students are added
 * as users to the same blog on which the professor is a user, a student will be
 * any user who is not an administrator and not a subscriber.  Their blog URL
 * will be the author archive view associated with their user account.
 *
 * When running WordPress in multisite mode, a student is any user who is the
 * only adminstrator on a blog that is not the root blog.  Their blog URL will
 * be the URL of that blog.
 *
 * An example of using the functions of this class is as follows:
 *
 *     // Two students are added to the blog with user IDs of 2 and 3, and the
 *     // admin user has an ID of 1.
 *     $student_ids = ClassBlogs_Students::get_student_user_ids();
 *     assert( in_array( 2, $student_ids ) );
 *     assert( in_array( 3, $student_ids ) );
 *     assert( count( $student_ids ) === 2 );
 *     assert( ! ClassBlogs_Students::user_is_student( 1 ) );
 *     assert( ClassBlogs_Students::user_is_student( 2 ) );
 *
 *     // A blog with an ID of 2 is created for a student with an ID of 3.  This
 *     // blog is called 'Example' and is located at http://www.example.com.
 *     $blogs = ClassBlogs_Students::get_student_blogs();
 *     assert( count( $blogs ) === 1 );
 *     assert( array_key_exists( 3, $blogs ) );
 *     $blog = $blogs[3];
 *     assert( $blog->blog_id === 2 );
 *     assert( $blog->url === 'http://www.example.com' );
 *     assert( ClassBlogs_Students::get_blog_url_for_student( 3 ) === $blog->url );
 *
 * @package ClassBlogs
 * @subpackage Students
 * @since 0.5
 */
class ClassBlogs_Students
{

	/**
	 * Get a list of the user IDs of every student user.
	 *
	 * @return array a list of all student user IDs
	 *
	 * @since 0.5
	 */
	public static function get_student_user_ids()
	{
		$students = array();

		// If in multisite mode, populate the list with all users who are the
		// sole administrator on a non-root blog.  If running in single-site
		// mode, add any users who can edit posts but who are not admins.
		if ( ClassBlogs_Utils::is_multisite() ) {
			foreach ( ClassBlogs_Utils::get_non_root_blog_ids() as $blog_id ) {
				$admins = get_users( 'blog_id=' . $blog_id . '&role=administrator' );
				if ( count( $admins ) == 1 ) {
					$admin = $admins[0];
					if ( ! in_array( $admin->ID, $students ) ) {
						$students[] = $admin->ID;
					}
				}
			}
		} else {
			foreach ( get_users() as $user ) {
				if ( ! ClassBlogs_WordPress::user_can( $user->ID, 'administrator' ) &&
				     ClassBlogs_WordPress::user_can( $user->ID, 'edit_posts' ) ) {
					$students[] = $user->ID;
				}
			}
		}

		return $students;
	}

	/**
	 * Returns a list of information about each student blog.
	 *
	 * The blog information will be returned as an array, with keys of user IDs.
	 * Each key's values will be an object with the following attributes:
	 *
	 *     blog_id - the possible ID of the blog
	 *     url     - the URL of the blog
	 *
	 * If running in multisite mode, `blog_id` will be the ID of the user's
	 * blog.  If running in single-site mode, however, it will be null, as only
	 * one blog exists, and the blog URL is of the author archive page.
	 *
	 * @return array information on all student blogs
	 *
	 * @since 0.5
	 */
	public static function get_student_blogs()
	{
		$blogs = array();

		// Cycle through every student
		foreach ( self::get_student_user_ids() as $student_id ) {

			// Add the first non-root blog on which the student is an admin if
			// running in multisite mode, or use their author archive URL if
			// running in single-site mode
			$blog_info = array();
			if ( ClassBlogs_Utils::is_multisite() ) {
				foreach ( ClassBlogs_Utils::get_non_root_blog_ids() as $blog_id ) {
					$admins = get_users( "blog_id=$blog_id&include=$student_id&role=administrator" );
					if ( count( $admins ) == 1 ) {
						$blog_info = array(
							'blog_id' => $blog_id,
							'url'     => ClassBlogs_WordPress::get_blogaddress_by_id( $blog_id ) );
						break;
					}
				}
			} else {
				$blog_info = array(
					'blog_id' => null,
					'url'     => get_author_posts_url( $student_id ) );
			}
			if ( ! empty( $blog_info ) ) {
				$blogs[$student_id] = (object) $blog_info;
			}
		}

		return $blogs;
	}

	/**
	 * Gets the URL of the blog associated with the given student.
	 *
	 * If no blog could be found, an empty string is returned.
	 *
	 * @param  int    $user_id the ID of a student user
	 * @return string          the URL of the student's blog
	 *
	 * @since 0.5
	 */
	public static function get_blog_url_for_student( $user_id )
	{
		$students = self::get_student_blogs();
		if ( array_key_exists( $user_id, $students ) ) {
			return $students[$user_id]->url;
		} else {
			return "";
		}
	}

	/**
	 * Determine whether the user with the given ID is a student.
	 *
	 * @param  int  $user_id the ID of a possible student
	 * @return bool          whether the user is a student
	 *
	 * @since 0.5
	 */
	public static function user_is_student( $user_id )
	{
		return in_array( $user_id, self::get_student_user_ids() );
	}
}

?>
