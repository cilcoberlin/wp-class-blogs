<?php

/**
 * Settings for the class blogs aggregation plugins.
 *
 * These settings mainly provide information on the names of the tables used
 * by the sitewide plugins to track sitewide data.  There are also a few
 * constants that define relatively certain values used by WordPress.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage Settings
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_Settings
{

	/**
	 * The prefix to use for all sitewide tables.
	 *
	 * @access private
	 * @var string
	 * @since 0.2
	 */
	const _TABLE_PREFIX = 'cb_sw_';

	/**
	 * The name used to identify a tag in WordPress's taxonomy.
	 *
	 * @var string
	 * @since 0.1
	 */
	const TAG_TAXONOMY_NAME = 'post_tag';

	/**
	 * The available short names for the sitewide tables.
	 *
	 * This is a mapping of the name by which the table's full name can be
	 * accessed to the base name for the actual table.  The access name is
	 * the key, and the base name is the value.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private static $_table_names = array(
		'comments'  => 'comments',
		'posts'     => 'posts',
		'tags'      => 'tags',
		'tag_usage' => 'tag_usage'
	);

	/**
	 * Returns a mapping of short table names to their full names.
	 *
	 * The available short tables names, as well as their function, are as below:
	 *
	 *     posts     - the sitewide posts table
	 *     comments  - the sitewide comments table
	 *     tags      - the sitewide tags table
	 *     tag_usage - the sitewide tag usage table
	 *
	 * @return array a mapping of short names to full table names
	 *
	 * @since 0.1
	 */
	public static function get_table_names()
	{
		$tables = array();
		foreach ( self::$_table_names as $access_name => $table_name ) {
			$tables[$access_name] = self::_TABLE_PREFIX . $table_name;
		}
		return (object) $tables;
	}
}

?>
