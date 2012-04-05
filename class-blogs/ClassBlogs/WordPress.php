<?php

ClassBlogs::require_cb_file( 'Settings.php' );

/**
 * WordPress abstraction functions.
 *
 * This abstraction layer exists primarily to allow the class blogs suite to
 * run either in multisite mode, where each student is given a dedicated blog,
 * or in standard mode, where each student simply has a user account.
 *
 * @package ClassBlogs
 * @subpackage WordPress
 * @since 0.4
 */
class ClassBlogs_WordPress
{

	/**
	 * Switches to a blog with the given ID.
	 *
	 * This switches to the given blog when in multisite mode, or does nothing
	 * when running a normal installation with only a single blog defined.
	 *
	 * @param int $blog_id the ID of a blog
	 *
	 * @since 0.4
	 */
	public static function switch_to_blog( $blog_id )
	{
		if ( function_exists( 'switch_to_blog' ) ) {
			switch_to_blog( $blog_id );
		}
	}

	/**
	 * Restores the current blog.
	 *
	 * This restores the current blog when in multisite mode, or does nothing
	 * when running a normal installation where the current blog is static.
	 *
	 * @since 0.4
	 */
	public static function restore_current_blog()
	{
		if ( function_exists( 'restore_current_blog' ) ) {
			restore_current_blog();
		}
	}

	/**
	 * Gets an option on a blog.
	 *
	 * This gets the given option on the requested blog when in mulsite mode,
	 * or returns the given option on the root blog when running a standard
	 * installation with only one blog.
	 *
	 * @param  int    $blog_id the ID of a blog
	 * @param  string $option  the name of an option
	 * @return mixed           the value of the option
	 *
	 * @since 0.4
	 */
	public static function get_blog_option( $blog_id, $option )
	{
		if ( function_exists( 'get_blog_option' ) ) {
			return get_blog_option( $blog_id, $option );
		} else {
			return get_option( $option );
		}
	}

	/**
	 * Gets the URL for the blog with the given ID.
	 *
	 * This gets the URL for the requested blog when in multisite mode, or
	 * for the root blog when running a standard installation.
	 *
	 * @param  int    $blog_id the ID of a blog
	 * @return string          the URL for the blog
	 *
	 * @since 0.4
	 */
	public static function get_blogaddress_by_id( $blog_id )
	{
		if ( function_exists( 'get_blogaddress_by_id' ) ) {
			return get_blogaddress_by_id( $blog_id );
		} else {
			return home_url();
		}
	}

	/**
	 * Gets the permalink for the given post on the given blog.
	 *
	 * This gets the permalink for the post on the requested blog when in
	 * multisite mode, or on the root blog when running a standard installation.
	 *
	 * @param  int    $blog_id the ID of a blog
	 * @param  int    $post_id the ID of a post on the blog
	 * @return string          the URL for the blog
	 *
	 * @since 0.4
	 */
	public static function get_blog_permalink( $blog_id, $post_id )
	{
		if ( function_exists( 'get_blog_permalink' ) ) {
			return get_blog_permalink( $blog_id, $post_id );
		} else {
			return get_permalink( $post_id );
		}
	}

	/**
	 * Determines whether or not the given user has the given permission.
	 *
	 * @param  int    $user_id    the ID of a user
	 * @param  string $capability a role or capability
	 * @return bool               whether the user has the permission
	 *
	 * @since 0.5
	 */
	public static function user_can( $user, $capability )
	{
		if ( function_exists( 'user_can' ) ) {
			return user_can( $user, $capability );
		} else {

			// Taken from the source of WordPress >= 3.1
			if ( ! is_object( $user ) ) {
				$user = new WP_User( $user );
			}
			if ( ! $user || ! $user->ID ) {
				return false;
			}
			$args = array_slice( func_get_args(), 2 );
			$args = array_merge( array( $capability ), $args );
			return call_user_func_array( array( &$user, 'has_cap' ), $args );
		}
	}
}

?>
