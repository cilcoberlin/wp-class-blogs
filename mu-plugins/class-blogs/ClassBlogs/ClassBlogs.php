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
 *     ClassBlogs::register_plugin( 'my_plugin', new MyPlugin() );
 *
 * In another file in the class-blogs suite, this plugin could be accessed using
 * the following code:
 *
 *     $plugin = ClassBlogs::get_plugin( 'my_plugin' );
 *     assert( $plugin->return_one() === 1 );
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
	 * @since 0.1
	 */
	private static $_plugins = array();

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
}

ClassBlogs::register_plugin( 'class_blogs', new ClassBlogs() );

?>
