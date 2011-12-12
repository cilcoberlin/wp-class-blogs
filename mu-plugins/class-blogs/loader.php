<?php

/**
 * A loader that imports each component of the class blogs suite.  This first
 * imports all of the core class-blogs files and then pulls in the files
 * associated with each individual plugin in the suite.
 *
 * @package ClassBlogs
 * @since 0.1
 */

// Load core class-blogs modules
$core = array(
	'ClassBlogs',
	'Paginator',
	'Settings',
	'Schema',
	'Utils',
	'Admin'
);
foreach ( $core as $file ) {
	require_once( 'ClassBlogs/' . $file . '.php' );
}

// Load the suite's plugins
$plugins = array(
	'BasePlugin',
	'SidebarWidget',
	'Aggregation/Settings',
	'Aggregation/Schemata',
	'Aggregation/Aggregator',
	'Aggregation/SitewidePlugin',
	'Aggregation/SitewideComments',
	'Aggregation/SitewidePosts',
	'Aggregation/SitewideTags',
	'ClassmateComments',
	'DisableComments',
	'GravatarSignup',
	'NewUserConfiguration',
	'RandomImage',
	'StudentBlogLinks',
	'StudentBlogList',
	'StudentPseudonym',
	'WordCounter',
	'YouTubeClassPlaylist'
);
foreach ( $plugins as $plugin ) {
	require_once( 'ClassBlogs/Plugins/' . $plugin . '.php' );
}

/**
 * Performs initialization actions for the entire class blogs suite
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
