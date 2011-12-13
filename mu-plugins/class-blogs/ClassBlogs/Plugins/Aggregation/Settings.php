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
	 * @var string
	 * @since 0.1
	 */
	const TABLE_PREFIX = 'cb_sw_';

	/**
	 * The title of the default first post present on any newly created blog.
	 *
	 * @var string
	 * @since 0.1
	 */
	const FIRST_POST_TITLE = 'Hello world!';

	/**
	 * The name of the author of the first default comment on any new blog.
	 *
	 * @var string
	 * @since 0.1
	 */
	const FIRST_COMMENT_AUTHOR = 'Mr WordPress';

	/**
	 * The name used to identify a tag in the WordPress's taxonomy.
	 *
	 * @var string
	 * @since 0.1
	 */
	const TAG_TAXONOMY_NAME = 'post_tag';

	/**
	 * The name of the option used to store any sitewide cache keys.
	 *
	 * @var string
	 * @since 0.1
	 */
	const CACHE_KEY_OPTION_NAME = 'cb_sw_cache_keys';

	/**
	 * The available short names for the sitewide tables.
	 *
	 * @access private
	 * @var array
	 */
	private static $_table_names = array(
		'posts',
		'comments',
		'tags',
		'tag_usage'
	);

	/**
	 * Returns the full name of the table used for the given short name.
	 *
	 * @param  string $table the short name for the table
	 * @return string        the full database name of the table, or an empty
	 *                       string if the table name is not valid
	 *
	 * @since 0.1
	 */
	public static function get_table_name( $table )
	{
		if ( array_search( $table, self::$_table_names ) !== false ) {
			return self::TABLE_PREFIX . $table;
		}
		return "";
	}

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
		foreach ( self::$_table_names as $table_name ) {
			$tables[$table_name] = self::get_table_name( $table_name );
		}
		return (object) $tables;
	}

}

?>
