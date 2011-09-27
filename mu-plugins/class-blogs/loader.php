<?php

// Load core, utilities and settings
$core = array(
	'ClassBlogs',
	'Paginator',
	'Settings',
	'Utils'
);
if ( is_admin() ) {
	$core[] = 'Admin';
}
foreach ( $core as $file ) {
	require_once( 'ClassBlogs/' . $file . '.php' );
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
