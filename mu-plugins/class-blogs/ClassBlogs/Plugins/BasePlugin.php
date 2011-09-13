<?php

/**
 * Base class for any plugin that is part of the class blogs suite
 *
 * @package ClassBlogs
 * @since 0.1
 */
abstract class ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The default options for the plugin
	 *
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array();

	/**
	 * The internally stored options for the plugin
	 *
	 * @access private
	 * @var array
	 */
	private $_options;

	/**
	 * Performs sanity checks and configuration when loaded
	 */
	public function __construct()
	{
		// Provide plugins with the option of enabling an admin page
		if ( is_admin() ) {
    		add_action( 'admin_menu', array( $this, '_maybe_enable_admin_page' ) );
    	}
	}

	/**
	 * Generates a pseudo-unique ID for the plugin
	 *
	 * This ID is automatically generated from a truncated and modified version
	 * of the current plugin's classname.
	 *
	 * @return string the plugin's pseudo-unique ID
	 *
	 * @since 0.1
	 */
	public function get_uid()
	{
		return strtolower( str_replace( 'ClassBlogs_Plugins', 'cb', get_class( $this ) ) );
	}

	/**
	 * Returns a list of all blog IDs on the site
	 *
	 * @return array a list of all blog IDs on the site
	 *
	 * @since 0.1
	 */
	public function get_all_blog_ids()
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
	 * Gets the options for the current plugin
	 *
	 * If no options are found for the plugin, options are set using the values
	 * contained in the $default_options  variable.  If this variable is
	 * empty, it is assumed that no options are used for the plugin.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_options()
	{
		$options_id = $this->get_uid();
		$options = get_site_option( $options_id );
		if ( empty( $options ) ) {
			$options = $this->default_options;
			$this->update_options( $options );
		}
		$this->_options = $options;
	}

	/**
	 * Return the value of the requested plugin option
	 *
	 * @param  string $name the name of the plugin option
	 * @return mixed        the value of the plugin option
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_option( $name )
	{
		if ( ! isset( $this->options ) && ! empty( $this->default_options ) ) {
			$this->_get_options();
		}
		if ( $this->_options && array_key_exists( $name, $this->_options ) ) {
			return $this->_options[$name];
		} else {
			return null;
		}
	}

	/**
	 * Returns true if the given options's value is empty
	 *
	 * This will be true if the option has a value that counts as empty, or
	 * if the option is not set.
	 *
	 * @param  string $name the name of the option
	 * @return bool         whether the option's value is empty
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function option_is_empty( $name )
	{
		$value = $this->get_option( $name );
		return empty( $value );
	}

	/**
	 * Returns an array containing all the current plugin options
	 *
	 * @return array a hash of the plugin's current options
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_options()
	{
		$this->_get_options();
		return $this->_options;
	}

	/**
	 * Updates the value of a single option
	 *
	 * @param string $option the name of the plugin value to update
	 * @param mixed  $value  the new value of the plugin option
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function update_option( $option, $value )
	{
		$options = $this->get_options();
		$options[$option] = $value;
		$this->update_options( $options );
	}

	/**
	 * Updates the options used by a plugin
	 *
	 * @param array $options an array of the plugin's options
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function update_options( $options )
	{
		update_site_option( $this->get_uid(), $options );
		$this->_get_options();
	}

	/**
	 * Adds a value to the cache
	 *
	 * @param string the key under which to cache the data
	 * @param mixed  the date to cache
	 * @param int    the optional number of seconds for which to cache the value
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function set_cache( $key, $value, $expiration = 3600 )
	{
		set_transient( $this->_make_cache_key_name( $key ), $value, $expiration );
	}

	/**
	 * Retrieves the requested cache value
	 *
	 * If the cache value is not found, and empty string is returned
	 *
	 * @param  string $key the cache key whose value should be retrieved
	 * @return mixed       the cached value or an empty string
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function get_cache( $key )
	{
		return get_transient( $this->_make_cache_key_name( $key ) );
	}

	/**
	 * Clears a single cached value
	 *
	 * @param string $key the cache key to clear
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function clear_cache( $key )
	{
		delete_transient( $this->_make_cache_key_name( $key ) );
	}

	/**
	 * Generates the name of the cache key used to store data for the current plugin
	 *
	 * @param  string $key the base name of the cache key
	 * @return string      the full name of the cache key
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _make_cache_key_name( $key ) {
		return $this->get_uid() . '_' . $key;
	}

	/**
	 * Registers a sidebar widget
	 *
	 * @param mixed  $widget the widget class
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function register_widget( $widget )
	{
		register_widget( $widget );
	}

	/**
	 * Registers a sidebar widget that should only be available on the root blog
	 *
	 * This makes the widget only appear as a selection on the admin side if the
	 * user is an admin on the root blog and the root blog is being edited, but
	 * will show the widget to any user viewing the root blog.
	 *
	 * @param mixed  $widget the widget class
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function register_root_only_widget( $widget )
	{
		global $blog_id;
		if ( $blog_id == ClassBlogs_Settings::get_root_blog_id() && ( ! is_admin() || $this->current_user_is_admin_on_root_blog() ) ) {
			$this->register_widget( $widget );
		}
	}

	/**
	 * Returns true if the current user is an administrator on the root blog
	 *
	 * @return bool whether the current user is an admin on the root blog
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function current_user_is_admin_on_root_blog()
	{
		return current_user_can_for_blog(
			ClassBlogs_Settings::get_root_blog_id(),
			'administrator' );
	}

	/**
	 * Returns markup to make a checkbox or select box selected if it is true
	 *
	 * @param  string $option the name of the option
	 * @return string         possible markup for a checked attribute
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function option_to_selected_attribute( $option )
	{
		return ( $this->get_option( $option ) ) ? 'checked="checked"' : "";
	}

	/**
	 * Creates a page for the plugin with the given name on the root blog
	 *
	 * This makes a private page, so that it is not displayed by page-listing
	 * function of WordPress.  However, there is logic behind the scenes that
	 * grants any user access to the page when visiting its URL.
	 *
	 * An optional existing page ID can be passed in.  If this argument is given
	 * and the page ID does not map to a valid page, the page is recreated.
	 *
	 * @param  string $name    the name of the page to create
	 * @param  int    $page_id the optional ID of an already created page
	 * @return int             the ID of the created page
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function create_plugin_page( $name, $page_id = null )
	{
		$conflicts = true;
		$counter = 0;
		$page_name = $name;

		// If a page with the given ID already exists, abort early
		if ( get_page( $page_id ) ) {
			ClassBlogs::register_plugin_page( $page_id );
			return $page_id;
		}

		// Find a name for the new page that doesn't conflict with others
		while ( $conflicts ) {
			$page = get_page_by_title( $page_name );
			if ( isset( $page ) ) {
				$counter++;
				$page_name = sprintf( '%s %d', $name, $counter );
			} else {
				$conflicts = false;
			}
		}

		// Create the new page and store its ID
		$new_page = array(
			'post_author' => ClassBlogs_Settings::get_admin_user_id(),
			'post_status' => 'private',
			'post_title'  => $page_name,
			'post_type'   => 'page' );
		$page_id = wp_insert_post( $new_page );
		ClassBlogs::register_plugin_page( $page_id );

		return $page_id;
	}

	/**
	 * Enables a possible admin page associated with a child plugin
	 *
	 * This simply provides a shortcut for any child plugins to register an admin
	 * page that is part of the class blogs menu group.  A child plugin can override
	 * the `enable_admin_page` method called by this to register an admin page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _maybe_enable_admin_page()
	{
		if ( ClassBlogs_Utils::on_root_blog_admin() ) {
			$admin = ClassBlogs_Admin::get_admin();
			$this->enable_admin_page( $admin );
		}
	}

	/**
	 * Allows a child plugin to register an admin page
	 *
	 * @param object $admin a ClassBlogs_Admin instance
	 *
	 * @since 0.1
	 */
	public function enable_admin_page( $admin ) {}
}

?>
