<?php

/**
 * A widget that shows a list of recent sitewide posts
 *
 * @access private
 * @package Class Blogs
 * @since 0.1
 */
class _ClassBlogs_Plugins_Aggregation_SitewidePostsWidget extends ClassBlogs_Plugins_SidebarWidget
{

	/**
	 * The length of the content excerpt in words
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _EXCERPT_LENGTH_WORDS = 15;

	/**
	 * Default options for the sitewide posts widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'max_posts'          => 5,
		'max_posts_per_blog' => 2,
		'meta_format'        => 'By %author% on %date%',
		'show_excerpt'       => false,
		'title'              => 'Recent Posts'
	);

	/**
	 * Creates the sitewide posts widget
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Recent Sitewide Posts', 'classblogs' ),
			__( 'A list of recent posts from across all student blogs', 'classblogs' ),
			'cb-sitewide-recent-posts' );
	}

	/**
	 * Displays the sitewide posts widget
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'sitewide_posts' );

		$sitewide_posts = $plugin->get_posts_for_sidebar(
			$instance['max_posts'],
			$instance['max_posts_per_blog'],
			$instance['meta_format'] );
		if ( empty( $sitewide_posts ) ) {
			return;
		}

		$this->start_widget( $args, $instance );
		echo '<ul>';

		foreach ( $sitewide_posts as $post ) {
?>
			<li class="cb-sitewide-post">
				<a href="<?php echo $post->permalink; ?>" class="cb-sitewide-post-title"><?php echo $post->title; ?></a>
				<?php if ( $post->meta ): ?>
					<p class="cb-sitewide-post-meta"><?php echo $post->meta; ?></p>
				<?php endif; ?>
				<?php if ( $instance['show_excerpt'] ): ?>
					<p class="cb-sitewide-post-excerpt"><?php echo ClassBlogs_Utils::make_post_excerpt( $post->content, self::_EXCERPT_LENGTH_WORDS ); ?></p>
				<?php endif; ?>
			</li>
<?php
		}

		echo '</ul>';
		$this->end_widget( $args );
	}

	/**
	 * Updates the sitewide posts widget
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['max_posts']          = ClassBlogs_Utils::sanitize_user_input( $new['max_posts'] );
		$instance['max_posts_per_blog'] = ClassBlogs_Utils::sanitize_user_input( $new['max_posts_per_blog'] );
		$instance['meta_format']        = ClassBlogs_Utils::sanitize_user_input( $new['meta_format'] );
		$instance['show_excerpt']       = ClassBlogs_Utils::checkbox_as_bool( $new['show_excerpt'] );
		$instance['title']              = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the sitewide posts widget
	 */
	public function form( $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_posts' ); ?>"><?php _e( 'Post Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_posts' ); ?>" name="<?php echo $this->get_field_name( 'max_posts' ); ?>" type="text" value="<?php echo esc_attr( $instance['max_posts'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_posts_per_blog' ); ?>"><?php _e( 'Posts-per-Blog Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_posts_per_blog' ); ?>" name="<?php echo $this->get_field_name( 'max_posts_per_blog' ); ?>" type="text" value="<?php echo esc_attr( $instance['max_posts_per_blog'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show Post Excerpt', 'classblogs' ); ?></label>
			<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php if ( $instance['show_excerpt'] ): ?>checked="checked"<?php endif; ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'meta_format' ); ?>"><?php _e( 'Meta Format', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'meta_format' ); ?>" name="<?php echo $this->get_field_name( 'meta_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['meta_format'] ); ?>" />
		</p>
		<div>
			<h3><?php _e( 'Format variables you can use', 'classblogs' ) ?></h3>
			<dl>
				<dt>%author%</dt>
				<dd><?php _e( "The author's name", 'classblogs' ); ?></dd>
				<dt>%blog%</dt>
				<dd><?php _e( 'The name of the blog on which the post was made', 'classblogs' ); ?></dd>
				<dt>%date%</dt>
				<dd><?php _e( 'The creation date of the post', 'classblogs' ); ?></dd>
				<dt>%time%</dt>
				<dd><?php _e( 'The creation time of the post', 'classblogs' ); ?></dd>
			</dl>
		</div>
<?php
	}
}

/**
 * The sitewide posts plugin
 *
 * This provides a main-blog-only widget that can show recent sitewide posts,
 * and also provides a user with the ability to display sitewide posts on the
 * front page of the main blog.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_SitewidePosts extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * Default options for the sitewide posts plugin
	 *
	 * @access protected
	 * @var array
	 */
	protected $default_options = array (
		'root_excerpt_words'          => 50,
		'root_show_posts'             => true,
		'root_strip_formatting'       => true,
		'root_use_excerpt'            => true
	);

	/**
	 * A list of the URLs for any pages on the main blog
	 *
	 * @access private
	 * @var array
	 */
	private $_page_urls;

	/**
	 * A list of unprocessed sitewide posts
	 *
	 * @access private
	 * @var array
	 */
	private $_sitewide_posts;

	/**
	 * Initialize WordPress hooks for the widget and the main page
	 */
	public function __construct()
	{

		parent::__construct();

		// Enable the admin menu and the sidebar widget if on the admin side,
		// and enable the root blog hooks if posts are to be shown on the root blog
		if ( ! is_admin() && $this->get_option( 'root_show_posts' ) ) {
			add_action( 'pre_get_posts', array( $this, 'initialize_root_blog_hooks' ) );
		}
		add_action( 'widgets_init', array( $this, 'enable_widget' ) );
	}

	/**
	 * Initializes the hooks required to make the root blog show sitewide posts
	 *
	 * This functionality, if required, can be overridden by other code by
	 * defining the constant CLASS_BLOGS_SHOW_SITEWIDE_POSTS_ON_FRONT_PAGE.  If
	 * it is given a value of false, the root blog will never be populated with
	 * the sitewide post data.
	 *
	 * @since 0.1
	 */
	public function initialize_root_blog_hooks()
	{

		// Allow external code to prevent the main page of the root blog from
		// being populated with the sitewide posts
		$allow_posts = true;
		if ( defined( 'CLASS_BLOGS_SHOW_SITEWIDE_POSTS_ON_FRONT_PAGE' ) ) {
			$allow_posts = CLASS_BLOGS_SHOW_SITEWIDE_POSTS_ON_FRONT_PAGE;
		}

		if ( $allow_posts && ClassBlogs_Utils::is_root_blog() && ( is_home() || is_front_page() ) ) {

			add_action( 'loop_end',   array( $this, 'reset_blog_on_loop_end' ) );
			add_action( 'loop_start', array( $this, 'restore_sitewide_post_ids' ) );
			add_action( 'the_post',   array( $this, 'use_correct_blog_for_sitewide_post' ) );
			add_filter( 'the_posts',  array( $this, '_use_sitewide_posts' ) );
			add_filter( 'page_link',  array( $this, '_use_correct_page_url' ), 10, 2 );

			// Use the post's excerpt if that option has been set
			if ( $this->get_option( 'root_use_excerpt' ) ) {
				add_filter( 'the_content', array( $this, '_use_post_excerpt' ) );
			}
		}
	}

	/**
	 * Replaces the actual posts of the root blog with the sitewide posts
	 *
	 * @param  array $actual_posts the posts made on the root blog
	 * @return array               the sitewide posts
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _use_sitewide_posts( $actual_posts )
	{

		// Get a list of the URLs of any pages on the main blog, to avoid having
		// the sitewide posts interfere with the page URLs, which can happen
		// when the ID of a post on another blog equals that of a page on the root blog
		$this->_page_urls = array();
		foreach ( get_pages() as $page ) {
			$this->_page_urls[$page->ID] = get_page_link( $page->ID );
		}

		// Keep a record of the actual root posts for later use
		$this->root_blog_posts = $actual_posts;

		// Set correct pagination and page count information
		global $wp_query;
		$current_page = max( 1, (int) $wp_query->query_vars['paged'] );
		$per_page = (int) get_option( 'posts_per_page' );
		$wp_query->max_num_pages = ceil( count( $this->get_sitewide_posts() ) / $per_page );

		// Return the sitewide posts, prevent ID conflicts before doing so
		$sw_posts = array_slice( $this->get_sitewide_posts(), ($current_page - 1) * $per_page, $per_page );
		$this->prevent_sitewide_post_id_conflicts( $sw_posts );
		return $sw_posts;
	}

	/**
	 * Uses the sitewide post's excerpt as its content
	 *
	 * This is only called if the user has selected to show sitewide posts on
	 * the main page and use their excerpts.
	 *
	 * @param  string $content the post's full content
	 * @return string          the post's precomputed excerpt
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _use_post_excerpt( $content )
	{
		global $post;

		$excerpt = $post->cb_sw_excerpt;
		return ( $excerpt ) ? $excerpt : $content;
	}

	/**
	 * Uses the correct URL for any pages on the root blog
	 *
	 * By replacing the normal posts with sitewide posts, errors can occur where
	 * a page on the root blog's URL will be that of a post from another blog
	 * that shares the same ID.  By getting all page URLs before replacing
	 * the posts, we can provide each page with its proper URL in this method.
	 *
	 *  @param  string $url the current URL of the page
	 *  @param  int    $id  the current page's ID
	 *  @return string      the correct page URL
	 *
	 *  @access private
	 *  @since 0.1
	 */
	function _use_correct_page_url( $url, $id )
	{
		return ( empty( $this->_page_urls[$id] ) ) ? $url : $this->_page_urls[$id];
	}

	/**
	 * Returns the a post's tag list with each tag pointing to the sitewide tags page
	 *
	 * This makes it so that each tag shown with a post will go to the sitewide
	 * tags page showing usage of the tag across the site, instead of just on the
	 * blog on which the post was made.
	 *
	 * @param  string $tags   the markup for the current tag list
	 * @param  string $before a string coming before the tag list
	 * @param  string $sep    the separator used to space the tags
	 * @param  string $after  a string coming after the tag list
	 * @return string         markup for the sitewide tag list
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _show_sitewide_tag_list( $tags, $before = "", $sep = ', ', $after = "" )
	{

		global $post;
		$new_tags = array();

		//  Display the link to the sitewide usage page for each used tag
		foreach ( $this->getTagsForPost( $post->ID, $post->from_blog ) as $tag ) {
			$new_tags[] = sprintf( '<a href="%s">%s</a>',
				$tag->name,
				ClassBlogs_Plugins_Aggregation_SitewideTags::get_tag_page_url( $tag->slug ) );
		}

		return $before . join( $sep, $new_tags ) . $after;
	}

	/**
	 * Returns an array of sitewide posts
	 *
	 * These posts returned are limited only by the options of the sitewide
	 * aggregator, which can restrict which blogs populate the sitewide tables.
	 * The returned posts are in descending order by their published date.
	 *
	 * Since the posts will be from multiple different blogs, certain values
	 * are precomputed and added each post.  The values are as follows:
	 *
	 *     cb_sw_permalink - the permalink for the post
	 *     cb_sw_excerpt   - the post's excerpt
	 *
	 * @return array a list of sitewide posts
	 *
	 * @since 0.1
	 */
	public function get_sitewide_posts()
	{

		// Return the cached version if we've already built the sitewide post list
		if ( isset( $this->_sitewide_posts ) ) {
			return $this->_sitewide_posts;
		}

		global $wpdb;
		$posts = array();
		$use_root_excerpt = $this->get_option( 'root_use_excerpt' );
		$excerpt_words = $this->get_option( 'root_excerpt_words' );

		foreach ( $wpdb->get_results( "SELECT * FROM {$this->sw_tables->posts} ORDER BY post_date DESC" ) as $post ) {

			// Precompute each post's permalink
			$post->cb_sw_permalink = get_blog_permalink( $post->from_blog, $post->ID );

			// Precompute each post's excerpt
			if ( $use_root_excerpt ) {
				$post->cb_sw_excerpt = ClassBlogs_Utils::make_post_excerpt( $post->post_content, $excerpt_words );
			}

			$posts[] = $post;
		}

		$this->_sitewide_posts = $posts;
		return $posts;
	}

	/**
	* Gets the sitewide post with the newest creation date.
	*
	* @return object a single row from the posts table
	*
	* @since 0.1
	*/
	public function get_newest_post()
	{
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT post_date FROM {$this->sw_tables->posts} ORDER BY post_date DESC LIMIT 1" ) );
	}

	/**
	 * Gets the sitewide post with the oldest creation date.
	 *
	 * @return object a single row from the posts table
	 *
	 * @since 0.1
	 */
	public function get_oldest_post()
	{
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT post_date FROM {$this->sw_tables->posts} ORDER BY post_date LIMIT 1" ) );
	}

	/**
	 * Returns a subset of the sitewide posts, filtered by user and date.
	 *
	 * @param  int    $user_id    the ID of the desired post author
	 * @param  object $start_date the start date of the date filter window
	 * @param  object $end_date   the end date of the date filter window
	 * @return array              a list of the posts matching the given filters
	 *
	 * @since 0.1
	 */
	public function filter_posts( $user_id, $start_date, $end_date )
	{
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->sw_tables->posts} WHERE post_author=%s AND post_date >= %s AND post_date <= %s",
			$user_id,
			$start_date->format( 'Ymd' ),
			$end_date->format( 'Ymd' ) ) );
	}

	/**
	 * Gets a list of recent posts formatted for display in a sidebar widget
	 *
	 * The array of returned posts contains custom object instances with the
	 * following properties that can be used by the sidebar:
	 *
	 *      content   - the content of the post
	 *      meta      - a string describing the post's meta, constructed from
	 *                  the meta formatting string passed to this method
	 *      permalink - the permalink URL for the post
	 *      title     - the title of the post
	 *
	 * @param  int    $max_posts          the maximum number of posts to return
	 * @param  int    $max_posts_per_blog the most posts allowed per blog
	 * @param  string $meta_format        the formatting string for the post meta
	 * @return array                      an array of formatted posts
	 *
	 * @since 0.1
	 */
	public function get_posts_for_sidebar( $max_posts, $max_posts_per_blog, $meta_format )
	{
		$posts = array();
		$raw_posts = $this->limit_sitewide_resources(
			$this->get_sitewide_posts(),
			$max_posts,
			$max_posts_per_blog );

		foreach ( $raw_posts as $post ) {

			// Create a string for the post metadata
			$meta = "";
			if ( $meta_format ) {
				$user_data = get_userdata( $post->post_author );
				$blog = sprintf( '<a href="%s" class="cb-sitewide-post-blog">%s</a>',
					get_blogaddress_by_id( $post->from_blog ),
					get_blog_option( $post->from_blog, 'blogname' ) );
				$meta = ClassBlogs_Utils::format_user_string(
					$meta_format,
					array(
						'author' => $user_data->display_name,
						'blog'   => $blog,
						'date'   => mysql2date( get_option( 'date_format' ), $post->post_date ),
						'time'   => mysql2date( get_option( 'time_format' ), $post->post_date ) ),
					'cb-sitewide-post' );
			}

			$posts[] = (object) array(
				'content'   => $post->post_content,
				'meta'      => $meta,
				'permalink' => $post->cb_sw_permalink,
				'title'     => $post->post_title );
		}

		return $posts;
	}

	/**
	 * Enables the sidebar widget for showing recent sitewide posts
	 *
	 * @since 0.1
	 */
	public function enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_Aggregation_SitewidePostsWidget' );
	}

	/**
	 * Configures the plugin's admin page
	 *
	 * @since 0.1
	 */
	public function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Sitewide Posts', 'classblogs' ), array( $this, 'admin_page' ) );
	}

	/**
	 * Handles the admin page logic for the sitewide posts plugin
	 *
	 * @since 0.1
	 */
	public function admin_page()
	{

		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$options = $this->get_options();
			$options['root_excerpt_words']     = ClassBlogs_Utils::sanitize_user_input( $_POST['root_excerpt_words'] );
			$options['root_show_posts']        = ClassBlogs_Utils::checkbox_as_bool( $_POST['root_show_posts'] );
			$options['root_strip_formatting']  = ClassBlogs_Utils::checkbox_as_bool( $_POST['root_strip_formatting'] );
			$options['root_use_excerpt']       = ClassBlogs_Utils::checkbox_as_bool( $_POST['root_use_excerpt'] );
			$this->update_options( $options );

			echo '<div id="message" class="updated fade"><p>' . __( 'Your sitewide posts options have been updated', 'classblogs' ) . '</p></div>';
		}
	?>
		<div class="wrap">

			<h2><?php _e( 'Sitewide Posts Configuration', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This page allows you to control options that will affect the display of sitewide posts on the home page of the root blog.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Show Sitewide Posts On Root Blog', 'classblogs' ); ?></th>
							<td>
								<input type="checkbox" name="root_show_posts" id="root-show-posts" <?php echo $this->option_to_selected_attribute( 'root_show_posts' ); ?> />
								<label for="root-show-posts"><?php _e( 'Enabled', 'classblogs' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Remove Post Formatting', 'classblogs' ); ?></th>
							<td>
								<input type="checkbox" name="root_strip_formatting" id="root-strip-formatting" <?php echo $this->option_to_selected_attribute( 'root_strip_formatting' ); ?> />
								<label for="root-strip-formatting"><?php _e( 'Enabled', 'classblogs' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Only Show Post Excerpt', 'classblogs' ); ?></th>
							<td>
								<input type="checkbox" name="root_use_excerpt" id="root-use-excerpt" <?php echo $this->option_to_selected_attribute( 'root_use_excerpt' ); ?> />
								<label for="root-use-excerpt"><?php _e( 'Enabled', 'classblogs' ) ?></label><br /><br />
								<input type="text" name="root_excerpt_words" id="root-excerpt-words" value="<?php echo esc_attr( $this->get_option( 'root_excerpt_words' ) ); ?>" size="4" /><br />
								<label for="root-excerpt-words"><?php _e( 'Excerpt length (in words)', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Update Sitewide Posts Options', 'classblogs' ); ?>" /></p>
			</form>
		</div>
	<?php
	}

	/**
	 * Provides a list of posts made by each user of the blog
	 *
	 * The returned list of posts is in descending order by the published date
	 * of the most recent post written by each user.
	 *
	 * The returned post list contains objects with the following properties:
	 *
	 *     posts       - an array of posts
	 *     total_posts - the total number of posts made by the user
	 *     user_id     - the user's database ID
	 *
	 * @param  int   $limit the most results per blog to return
	 * @return array        the list of posts by user
	 *
	 * @since 0.1
	 */
	public function get_posts_by_user( $limit = 5 )
	{
		$by_user = array();

		// Build the list of posts by user
		foreach ( $this->get_sitewide_posts() as $post ) {
			if ( ! array_key_exists( $post->post_author, $by_user ) ) {
				$by_user[$post->post_author] = (object) array(
					'posts'       => array(),
					'total_posts' => 0,
					'user_id'     => $post->post_author );
			}
			if ( $by_user[$post->post_author]->total_posts < $limit ) {
				$by_user[$post->post_author]->posts[] = $post;
			}
			$by_user[$post->post_author]->total_posts++;
		}

		// Sort the posts by the published date of the first post of each user
		usort( $by_user, array( $this, '_sort_posts_by_user_by_date' ) );
		return array_values( $by_user );
	}

	/**
	 * Sorts a list of posts by user by the published date of the first post
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _sort_posts_by_user_by_date( $a, $b ) {
		return strcasecmp(
			$this->_get_first_post_published_date( $b->posts ),
			$this->_get_first_post_published_date( $a->posts ) );
	}

	/**
	 * Returns the creation date of the first post in a list of posts
	 *
	 * @param  array  $posts a list of posts
	 * @return string        the creation date of the first post
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_first_post_published_date( $posts ) {
		if ( count( $posts ) ) {
			return $posts[0]->post_date;
		} else {
			return '0';
		}
	}
}

ClassBlogs::register_plugin( 'sitewide_posts', new ClassBlogs_Plugins_Aggregation_SitewidePosts() );

?>
