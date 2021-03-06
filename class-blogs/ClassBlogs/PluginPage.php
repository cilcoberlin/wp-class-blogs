<?php

ClassBlogs::require_cb_file( 'Settings.php' );
ClassBlogs::require_cb_file( 'Utils.php' );

/**
 * An interface to a page created by a plugin.
 *
 * These pages are created by plugins to allow them to display certain
 * full-page content without the user having to create a page for them.  For
 * example, a plugin could use this to create a dedicated page that would show
 * a list of all posts matching some sort of custom query generated by the plugin.
 *
 * Any page generated through this class will show up in the list of pages on
 * a user's blog.  However, all created pages will be excluded from the
 * auto-generated navigation bars appearing at the top of most themes.
 *
 * For a plugin to register a page, the following code can be run:
 *
 *     $page_id = ClassBlogs_PluginPage::create_plugin_page( 'My Page' );
 *     echo 'A plugin page called "My Page" was created with an ID of ' . $page_id;
 *
 * @package ClassBlogs
 * @subpackage PluginPage
 * @since 0.2
 */
class ClassBlogs_PluginPage
{

	/** Registers a hook to exclude plugin pages from the theme's page list. */
	public function __construct()
	{
		add_filter( 'get_pages', array( $this, '_exclude_plugin_pages' ) );
	}

	/**
	 * Exclude any pages created by plugins from the list of pages shown at the
	 * top of many themes.
	 *
	 * @access private
	 * @since 0.4
	 */
	public static function _exclude_plugin_pages( $pages )
	{
		// Abort if we're on the admin side
		if ( is_admin() ) {
			return $pages;
		}

		// Remove any pages created by plugins from the list of pages
		$plugin_pages = get_site_option( 'cb_plugin_pages' );
		if ( ! empty( $plugin_pages ) ) {
			$new_pages = array();
			for ( $i=0; $i < count( $pages ); $i++ ) {
				if ( ! in_array( $pages[$i]->ID, $plugin_pages ) ) {
					$new_pages[] = $pages[$i];
				}
			}
			$pages = $new_pages;
		}
		return $pages;
	}

	/**
	 * Adds a plugin page's ID to the list of pages that need to be opened
	 * if the page ID is not already in the registry.
	 *
	 * Pages are added to this list due to the fact that any page created through
	 * this class should be excluded from the list of pages in the theme's nav
	 * bar, and we need to maintain a list of created pages in order to do this.
	 *
	 * @param int $page_id the ID of a plugin page
	 *
	 * @access private
	 * @since 0.2
	 */
	private static function _register_plugin_page( $page_id )
	{
		$plugin_pages = get_site_option( 'cb_plugin_pages', array() );
		if ( ! in_array( $page_id, $plugin_pages ) ) {
			$plugin_pages[] = $page_id;
			update_site_option( 'cb_plugin_pages', $plugin_pages );
		}
	}

	/**
	 * Creates a page for the plugin with the given name on the root blog.
	 *
	 * This makes a new page for the plugin, and adds it to the list of plugin
	 * pages, which is used to make the page visible but not shown automatically
	 * in the list of pages on a site that appears at the top of manyt hemes.
	 *
	 * An optional existing page ID can be passed in.  If this argument is given
	 * and the page ID does not map to a valid page, the page is recreated.  If
	 * the page ID represents an extant and valid page, however, no action is
	 * taken except for making sure that the page is registered in the list
	 * of plugin-created pages.
	 *
	 * @param  string $name    the name of the page to create
	 * @param  string $content the desired content of the page
	 * @param  int    $page_id the optional ID of an already created page
	 * @return int             the ID of the created page
	 *
	 * @access protected
	 * @since 0.1
	 */
	public static function create_plugin_page( $name, $content = '', $page_id = null )
	{
		$conflicts = true;
		$counter = 0;
		$page_name = $name;

		// If a page with the given ID already exists, abort early
		if ( $page_id && get_page( $page_id ) ) {
			self::_register_plugin_page( $page_id );
			return $page_id;
		}

		// Find a name for the new page that doesn't conflict with others
		while ( $conflicts ) {
			$page = get_page_by_title( $page_name );
			if ( isset( $page ) ) {
				$counter++;
				$page_name = sprintf( '%s %d', $name, $counter );
			} else {
				$conflicts = false;
			}
		}

		// Create the new page and store its ID
		$new_page = array(
			'post_author'  => ClassBlogs_Settings::get_admin_user_id(),
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_title'   => $page_name,
			'post_type'    => 'page' );
		$page_id = wp_insert_post( $new_page );
		self::_register_plugin_page( $page_id );

		return $page_id;
	}
}

ClassBlogs::register_plugin(
	'plugin_page',
	'ClassBlogs_PluginPage',
	__( 'Plugin Page', 'classblogs' ),
	__( 'Manages pages associated with a plugin.', 'classblogs' ),
	false
);

?>
