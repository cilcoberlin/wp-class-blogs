<?php

/**
 * A high-level class for namespacing class blogs data.
 *
 * @package ClassBlogs
 * @since 0.1
 */
class ClassBlogs {

	/**
	 * A repository of all active class blogs plugins.
	 *
	 * @access private
	 * @var array
	 */
	private static $_plugins = array();

	/**
	 * Executes actions common to all class blogs plugins.
	 */
	public function __construct()
	{
		$this->_maybe_open_plugin_pages();
	}

	/**
	 * Add a plugin page's ID to the list of pages that need to be opened
	 * if the page ID is not already in the registry.
	 *
	 * @param int $page_id the ID of a plugin page
	 *
	 * @since 0.1
	 */
	public static function register_plugin_page( $page_id )
	{
		$plugin_pages = get_site_option( 'cb_plugin_pages', array() );
		if ( ! in_array( $page_id, $plugin_pages ) ) {
			$plugin_pages[] = $page_id;
			update_site_option( 'cb_plugin_pages', $plugin_pages );
		}
	}

	/**
	 * Open up any plugin pages if the plugin has created pseudo-private pages.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _maybe_open_plugin_pages()
	{
		add_filter( 'posts_results', array( $this, '_allow_access_to_plugin_pages' ) );
	}

	/**
	 * Allows a non-admin user access to a private page.
	 *
	 * This provides access only to the private pages created using the
	 * create_plugin_page function, which creates a private page that should
	 * be accessible to any user.
	 *
	 * @param array $results the posts found when the page loaded
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _allow_access_to_plugin_pages( $results )
	{
		// Abort if we have no private pages that need opening
		$pages = get_site_option( 'cb_plugin_pages' );
		if ( empty( $pages ) ) {
			return $results;
		}

		// If we have private pages and we're on one of them, make it temporarily
		// public and return its contents
		global $wpdb;
		foreach ( $pages as $page ) {
			if ( ClassBlogs::is_page( $page ) ) {
				$content = $wpdb->get_row( $wpdb->prepare ( "
					SELECT * FROM $wpdb->posts WHERE ID = %d",  $page ) );
				$content->comment_status = 'closed';
				$content->post_status = 'publish';
				return array( $content );
			}
		}
		return $results;
	}

	/**
	 * Registers a plugin with the class blogs suite.
	 *
	 * If the given name conflicts with an already registered plugin, a fatal
	 * error is thrown.
	 *
	 * @param string $name   the plugin name
	 * @param object $plugin the plugin instance
	 *
	 * @since 0.1
	 */
	public static function register_plugin( $name, $plugin )
	{
		if ( array_key_exists( $name, self::$_plugins ) ) {
			trigger_error(
				sprintf( 'You have already registered a plugin with the slug "%s"!', $name ),
				E_USER_ERROR );
		}
		self::$_plugins[$name] = $plugin;
	}

	/**
	 * Returns a plugin instance registered under the given name.
	 *
	 * If no plugin is found matching the given name, a null value is returned.
	 *
	 * @param  string $name the name with which the plugin was registered
	 * @return object       the plugin instance or null
	 *
	 * @since 0.1
	 */
	public static function get_plugin( $name )
	{
		if ( array_key_exists( $name, self::$_plugins ) ) {
			return self::$_plugins[$name];
		} else {
			return null;
		}
	}

	/**
	 * Functions identically to WordPress's native `is_page` function, but returns
	 * false if the current page is in a state where `is_page` could not be
	 * called without creating an error.
	 *
	 * @param  int  $page_id the ID of the page being checked
	 * @return bool          whether or not the current page matches the page ID
	 *
	 * @since 0.1
	 */
	public static function is_page( $page_id )
	{

		// This exists due to a flaw, likely due to some unknown structural
		// deficiency of the class blogs suite, that causes WordPress to spew
		// errors about a null post object when `is_page` is called on a page
		// where no posts were found.  Making the `post` property of the `wp_query`
		// object be a null post with all of the properties that are checked
		// for in the `is_page` call prevents these errors from occurring.
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
	 * @param  int $blog_id the ID of the blog to restore to
	 *
	 * @since 0.1
	 */
	public static function restore_blog( $blog_id )
	{
		global $switched_stack, $switched;
		switch_to_blog( $blog_id );
		$switched_stack = array();
		$switched = false;
	}
}

ClassBlogs::register_plugin( 'class_blogs', new ClassBlogs() );

?>
