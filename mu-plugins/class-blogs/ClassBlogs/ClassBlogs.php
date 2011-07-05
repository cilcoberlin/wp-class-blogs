<?php

/**
 * A high-level class for namespacing class blogs data
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs {

	/**
	 * A repository of all active class blogs plugins
	 *
	 * @access private
	 * @var array
	 */
	private static $_plugins = array();

	/**
	 * Registers a plugin with the class blogs suite
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
	 * Returns a plugin instance registered under the given name
	 *
	 * If no plugin is found matching the given name, a null value is returned.
	 *
	 * @param  string $name the name with which the plugin was registered
	 * @return object       the plugin instance or null
	 *
	 *  @since 0.1
	 */
	public static function get_plugin( $name )
	{
		return self::$_plugins[$name];
	}

}

?>
