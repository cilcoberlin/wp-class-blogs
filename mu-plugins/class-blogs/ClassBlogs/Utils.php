<?php

/**
 * Utility functions used by the class blogs plugin suite
 *
 * @package ClassBlogs
 * @since 0.1
 */
class ClassBlogs_Utils
{

	/**
	 * The database ID of the root blog
	 *
	 * @var int
	 */
	const ROOT_BLOG_ID = 1;

	/**
	 * Returns true if the current page is on the root blog
	 *
	 * @return bool whether or not the page is on the root blog
	 *
	 * @since 0.1
	 */
	public static function is_root_blog()
	{
		global $blog_id;
		return self::ROOT_BLOG_ID == $blog_id;
	}

	/**
	 * Returns true if the current page is the admin side of the root blog and
	 * the logged-in user has administrator privileges on the blog.
	 *
	 * @return bool whether or not the current user is a root-blog admin logged
	 *              in to the admin page for the root blog
	 *
	 * @since 0.1
	 */
	public static function on_root_blog_admin()
	{
		return is_admin() && self::is_root_blog() && current_user_can( 'administrator' );
	}

	/**
	 * Gets the user IDs of any student users, which are defined in this sense
	 * as any user that is not an admin on the root blog.
	 *
	 * @return array a list of student user IDs
	 *
	 * @since 0.1
	 */
	public static function get_student_user_ids()
	{
		global $wpdb;
		$ids = array();

		// Add any users who are not admins on the root blog to the list
		switch_to_blog( self::ROOT_BLOG_ID );
		foreach ( $wpdb->get_results( "SELECT ID FROM $wpdb->users" ) as $user ) {
			if ( ! user_can( $user->ID, 'administrator' ) ) {
				$ids[] = $user->ID;
			}
		}
		restore_current_blog();

		return $ids;
	}

	/**
	 * Sanitizes any input coming from a user
	 *
	 * @param  string $input text of the user's input in a form field
	 * @return string        the sanitized version of the input
	 */
	public static function sanitize_user_input( $input )
	{
		return strip_tags( stripslashes( $input ) );
	}

	/**
	 * Returns the boolean equivalent of the given checkbox value
	 *
	 * Since checkboxes that have been checked are represented in the POST data
	 * as 'on', any value other than that will evaluate to false.
	 *
	 * @param  string $value the value of a checkbox
	 * @return bool          the boolean equivalent of the value
	 *
	 * @since 0.1
	 */
	public static function checkbox_as_bool( $value ) {
		return $value == 'on';
	}

	/**
	 * Creates a plain-text excerpt for the post's content
	 *
	 * The excerpt returned will have all shortcodes and HTML markup stripped
	 * out, and will be at most the number of words requested, with words
	 * being naively defined as any whitespace-separated strings.
	 *
	 * @param  string $content    the full content of the post
	 * @param  int    $word_count the maximum number of words to use
	 * @return string             the post excerpt
	 *
	 * @since 0.1
	 */
	public static function make_post_excerpt( $content, $word_count )
	{
		$content = strip_shortcodes( strip_tags( $content ) );
		$words = preg_split( '/\s+/', $content );
		if ( count( $words ) <= $word_count ) {
			return $content;
		} else {
			$excerpt = join( ' ', array_slice( $words, 0, $word_count ) );
			if ( '.' == substr( $excerpt, -1) ) {
				$excerpt = substr( $excerpt, 0, -1 );
			}
			return $excerpt . '&hellip;';
		}
	}

	/**
	 * Returns a user-generated formatting string with the variables replaced
	 *
	 * This is used to process any user-created string containing variables of
	 * the form %variable_name% ('%' is the default sigil), which is often used
	 * in the widgets that are part of the class blogs suite.
	 *
	 * @param  string $format the formatting string
	 * @param  array  $lookup a lookup table of the available variables
	 * @param  string $prefix the prefix for the CSS wrapper classes
	 * @param  string $sigil  the character surrounding any variables
	 * @return string         the formatting string with variables replaced
	 *
	 * @since 0.1
	 */
	public static function format_user_string( $format, $lookup, $prefix = "", $sigil = '%' )
	{
		if ( $prefix ) {
			$prefix .= '-';
		}
		foreach ( $lookup as $variable => $value ) {
			$format = str_replace(
				sprintf( '%1$s%2$s%1$s', $sigil, $variable ),
				sprintf( '<span class="%s%s format">%s</span>', $prefix, $variable, $value ),
				$format );
		}
		return $format;
	}

	/**
	 * Returns the URL to the class blogs media directory
	 *
	 * This takes an argument of the name of the media-type directory to add on
	 * to the path, such as a 'css' or 'js' directory.
	 *
	 * @param  string $media_type the name of a media type to append to the URL
	 * @return string             the path to the class blogs media directory
	 *
	 * @access private
	 * @since 0.1
	 */
	private static function _get_plugin_media_url( $media_type )
	{
		return implode( "/", array(
			WPMU_PLUGIN_URL,
			ClassBlogs_Settings::SRC_DIR_NAME,
			ClassBlogs_Settings::MEDIA_DIR_NAME,
			$media_type ) ) . "/";
	}

	/**
	 * Returns the URL to the class blogs JavaScript directory
	 *
	 * @return string the URL of the class blogs JavaScript directory
	 *
	 * @since 0.1
	 */
	public static function get_plugin_js_url()
	{
		return self::_get_plugin_media_url( ClassBlogs_Settings::MEDIA_JS_DIR_NAME );;
	}

	/**
	 * Returns the URL to the class blogs CSS directory
	 *
	 * @return string the URL of the class blogs CSS directory
	 *
	 * @since 0.1
	 */
	public static function get_plugin_css_url()
	{
		return self::_get_plugin_media_url( ClassBlogs_Settings::MEDIA_CSS_DIR_NAME );;
	}

	/**
	 * Returns the name for a table used as part of the class blogs suite
	 *
	 * This simply appends the class blogs prefix to the given table name
	 *
	 * @param  string $table the base table name
	 * @return string        the prefixed table name
	 *
	 * @since 0.1
	 */
	public static function make_table_name( $table )
	{
		return ClassBlogs_Settings::TABLE_PREFIX . $table;
	}

}

?>
