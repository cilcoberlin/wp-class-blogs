<?php

// Load the components of the aggregation suite
$base_dir = dirname( __FILE__ );
$required_files = array(
	'Settings',
	'SitewidePlugin',
	'Aggregator',
	'Schemata',
	'SitewideComments',
	'SitewidePosts',
	'SitewideTags'
);
foreach ( $required_files as $file ) {
	require_once( "$base_dir/Aggregation/$file.php" );
}

/**
 * A collection of plugins that deal with aggregated data from all of the blogs
 * on the current site.
 *
 * @package ClassBlogs_Plugins
 * @subpackage Aggregation
 * @since 0.3
 */
class ClassBlogs_Plugins_Aggregation
{
	// This is an empty stub class designed to act as a wrapper for the three
	// actual sitewide plugins with which a user can interact
}

?>
