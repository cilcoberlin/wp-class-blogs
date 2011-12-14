<?php

/**
 * A widget that shows a tag cloud built from all of the tags used on the site.
 *
 * The tag cloud, as with the core WordPress tag cloud, can be configured to
 * only display tags that are used more than an arbitrary number of times.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewideTagsWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_Aggregation_SitewideTagsWidget extends ClassBlogs_Plugins_Widget
{

	/**
	 * Default options for the sitewide tag cloud widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'max_font_size' => 22,
		'min_font_size' => 8,
		'min_usage'     => 1,
		'title' 	    => 'Sitewide Tag Cloud'
	);

	/**
	 * Creates the sitewide tag cloud widget.
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Sitewide Tag Cloud', 'classblogs' ),
			__( 'A tag cloud made from tags on all blogs on the site', 'classblogs' ),
			'cb-sitewide-tag-cloud' );
	}

	/**
	 * Displays the sitewide tag cloud widget.
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewideTags to get all sitewide tags
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'sitewide_tags' );
		$most_usage = $plugin->get_max_usage_count();
		$least_usage = $plugin->get_min_usage_count();
		if ( ! $most_usage && ! $least_usage ) {
			return;
		}

		$this->start_widget( $args, $instance );

		$most_usage = max( $most_usage, $instance['min_usage'] );
		$least_usage = max( $least_usage, $instance['min_usage'] );
		$max_font = $instance['max_font_size'];
		$min_font = $instance['min_font_size'];

		// Display each tag as a weighted link a list
		echo "<div class='tagcloud'>";
		foreach ( $plugin->get_tags_for_tag_cloud( $instance['min_usage'] ) as $tag ) {
			printf( '<a class="tag-link" href="%s" rel="tag" title="%s" style="font-size: %dpt;">%s</a> ',
				esc_url( $tag->url ),
				esc_attr( sprintf( _n( '%d topic', '%d topics', $tag->count ), $tag->count ) ),
				$min_font + floor( ( ( $tag->count - $least_usage ) / max( $most_usage - $least_usage, 1 ) ) * ( $max_font - $min_font ) ),
				esc_html( $tag->name ) );
		}
		echo "</div>";

		$this->end_widget( $args );
	}

	/**
	 * Updates the sitewide tag cloud widget.
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['max_font_size'] = absint( ClassBlogs_Utils::sanitize_user_input( $new['max_font_size'] ) );
		$instance['min_font_size'] = absint( ClassBlogs_Utils::sanitize_user_input( $new['min_font_size'] ) );
		$instance['min_usage']     = absint( ClassBlogs_Utils::sanitize_user_input( $new['min_usage'] ) );
		$instance['title']         = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the sitewide tag cloud widget.
	 */
	public function form( $instance )
	{

		$instance = $this->maybe_apply_instance_defaults( $instance );
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'title' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'min_font_size' ); ?>"><?php _e( 'Minimum Font Size', 'classblogs' ); ?></label>
			<input size="2" id="<?php echo $this->get_field_id( 'min_font_size' ); ?>" name="<?php echo $this->get_field_name( 'min_font_size' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'min_font_size' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_font_size' ); ?>"><?php _e( 'Maximum Font Size', 'classblogs' ); ?></label>
			<input size="2" id="<?php echo $this->get_field_id( 'max_font_size' ); ?>" name="<?php echo $this->get_field_name( 'max_font_size' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'max_font_size' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'min_usage' ); ?>"><?php _e( 'Minimum Usage Count', 'classblogs' ); ?></label>
			<input size="2" id="<?php echo $this->get_field_id( 'min_usage' ); ?>" name="<?php echo $this->get_field_name( 'min_usage' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'min_usage' ); ?>" />
		</p>
<?php
	}
}

/**
 * A plugin that displays information on the tags used on any blog on the site.
 *
 * This provides a widget available on the main blog only that displays a tag
 * cloud built from the tags used on all blogs on the site.  Clicking on any
 * one of these tags will take a user to a page showing all posts from across
 * the site that use the clicked-on tag.
 *
 * This plugin also provides a basic interface to the sitewide tag data.  An
 * example of accessing this data through this plugin is as follows:
 *
 *    // The tags 'one' and 'two' are used on a post on blog 1.  The tags
 *    // 'two' and 'three' are used on a post on blog 2.
 *    $sw_tags = ClassBlogs::get_plugin( 'sitewide_tags' );
 *
 *    assert( $sw_tags->get_min_usage_count() === 1 );
 *    assert( $sw_tags->get_max_usage_count() === 2 );
 *
 *    $all = $sw_tags->get_sitewide_tags();
 *    assert( $all['one']['count'] === 1 );
 *    assert( $all['two']['count'] === 2 );
 *    assert( count( $all ) === 3 );
 *
 *    $tagged = $sw_tags->get_tagged_posts( 'two' );
 *    assert( count( $tagged ) === 2 );
 *
 *    echo "Tag page for 'two' is " . $sw_tags->get_tag_page_url( 'two' );
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewideTags
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_SitewideTags extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * The name of the GET query variable that contains the name of a tag.
	 *
	 * This is used to allow the sitewide tag usage page to display usage
	 * for the tag specified in this query variable.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _TAG_QUERY_VAR_NAME = 'tag';

	/**
	 * The default name for the tag list page.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _TAG_PAGE_DEFAULT_NAME = 'Sitewide Tags';

	/**
	 * Default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'tag_page_id' => null
	);

	/**
	 * Cached sitewide tags.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_sitewide_tags;

	/**
	 * Information on the tag displayed on a sitewide tags page.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_current_tag;

	/**
	 * Initialize the tag list.
	 */
	function __construct()
	{

		parent::__construct();

		add_action( 'init',          array( $this, '_ensure_tag_list_page_is_created' ) );
		add_action( 'pre_get_posts', array( $this, '_maybe_enable_tag_list_page'  ) );
		add_action( 'widgets_init',  array( $this, '_enable_widget' ) );
	}

	/**
	 * Enables the tag list page if the the user is on that page and viewing a tag.
	 *
	 * This is called before the posts are obtained, and checks to see whether
	 * the user is on the private page created by this plugin and whether the
	 * tag-slug query variable is present.  If this is the case, the tags page
	 * is rendered as a tag archive page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _maybe_enable_tag_list_page() {


		//  Only register the filters and tags if we're on the sitewide tags page
		if ( ClassBlogs::is_page( $this->get_option( 'tag_page_id' ) ) ) {

			// Get information on the tag being displayed
			global $wpdb;
			$slug = $_GET[self::_TAG_QUERY_VAR_NAME];
			$this->_current_tag = array(
				'name' => $wpdb->get_var( $wpdb->prepare( "SELECT name FROM {$this->sw_tables->tags} WHERE slug = %s", $slug ) ),
				'slug' => $slug
			);

			add_action( 'loop_end',         array( $this, 'reset_blog_on_loop_end' ) );
			add_action( 'loop_start',       array( $this, 'restore_sitewide_post_ids' ) );
			add_action( 'the_post',         array( $this, 'use_correct_blog_for_sitewide_post' ) );
			add_filter( 'the_posts',        array( $this, '_fake_tag_archive_page' ) );
			add_filter( 'single_tag_title', array( $this, '_set_tag_title' ) );
			add_filter( 'wp_title',         array( $this, '_set_page_title' ), 10, 2);
		}
	}

	/**
	 * Creates a page that shows all uses of a given tag across the site.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _ensure_tag_list_page_is_created()
	{
		if ( ClassBlogs_Utils::is_root_blog() ) {
			$current_page = $this->get_option( 'tag_page_id' );
			$page_id = $this->create_plugin_page( self::_TAG_PAGE_DEFAULT_NAME, $current_page );
			if ( $page_id != $current_page ) {
				$this->update_option( 'tag_page_id', $page_id );
			}
		}
	}

	/**
	 * Enables the sitewide tag cloud widget.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_Aggregation_SitewideTagsWidget' );
	}

	/**
	 * Returns the URL for the tag-list page.
	 *
	 * @return string the URL for the tag-list page
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _get_tag_page_url()
	{
		switch_to_blog( ClassBlogs_Settings::get_root_blog_id() );
		$url = get_permalink( $this->get_option( 'tag_page_id' ) );
		restore_current_blog();
		return $url;
	}

	/**
	 * Makes WordPress treat a sitewide tag page as a tag archive.
	 *
	 * This sets internal flags used by WordPress to make it think that it is
	 * on a tag archive page and replaces the page content with a list of posts
	 * that use the currently set tag.
	 *
	 * @param  array $posts the current posts associated with the page
	 * @return array        all posts using the current tag
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _fake_tag_archive_page( $posts )
	{

		global $wp_query;

		// Make the current page appear to be a tag archive to WordPress
		$wp_query->is_archive = true;
		$wp_query->is_page    = false;
		$wp_query->is_tag	  = true;

		// Further convince the page that it is a tag archive by providing
		// providing dummy data that a tag archive expects
		$wp_query->query_vars['tag_id'] = 1;
		$wp_query->tax_query = (object) array( 'queries' => array() );

		// Provide values for data expected by a tag page
		$wp_query->queried_object = (object) array(
			'name'    => $this->_current_tag['name'],
			'slug'    => $this->_current_tag['slug'],
			'term_id' => 0
		);

		// Use the tagged posts and prevent ID conflicts
		$tagged_posts = $this->get_tagged_posts( $this->_current_tag['slug'] );
		$this->prevent_sitewide_post_id_conflicts( $tagged_posts );
		return $tagged_posts;
	}

	/**
	 * Sets the correct title for a sitewide tags archive page.
	 *
	 * @param  object $tag the current tag
	 * @return string      the current tag's name
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _set_tag_title( $tag )
	{
		return $this->_current_tag['name'];
	}

	/**
	 * Set the sitewide tag page title to reflect the name of the current tag.
	 *
	 * @param  string $title     the current page's title
	 * @param  string $separator the title separator character
	 * @return string            the page title for the current tag
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _set_page_title( $title, $separator=":" )
	{
		return sprintf( ' %s %s ', $this->_current_tag['name'], $separator );
	}

	/**
	 * Calculate the maximum and minimum sitewide tag usage counts.
	 *
	 * The counts are returned in an array with a 'max' and 'min' key.  If no
	 * tag usage was found across the site, these values will be 0.
	 *
	 * @return array the max and min counts
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_tag_counts()
	{
		// Use cached information if possible
		$cached = $this->get_site_cache( 'tag_counts' );
		if ( $cached !== null ) {
			return $cached;
		}

		//  Get the max and min tag usage counts
		$counts = array();
		foreach ( $this->get_sitewide_tags() as $tag  ) {
			$counts[] = $tag['count'];
		}
		if ( count( $counts ) ) {
			 $tag_min = min( $counts );
			 $tag_max = max( $counts );
		} else {
			$tag_min = $tag_max = 0;
		}
		$max_min = array(
			'max' => $tag_max,
			'min' => $tag_min );
		$this->set_site_cache( 'tag_counts', $max_min );
		return $max_min;
	}

	/**
	 * Gets the maximum or minimum tag usage count.
	 *
	 * This is a utility method that tries to get the key from the cache first.
	 * If the cache is not built, it fetches the value, builds the cache and
	 * then returns the requested key's value.
	 *
	 * Valid keys that can be passed to this function are 'max', for the maximum
	 * count, or 'min' for the minimum.  Any other values will result in a 0
	 * being returned.
	 *
	 * @param  string $key the cached count key
	 * @return int         the tag usage count
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_usage_count( $key )
	{
		$counts = $this->_get_tag_counts();
		if ( array_key_exists( $key, $counts ) ) {
			return (int) $counts[$key];
		} else {
			return 0;
		}
	}

	/**
	 * Returns the URL for the page showing a list of all posts on the site that
	 * use the given tag.
	 *
	 * @param  string $slug the tag's slug
	 * @return string       the URL of the tag-list page for the tag
	 *
	 * @since 0.1
	 */
	public function get_tag_url( $slug )
	{
		if ( ! property_exists( $this, '_tag_page_url' ) ) {
			$this->_tag_page_url = $this->_get_tag_page_url();
		}
		return sprintf( '%s?%s=%s',
			$this->_tag_page_url,
			self::_TAG_QUERY_VAR_NAME,
			$slug );
	}

	/**
	 * Returns a list of the sitewide tags.
	 *
	 * The returned array is sorted alphabetically by the slug of each tag, and
	 * each array element has a key of the tag slug and a value of an array
	 * containing the following keys:
	 *
	 *     count - the usage count of the tag
	 *     name  - the name of the tag
	 *
	 * @return array a list of the sitewide tags
	 *
	 * @since 0.1
	 */
	public function get_sitewide_tags()
	{

		// Return the cached version if we've already built the sitewide tags list
		$cached = $this->get_site_cache( 'tags' );
		if ( $cached !== null ) {
			return $cached;
		}

		global $wpdb;
		$tags = array();
		$raw_tags = $wpdb->get_results( $wpdb->prepare( "
			SELECT name, slug, count FROM {$this->sw_tables->tags}" ) );

		// Create a record of the tags keyed by the slug, with information on
		// the usage count and the full name of the tag
		foreach ( $raw_tags as $tag ) {
			$tags[$tag->slug] = array(
				'count' => $tag->count,
				'name'  => $tag->name );
		}

		//  Cache and return the tag list sorted by tag name
		ksort( $tags );
		$this->set_site_cache( 'tags', $tags );
		return $tags;
	}

	/**
	 * Returns the lowest usage count of the sitewide tags.
	 *
	 * @return int the usage count of the least-used tag
	 *
	 * @since 0.1
	 */
	public function get_min_usage_count()
	{
		return $this->_get_usage_count( 'min' );
	}

	/**
	 * Returns the highest usage count of the sitewide tags.
	 *
	 * @return int the usage count of the most-used tag
	 *
	 * @since 0.1
	 */
	public function get_max_usage_count()
	{
		return $this->_get_usage_count( 'max' );
	}

	/**
	 * Returns an array of tags for use by the sitewide tag cloud widget.
	 *
	 * If the usage count of any tag is less than the given threshold, that
	 * tag is excluded from the returned list.  Each tag in the returned array
	 * is an object with the following properties:
	 *
	 *     count - the usage count of the tag
	 *     name  - the tag's full name
	 *     url   - the URL for viewing the tag on the sitewide tag usage page
	 *
	 * The returned array's tags are ordered alphabetically by the tag name.
	 *
	 * @param  int   $threshold the minimum usage count required for a tag
	 * @return array            the list of tags, ordered alphabetically
	 *
	 * @since 0.1
	 */
	public function get_tags_for_tag_cloud( $threshold )
	{
		// Use cached values if possible
		$cached = $this->get_site_cache( 'cloud' );
		if ( $cached !== null ) {
			return $cached;
		}

		$tags = array();
		foreach ( $this->get_sitewide_tags() as $slug => $tag ) {
			if ( $tag['count'] >= $threshold ) {
				$tags[] = (object) array(
					'count' => $tag['count'],
					'name'  => $tag['name'],
					'url'   => $this->get_tag_url( $slug ) );
			}
		}
		$this->set_site_cache( 'cloud', $tags );
		return $tags;
	}

	/**
	 * Returns all sitewide posts that use the given tag slug.
	 *
	 * @param  string $slug the tag of the slug
	 * @return array        a list of posts using the given tag
	 *
	 * @since 0.1
	 */
	public function get_tagged_posts( $slug )
	{

		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare ( "
			SELECT p.*, p.cb_sw_blog_id
			FROM {$this->sw_tables->posts} AS p, {$this->sw_tables->tags} AS t, {$this->sw_tables->tag_usage} AS tu
			WHERE t.slug = %s AND t.term_id = tu.uses_tag AND tu.post_id = p.ID AND tu.cb_sw_blog_id = p.cb_sw_blog_id
			ORDER BY post_date DESC ",
			$slug ) );
	}
}

ClassBlogs::register_plugin( 'sitewide_tags', new ClassBlogs_Plugins_Aggregation_SitewideTags() );

?>
