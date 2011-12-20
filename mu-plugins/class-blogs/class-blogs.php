<?php

/**
 * A loader that imports each component of the class blogs suite.  This first
 * imports all of the core class-blogs files and then pulls in the files
 * associated with each individual plugin in the suite.
 *
 * @package ClassBlogs
 * @version 0.2
 */

// Require the core class-blogs class and use it to load all required files
require_once( dirname( __FILE__ ) . '/ClassBlogs.php' );
ClassBlogs::initialize();

/**
 * Performs initialization actions for the entire class-blogs suite.
 *
 * @access private
 * @since 0.1
 */
function _classblogs_init()
{
	// Loads translations for the current locale
	load_plugin_textdomain(
		'classblogs',
		false,
		basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'init', '_classblogs_init' );

?>
