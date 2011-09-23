<?php

// Load utilities and settings
require_once( 'ClassBlogs/ClassBlogs.php' );
require_once( 'ClassBlogs/Settings.php' );
require_once( 'ClassBlogs/Utils.php' );

// If we're on the admin side, configure shared admin functionality
if ( is_admin() ) {
	require_once( 'ClassBlogs/Admin.php' );
}

// Load the suite's plugins
$plugins = array(
	'BasePlugin',
	'SidebarWidget',
	'Aggregation/Settings',
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
 * Performs global class-blogs plugin initialization actions
 *
 * @access private
 * @package Class Blogs
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
