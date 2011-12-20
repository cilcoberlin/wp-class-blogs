<?

/**
 * Schemata for tables used by the sitewide plugins.
 *
 * These schemata represent the tables that the sitewide plugins use to keep
 * track of the sitewide data.  Some of the tables, such as the comments and
 * posts table, are more or less identical to WordPress's core comments and posts
 * tables, with the addition of a few ID fields and modified indexes.  Other
 * tables, such as those used to keep track of sitewide tags, adhere less
 * closely to the WordPress tables.
 *
 * Note that these objects simply represent the table structure.  The actual
 * names of the tables to which these schemata are bound are set via the
 * `ClassBlogs_Plugins_Aggregation_Settings` class.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage Schemata
 * @since 0.2
 */
class ClassBlogs_Plugins_Aggregation_Schemata
{

	/**
	 * Get the schema used for the sitewide comments table
	 *
	 * @return ClassBlogs_Schema an instance of the comment schema
	 *
	 * @since 0.2
	 */
	public static function get_comments_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'cb_sw_ID',             'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'comment_ID',           'bigint(20) unsigned NOT NULL' ),
				array( 'comment_post_ID',      'bigint(20) unsigned NOT NULL DEFAULT "0"' ),
				array( 'comment_author',       'tinytext NOT NULL' ),
				array( 'comment_author_email', 'varchar(100) NOT NULL DEFAULT ""' ),
				array( 'comment_author_url',   'varchar(200) NOT NULL DEFAULT ""' ),
				array( 'comment_author_IP',    'varchar(100) NOT NULL DEFAULT ""' ),
				array( 'comment_date',         'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'comment_date_gmt',     'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'comment_content',      'text NOT NULL' ),
				array( 'comment_karma',        'int(11) NOT NULL DEFAULT "0"' ),
				array( 'comment_approved',     'varchar(20) NOT NULL DEFAULT "1"' ),
				array( 'comment_agent',        'varchar(255) NOT NULL DEFAULT ""' ),
				array( 'comment_type',         'varchar(20) NOT NULL DEFAULT ""' ),
				array( 'comment_parent',       'bigint(20) unsigned NOT NULL DEFAULT "0"' ),
				array( 'user_id',              'bigint(20) unsigned NOT NULL DEFAULT "0"' ),
				array( 'cb_sw_blog_id',        'bigint(20) unsigned NOT NULL' )
			),
			'cb_sw_ID',
			array(
				array( 'comment_approved',           'comment_approved' ),
				array( 'comment_post_ID',            'comment_post_ID' ),
				array( 'comment_approved_date_gmt',  array( 'comment_approved', 'comment_date_gmt' ) ),
				array( 'comment_date_gmt',           'comment_date_gmt' ),
				array( 'comment_parent',             'comment_parent' ),
				array( 'cb_sw_blog_id',              'cb_sw_blog_id' )
			)
		);
	}

	/**
	 * Get the schema used for the sitewide posts table
	 *
	 * @return ClassBlogs_Schema an instance of the posts schema
	 *
	 * @since 0.2
	 */
	public static function get_posts_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'cb_sw_ID',              'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'ID',                    'bigint(20) unsigned NOT NULL' ),
				array( 'post_author',           'bigint(20) unsigned NOT NULL DEFAULT "0"' ),
				array( 'post_date',             'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'post_date_gmt',         'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'post_content',          'longtext NOT NULL' ),
				array( 'post_title',            'text NOT NULL' ),
				array( 'post_excerpt',          'text NOT NULL' ),
				array( 'post_status',           'varchar(20) NOT NULL DEFAULT "publish"' ),
				array( 'comment_status',        'varchar(20) NOT NULL DEFAULT "open"' ),
				array( 'ping_status',           'varchar(20) NOT NULL DEFAULT "open"' ),
				array( 'post_password',         'varchar(20) NOT NULL DEFAULT ""' ),
				array( 'post_name',             'varchar(200) NOT NULL DEFAULT ""' ),
				array( 'to_ping',               'text NOT NULL' ),
				array( 'pinged',                'text NOT NULL' ),
				array( 'post_modified',         'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'post_modified_gmt',     'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"' ),
				array( 'post_content_filtered', 'text NOT NULL' ),
				array( 'post_parent',           'bigint(20) unsigned NOT NULL DEFAULT "0"' ),
				array( 'guid',                  'varchar(255) NOT NULL DEFAULT ""' ),
				array( 'menu_order',            'int(11) NOT NULL DEFAULT "0"' ),
				array( 'post_type',             'varchar(20) NOT NULL DEFAULT "post"' ),
				array( 'post_mime_type',        'varchar(100) NOT NULL DEFAULT ""' ),
				array( 'comment_count',         'bigint(20) NOT NULL DEFAULT "0"' ),
				array( 'cb_sw_blog_id',         'bigint(20) unsigned NOT NULL' )
			),
			'cb_sw_ID',
			array(
				array( 'post_name',        'post_name' ),
				array( 'type_status_date', array( 'post_type', 'post_status', 'post_date', 'ID' ) ),
				array( 'post_parent',      'post_parent' ),
				array( 'post_author',      'post_author' ),
				array( 'cb_sw_blog_id',    'cb_sw_blog_id' )
			)
		);
	}

	/**
	 * Get the schema used for the sitewide tags table
	 *
	 * @return ClassBlogs_Schema an instance of the tags schema
	 *
	 * @since 0.2
	 */
	public static function get_tags_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'term_id', 'bigint(20) unsigned AUTO_INCREMENT NOT NULL' ),
				array( 'name',    'varchar(200) NOT NULL' ),
				array( 'slug',    'varchar(200) NOT NULL' ),
				array( 'count',   'bigint(20) NOT NULL DEFAULT 0' )
			),
			'term_id',
			array(
				array( 'name', 'name' ),
				array( 'slug', 'slug' )
			)
		);
	}

	/**
	 * Get the schema used for the sitewide tag usage table
	 *
	 * @return ClassBlogs_Schema an instance of the tag usage schema
	 *
	 * @since 0.2
	 */
	public static function get_tag_usage_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'usage_id',  'bigint(20) unsigned AUTO_INCREMENT NOT NULL' ),
				array( 'post_id',   'bigint(20) unsigned NOT NULL' ),
				array( 'uses_tag',  'bigint(20) unsigned NOT NULL' ),
				array( 'blog_id',   'bigint(20) unsigned NOT NULL' )
			),
			'usage_id',
			array(
				array( 'provenance', array( 'post_id', 'blog_id' ) )
			)
		);
	}
}

?>
