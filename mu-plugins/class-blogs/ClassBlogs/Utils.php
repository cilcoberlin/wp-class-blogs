<?php

ClassBlogs::require_cb_file( 'Settings.php' );

/**
 * Utility functions used by the class blogs plugin suite.
 *
 * These utility functions include helper functions to manage blog and student
 * IDs, shared formatting and HTML-parsing functions, and functions to get
 * URLs for media and WordPress pages.
 *
 * @package ClassBlogs
 * @subpackage Utils
 * @since 0.1
 */
class ClassBlogs_Utils
{

	/**
	 * Returns true if the current page is on the root blog.
	 *
	 * @return bool whether or not the page is on the root blog
	 *
	 * @since 0.1
	 */
	public static function is_root_blog()
	{
		global $blog_id;
		return ClassBlogs_Settings::get_root_blog_id() === (int) $blog_id;
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
	 * Returns true when a user is on the administrative side of a student blog.
	 *
	 * @return bool whether or not the current user is adminstering a student log
	 *
	 * @since 0.3
	 */
	public static function on_student_blog_admin()
	{
		return is_admin() && ! self::is_root_blog();
	}

	/**
	 * Returns true if the current user is an administrator on the root blog.
	 *
	 * @return bool whether the current user is an admin on the root blog
	 *
	 * @since 0.2
	 */
	public static function current_user_is_admin_on_root_blog()
	{
		return current_user_can_for_blog(
			ClassBlogs_Settings::get_root_blog_id(),
			'administrator' );
	}

	/**
	 * Functions identically to WordPress's native `is_page` function, but returns
	 * false if the current page is in a state where `is_page` could not be
	 * called without creating an error.
	 *
	 * @param  int  $page_id the ID of the page being checked
	 * @return bool          whether or not the current page matches the page ID
	 *
	 * @since 0.2
	 */
	public static function is_page( $page_id )
	{

		// This exists due to a flaw, likely due to some unknown structural
		// deficiency of the class blogs suite, that causes WordPress to spew
		// errors about a null post object when `is_page` is called on a page
		// where no posts were found.  Making the `post` property of the `wp_query`
		// object be a null post with all of the properties that are checked
		// for in the `is_page` call seems to prevent these errors from occurring.
		global $wp_query;
		if ( ! isset( $wp_query->post ) ) {
			$wp_query->post = (object) array(
				'ID' => null,
				'post_title' => null,
				'post_name' => null );
		}

		return is_page( $page_id );
	}

	/**
	 * Restores an arbitrary blog.
	 *
	 * This functions identically to `restore_current_blog`, but with the option
	 * of passing a blog ID to restore to.  This switches to that blog, then
	 * clears the switched stack and resets the switched state flag to false.
	 *
	 * @param int $blog_id the ID of the blog to restore to
	 *
	 * @since 0.2
	 */
	public static function restore_blog( $blog_id )
	{
		global $switched_stack, $switched;
		switch_to_blog( $blog_id );
		$switched_stack = array();
		$switched = false;
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
		switch_to_blog( ClassBlogs_Settings::get_root_blog_id() );
		foreach ( $wpdb->get_results( "SELECT ID FROM $wpdb->users" ) as $user ) {
			if ( ! user_can( $user->ID, 'administrator' ) ) {
				$ids[] = $user->ID;
			}
		}
		restore_current_blog();

		return $ids;
	}

	/**
	 * Returns a list of all blog IDs on the site.
	 *
	 * @return array a list of all blog IDs on the site
	 *
	 * @access protected
	 * @since 0.2
	 */
	public static function get_all_blog_ids()
	{
		global $wpdb;
		$blog_ids = array();

		$blogs = $wpdb->get_results( $wpdb->prepare( "
			SELECT blog_id FROM $wpdb->blogs
			WHERE site_id = %d AND archived = '0' AND deleted = '0'",
			$wpdb->siteid ) );
		foreach ( $blogs as $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
		return $blog_ids;
	}

	/**
	 * Sanitizes any input coming from a user.
	 *
	 * @param  string $input text of the user's input in a form field
	 * @return string        the sanitized version of the input
	 *
	 * @since 0.1
	 */
	public static function sanitize_user_input( $input )
	{
		return strip_tags( stripslashes( $input ) );
	}

	/**
	 * Returns the boolean equivalent of the given checkbox value.
	 *
	 * Since checkboxes that have been checked are represented in the POST data
	 * as 'on', any value other than that will evaluate to false.
	 *
	 * @param  array  $post  the POST data from a form
	 * @param  string $value the value of a checkbox
	 * @return bool          the boolean equivalent of the value
	 *
	 * @since 0.1
	 */
	public static function checkbox_as_bool( $post, $value ) {
		return array_key_exists( $value, $post ) && $post[$value] == 'on';
	}

	/**
	 * Creates a plain-text excerpt for the post's content.
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
	 * Returns a user-generated formatting string with the variables replaced.
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
				sprintf( '<span class="%s format">%s</span>',
					sanitize_html_class( $prefix . $variable ), esc_html( $value ) ),
				$format );
		}
		return $format;
	}

	/**
	 * Returns the URL of a class-blogs media directory containing the given
	 * type of media.
	 *
	 * This takes an argument of the name of the media-type directory to add on
	 * to the path, such as a 'css' or 'js' directory.
	 *
	 * The optional `$supports_dev` flag is used in conjuction with the value of
	 * `WP_DEBUG` to determine which media to use.  If the given media directory
	 * has a development branch and debugging mode is on, that will be used
	 * instead of the production branch.  This is used to serve optimized
	 * static media in a production environment but allow for easy development
	 * of uncompressed, commented media in a test environment.
	 *
	 * @param  string $media_type  the name of a media type to append to the URL
	 * @param  bool   $support_dev whether or not the media type supports a dev branch
	 * @return string              the path to the class blogs media directory
	 *
	 * @access private
	 * @since 0.2
	 */
	private static function _get_base_media_url( $media_type, $supports_dev=true )
	{
		return implode( "/", array(
			WPMU_PLUGIN_URL,
			ClassBlogs_Settings::SRC_DIR_NAME,
			ClassBlogs_Settings::MEDIA_DIR_NAME,
			( $supports_dev && WP_DEBUG ) ? 'devel' : 'prod',
			$media_type ) ) . "/";
	}

	/**
	 * Returns the URL of the class-blogs JavaScript directory.
	 *
	 * @return string the URL of the class-blogs JavaScript directory
	 *
	 * @since 0.2
	 */
	public static function get_base_js_url()
	{
		return self::_get_base_media_url( ClassBlogs_Settings::MEDIA_JS_DIR_NAME );
	}

	/**
	 * Returns the URL of the class-blogs CSS directory.
	 *
	 * @return string the URL of the class-blogs CSS directory
	 *
	 * @since 0.2
	 */
	public static function get_base_css_url()
	{
		return self::_get_base_media_url( ClassBlogs_Settings::MEDIA_CSS_DIR_NAME );
	}

	/**
	 * Returns the URL of the class-blogs images directory.
	 *
	 * @return string the URL of the class-blogs images directory
	 *
	 * @since 0.2
	 */
	public static function get_base_images_url()
	{
		return self::_get_base_media_url( ClassBlogs_Settings::MEDIA_IMAGES_DIR_NAME, false );
	}

	/**
	 * Returns the name for a table used as part of the class blogs suite.
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

	/**
	 * Gets a value from the $_SERVER global if it exists, or returns an empty
	 * string if it is not found.
	 *
	 * @param  string $name the name of a key in the $_SERVER array
	 * @return string       the value of the $_SERVER value, or an empty string
	 *
	 * @access private
	 * @since 0.1
	 */
	private static function _get_server_var( $name )
	{
		if ( array_key_exists( $name, $_SERVER ) ) {
			return $_SERVER[$name];
		} else {
			return "";
		}
	}

	/**
	 * Gets the full URL of the current page, including the query string.
	 *
	 * @return string the absolute URL of the current page
	 *
	 * @since 0.1
	 */
	public static function get_current_url()
	{
		// Build the base URL
		$request = self::_get_server_var( 'REQUEST_URI' );
		if ( ! $request ) {
			$request = self::_get_server_var( 'PHP_SELF' );
		}
		$url = sprintf( 'http%s://%s%s%s',
			( self::_get_server_var( 'HTTPS' ) == 'on' ) ? 's' : '',
			self::_get_server_var( 'SERVER_NAME' ),
			( self::_get_server_var('SERVER_PORT') == '80' ) ? '' : ( ':' . self::_get_server_var( 'SERVER_PORT' ) ),
			$request );

		// Make sure that any query string information is included
		if ( ! empty( $_GET ) ) {
			$query = parse_url( $url, PHP_URL_QUERY );
			if ( ! $query ) {
				$url .= '?' . http_build_query( $_GET );
			}
		}
		return $url;
	}

	/**
	 * Creates a slug from the given text.
	 *
	 * @param  string $text the text to slugify
	 * @return string       a slug containing only ASCII characters
	 *
	 * @since 0.3
	 */
	public static function slugify( $text )
	{
		return sanitize_title_with_dashes( $text );
	}

	/**
	 * Finds the position of the widget with the given base name in the widget list.
	 *
	 * The list of widgets should be an array containing the string IDs of any
	 * registered widgets.  This list is search for both the provided widget
	 * base name and any multiwidget variants.  If the widget is not found
	 * in the list, a value of FALSE is returned.
	 *
	 * @param  string $name    the base name of the widget to search for
	 * @param  array  $widgets a list of widget IDs
	 * @return mixed           the index of the widget or FALSE if not found
	 *
	 * @since 0.3
	 */
	public function widget_search( $name, $widgets )
	{
		$search = '/^' . preg_quote( $name ) . '([_-]\d+)?$/';
		foreach ( $widgets as $index => $widget ) {
			if ( preg_match( $search, $widget ) ) {
				return $index;
			}
		}
		return false;
	}
}

?>
