<?php

/**
 * A class used by plugins to register themselves and get information on other plugins.
 *
 * For a plugin to make itself available to other plugins, it should register an
 * instance of itself with this class.  This will then allow other plugins to
 * access that plugin instance via a unique identifier.
 *
 * An example of a plugin registering itself in one file is as follows:
 *
 *     class MyPlugin {
 *         public function return_one() {
 *             return 1;
 *         }
 *     }
 *     ClassBlogs::register_plugin(
 *         'my_plugin',
 *         'MyPlugin',
 *         'My Plugin (Display Name)',
 *         'My plugin has a description.'
 *     );
 *
 * In another file in the class-blogs suite, this plugin could be accessed using
 * the following code:
 *
 *     $plugin = ClassBlogs::get_plugin( 'my_plugin' );
 *     assert( $plugin->return_one() === 1 );
 *
 * @package ClassBlogs
 * @version 0.5
 */
class ClassBlogs {

	/**
	 * A repository of all active class blogs plugins.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private static $_plugins = array();

	/**
	 * Return only plugin objects that are enabled.
	 *
	 * @param  object $plugin an internal plugin object
	 * @return bool           whether or not the plugin is enabled
	 *
	 * @private
	 * @since 0.3
	 */
	public static function _filter_enabled_plugins( $plugin )
	{
		return $plugin->enabled;
	}

	/**
	 * Return only plugin objects that can be disabled by a user.
	 *
	 * @param  object $plugin an internal plugin object
	 * @return bool           whether or not the plugin can be disabled
	 *
	 * @private
	 * @since 0.3
	 */
	public static function _filter_user_controlled_plugins( $plugin )
	{
		return $plugin->can_disable;
	}

	/**
	 * Helper function used to sort plugins by their display name.
	 *
	 * @param  object $a a plugin object
	 * @param  object $b a plugin object
	 * @return int       a comparison number
	 *
	 * @private
	 * @since 0.3
	 */
	public static function _sort_plugins_by_name( $a, $b )
	{
		return strcasecmp( $a->name, $b->name );
	}

	/**
	 * Checks whether the given plugin is enabled.
	 *
	 * @param  string $plugin_id the ID of the plugin
	 * @return bool              whether or not the plugin is enabled
	 *
	 * @private
	 * @since 0.3
	 */
	private static function _is_plugin_enabled( $plugin_id )
	{
		$disabled_plugins = get_site_option( 'cb_disabled_plugins' );
		if ( empty( $disabled_plugins ) ) {
			return true;
		} else {
			return ! array_key_exists( $plugin_id, $disabled_plugins );
		}
	}

	/**
	 * Loads all of the files in the given directory.
	 *
	 * No directory travesal takes place with this function, as it only loads
	 * the files directly in the given path; no files in subdirectories are loaded.
	 * The files are loaded in alphabetical order.
	 *
	 * @param string $dir an absolute path to a directory containing files to be loaded
	 *
	 * @since 0.3
	 */
	public static function load_php_files( $dir )
	{
		if ( $handle = opendir( $dir ) ) {
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( substr( $entry, -strlen( '.php' ) ) === '.php' ) {
					require_once( $dir . '/' . $entry );
				}
			}
		}
	}

	/**
	 * Initializes the class-blogs suite.
	 *
	 * This handles the loading of all files that are part of the suite.
	 *
	 * @since 0.4
	 */
	public static function initialize()
	{
		// Register initialization hooks
		add_action( 'init', array( 'ClassBlogs', '_initialize' ) );

		// Load all plugin files
		self::load_php_files( CLASS_BLOGS_DIR_ABS . '/ClassBlogs' );
		self::load_php_files( CLASS_BLOGS_DIR_ABS . '/ClassBlogs/Plugins' );

		// Allow the custom themes to be used
		$themes_dir = CLASS_BLOGS_DIR_ABS . '/themes';
		if ( is_dir( $themes_dir ) ) {
			register_theme_directory( $themes_dir );
		}
	}

	/**
	 * Performs WordPress-specific initialization for the class-blogs suite.
	 *
	 * @access private
	 * @since 0.4
	 */
	public static function _initialize()
	{
		// Loads translations for the current locale
		load_plugin_textdomain(
			'classblogs',
			false,
			CLASS_BLOGS_DIR_REL . '/languages' );

		// Possibly upgrade the plugin
		self::maybe_upgrade();
	}

	/**
	 * Toggles the active status of the class blogs suite.
	 *
	 * @param bool $activate whether to activate or deactivate the plugin suite
	 *
	 * @since 0.4
	 */
	public static function _set_activation( $activate )
	{
		$method = ( $activate ) ? 'activate' : 'deactivate';
		ClassBlogs_WordPress::switch_to_blog( ClassBlogs_Settings::get_root_blog_id() );
		foreach ( self::get_all_plugins() as $plugin ) {
			if ( is_callable( array( $plugin->plugin, $method ) ) ) {
				call_user_func( array( $plugin->plugin, $method ) );
			}
		}
		ClassBlogs_WordPress::restore_current_blog();
	}

	/**
	 * Handles activation for the class-blogs suite.
	 *
	 * @since 0.4
	 */
	public static function activate_suite()
	{
		self::_set_activation( true );
	}

	/**
	 * Handles deactivation for the class-blogs suite.
	 *
	 * @since 0.4
	 */
	public static function deactivate_suite()
	{
		self::_set_activation( false );
	}

	/**
	 * Requires a file that is part of the class-blogs suite.
	 *
	 * The file path must be relative to the base ClassBlogs directory.  The
	 * required file will be required only once, so multiple imports will not
	 * occur when using this method.
	 *
	 * @param string $path a relative or absolute path to a class-blogs files
	 *
	 * @since 0.3
	 */
	public static function require_cb_file( $path )
	{
		require_once( CLASS_BLOGS_DIR_ABS . '/ClassBlogs/' . $path );
	}

	/**
	 * Registers a plugin with the class blogs suite.
	 *
	 * If the ID under which the plugin is being registered conflicts with an
	 * already registered plugin, a fatal error is thrown.
	 *
	 * @param string $id          a unique identifier for the plugin
	 * @param string $plugin      the name of a plugin class descended from BasePlugin
	 * @param string $name        an optional user-friendly name for the plugin
	 * @param string $description an optional description of the plugin
	 * @param bool   $can_disable whether or not a user can disable the plugin
	 *
	 * @since 0.1
	 */
	public static function register_plugin( $id, $plugin, $name=null, $description=null, $can_disable=true)
	{

		// Check for invalid or conflicting plugin IDs
		if ( ! $id ) {
			trigger_error(
				'You must provide a unique ID as your first argument when you register a plugin!',
				E_USER_ERROR );
		}
		if ( array_key_exists( $id, self::$_plugins ) ) {
			trigger_error(
				sprintf( 'You have already registered a plugin with the ID "%s"!', $id ),
				E_USER_ERROR );
		}

		// If the plugin has not been explicitly disabled, declare a new instance
		// of it in the registry, which will enable it to register any hooks
		// that it needs to.  If it has been disabled, don't instantiate it, and
		// just provide a null value for the plugin instance.
		$registered_plugin = null;
		if ( self::_is_plugin_enabled( $id ) ) {
			$registered_plugin = new $plugin;
		}
		self::$_plugins[$id] = (object) array(
			'can_disable' => $can_disable,
			'description' => $description,
			'id'          => $id,
			'name'        => $name,
			'plugin'      => $registered_plugin
		);
	}

	/**
	 * Returns a plugin instance registered under the given ID.
	 *
	 * If no plugin is found matching the given ID, or if the given plugin is
	 * valid but has been disabled by the user, a null value is returned.
	 *
	 * @param  string $id the ID with which the plugin was registered
	 * @return object     the plugin instance or null
	 *
	 * @since 0.1
	 */
	public static function get_plugin( $id )
	{
		if ( array_key_exists( $id, self::$_plugins ) ) {
			return self::$_plugins[$id]->plugin;
		} else {
			return null;
		}
	}

	/**
	 * Gets a list of all plugins that are part of the class-blogs suite.
	 *
	 * Each entry in this list is an object with the following properties:
	 *
	 *     can_disable - whether or not the user can disable the plugin
	 *     description - a short description of the plugin
	 *     enabled     - whether or not the plugin is enabled
	 *     id          - the plugin's unique identifier
	 *     name        - the user-friendly name of the plugin
	 *     plugin      - an instance of the plugin class
	 *
	 * The returned list will be ordered ascending alphabetically by the
	 * user-friendly name of the plugin.
	 *
	 * @return array a list of all plugins that are part of the class-blogs suite.
	 *
	 * @since 0.3
	 */
	public static function get_all_plugins()
	{
		$plugins = self::$_plugins;
		foreach ( $plugins as $plugin ) {
			$plugin->enabled = self::_is_plugin_enabled( $plugin->id );
		}
		uasort( $plugins, array( 'self', '_sort_plugins_by_name' ) );
		return $plugins;
	}

	/**
	 * Gets a list of enabled class-blogs plugins.
	 *
	 * The items in the list are formatted identically to the values returned
	 * by the `get_all_plugins()` method.
	 *
	 * @return array a list of all enabled class-blogs plugins.
	 *
	 * @since 0.3
	 */
	public static function get_enabled_plugins()
	{
		return array_filter(
			self::get_all_plugins(),
			array( 'self', '_filter_enabled_plugins' ) );
	}

	/**
	 * Gets a list of all plugins that can be disabled by the user.
	 *
	 * The items in the list are formatted identically to the values returned
	 * by the `get_all_plugins()` method.
	 *
	 * @return array a list of all class-blogs plugins that can be disabled by the user
	 *
	 * @since 0.3
	 */
	public static function get_user_controlled_plugins()
	{
		return array_filter(
			self::get_all_plugins(),
			array( 'self', '_filter_user_controlled_plugins' ) );
	}

	/**
	 * Enables the given class-blogs plugin.
	 *
	 * This method, along with `disable_plugin`, is used to control which
	 * components of the class-blogs suite are used on a site.
	 *
	 * @param string $plugin_id the plugin's unique ID
	 *
	 * @since 0.3
	 */
	public static function enable_plugin( $plugin_id )
	{
		$disabled = get_site_option( 'cb_disabled_plugins' );
		if ( ! empty( $disabled ) ) {
			if ( array_key_exists( $plugin_id, $disabled ) ) {
				unset( $disabled[$plugin_id] );
				update_site_option( 'cb_disabled_plugins', $disabled );
			}
		}
	}

	/**
	 * Disables the given class-blogs plugin.
	 *
	 * This method, along with `enable_plugin`, is used to control which
	 * components of the class-blogs suite are used on a site.
	 *
	 * @param string $plugin_id the plugin's unique ID
	 *
	 * @since 0.3
	 */
	public static function disable_plugin( $plugin_id )
	{
		$disabled = get_site_option( 'cb_disabled_plugins' );
		if ( empty( $disabled ) ) {
			$disabled = array();
		}
		$disabled[$plugin_id] = true;
		update_site_option( 'cb_disabled_plugins', $disabled );
	}

	/**
	 * Possibly upgrade the plugin if a version difference is detected.
	 *
	 * This checks whether the stored version on the database is greater than
	 * the current version of the plugin.
	 *
	 * @since 0.3
	 */
	public static function maybe_upgrade()
	{

		// Check the version stored on the database, setting it if none is found
		$db_version = get_site_option( 'cb_version' );
		if ( empty( $db_version ) ){
			$db_version = ClassBlogs_Settings::VERSION;
			update_site_option( 'cb_version', $db_version );
		}

		// If the version on the database is less than the current version, call
		// the upgrade script on all of the plugins
		if ( version_compare( $db_version, ClassBlogs_Settings::VERSION, '<' ) ) {
			foreach ( self::get_all_plugins() as $plugin ) {
				if ( is_callable( array( $plugin->plugin, 'upgrade' ) ) ) {
					$plugin->plugin->upgrade( $db_version, ClassBlogs_Settings::VERSION );
				}
			}
			update_site_option( 'cb_version', ClassBlogs_Settings::VERSION );
		}
	}
}

ClassBlogs::register_plugin(
	'class_blogs',
	'ClassBlogs',
	__( 'Class Blogs', 'classblogs' ),
	__( 'The base class-blogs plugin that is used to manage plugin registration.', 'classblogs' ),
	false
);

// Delay including dependencies
ClassBlogs::require_cb_file( 'Settings.php' );
ClassBlogs::require_cb_file( 'WordPress.php' );

?>
