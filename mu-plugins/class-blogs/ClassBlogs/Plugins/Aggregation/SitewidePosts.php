<?php

/**
 * A widget that shows a list of recent sitewide posts.
 *
 * An admin user is able to control how the posts are displayed using a simple
 * string template using placeholder variables chosen from a list.  The total
 * number of posts displayed, as well as the maximum number of posts allowed
 * per blog, can also be controlled via the widget's admin panel.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewidePostsWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_Aggregation_SitewidePostsWidget extends ClassBlogs_Plugins_Widget
{

	/**
	 * The length of the content excerpt in words.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _EXCERPT_LENGTH_WORDS = 15;

	/**
	 * Default options for the sitewide posts widget.
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
	 * The name of the plugin.
	 */
	protected function get_name()
	{
		return __( 'Recent Sitewide Posts', 'classblogs' );
	}

	/**
	 * The description of the plugin.
	 */
	protected function get_description()
	{
		return __( 'A list of recent posts from across all student blogs', 'classblogs' );
	}

	/**
	 * Displays the sitewide posts widget.
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewidePosts to get all sitewide posts
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'sitewide_posts' );

		$sitewide_posts = $plugin->get_posts_for_widget(
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
				<a href="<?php echo esc_url( $post->permalink ); ?>" class="cb-sitewide-post-title"><?php echo esc_html( $post->title ); ?></a>
				<?php if ( $post->meta ): ?>
					<p class="cb-sitewide-post-meta"><?php echo $post->meta; ?></p>
				<?php endif; ?>
				<?php if ( $instance['show_excerpt'] ): ?>
					<p class="cb-sitewide-post-excerpt"><?php echo esc_html( ClassBlogs_Utils::make_post_excerpt( $post->content, self::_EXCERPT_LENGTH_WORDS ) ); ?></p>
				<?php endif; ?>
			</li>
<?php
		}

		echo '</ul>';
		$this->end_widget( $args );
	}

	/**
	 * Updates the sitewide posts widget.
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['max_posts']          = absint( ClassBlogs_Utils::sanitize_user_input( $new['max_posts'] ) );
		$instance['max_posts_per_blog'] = absint( ClassBlogs_Utils::sanitize_user_input( $new['max_posts_per_blog'] ) );
		$instance['meta_format']        = ClassBlogs_Utils::sanitize_user_input( $new['meta_format'] );
		$instance['show_excerpt']       = ClassBlogs_Utils::checkbox_as_bool( $new, 'show_excerpt' );
		$instance['title']              = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the sitewide posts widget.
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
			<label for="<?php echo $this->get_field_id( 'max_posts' ); ?>"><?php _e( 'Post Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_posts' ); ?>" name="<?php echo $this->get_field_name( 'max_posts' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'max_posts' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_posts_per_blog' ); ?>"><?php _e( 'Posts-per-Blog Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_posts_per_blog' ); ?>" name="<?php echo $this->get_field_name( 'max_posts_per_blog' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'max_posts_per_blog' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show Post Excerpt', 'classblogs' ); ?></label>
			<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php if ( $this->safe_instance_attr( $instance, 'show_excerpt' ) ): ?>checked="checked"<?php endif; ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'meta_format' ); ?>"><?php _e( 'Meta Format', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'meta_format' ); ?>" name="<?php echo $this->get_field_name( 'meta_format' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'meta_format' ); ?>" />
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
 * A plugin that displays data on all of the posts published on the site.
 *
 * This plugin provides two different ways of viewing this sitewide data.  The
 * first is a widget that can only appear on the root blog that displays a list
 * of recent posts.  The second is an admin page available to a professor via
 * the class-blogs admin menu group that shows a list of all student posts.
 *
 * In addition to these WordPress-level functions, this plugin also provides
 * basic programmatic access to the sitewide post data.  An example of using this
 * plugin in this manner is as follows:
 *
 *     // A user with a user ID of 2 makes three posts on their blog, with ten
 *     // minutes passing between each post.  Five minutes later, another
 *     // user with a user ID of 3 makes four posts on their blog, with
 *     // ten minutes passing between each post.
 *     $sw_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
 *
 *     $all = $sw_posts->get_sitewide_posts();
 *     assert( count( $all ) === 7 );
 *
 *     $filtered = $sw_posts->filter_posts( 2 );
 *     assert( count( $filtered ) === 3 );
 *
 *     $newest = $sw_posts->get_newest_post();
 *     $oldest = $sw_posts->get_oldest_post();
 *     assert( $newest->post_date > $oldest->post_date );
 *     assert( $oldest->user_id === 2 );
 *     assert( $newest->user_id === 3 );
 *
 *     $by_user = $sw_posts->get_posts_by_user();
 *     assert( count( $by_user ) === 2 );
 *     assert( $by_user[0]->user_id === 3 );
 *     assert( $by_user[1]->user_id === 2 );
 *     assert( count( $by_user[0]->posts ) === 4 );
 *     assert( count( $by_user[1]->posts ) === 3 );
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewidePosts
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_SitewidePosts extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * Default options for the sitewide posts plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array (
		'root_excerpt_words'    => 50,
		'root_show_posts'       => true,
		'root_strip_formatting' => true,
		'root_use_excerpt'      => true
	);

	/**
	 * Admin media files.
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $admin_media = array(
		'css' => array( 'sitewide-posts.css' )
	);

	/**
	 * The number of posts to show per page on the professor's admin page.
	 *
	 * @var int
	 * @since 0.1
	 */
	const POSTS_PER_ADMIN_PAGE = 20;

	/**
	 * A list of the URLs for any pages on the main blog.
	 *
	 * This is used to prevent sitewide post data injected in the loop from
	 * breaking the URLs in links to pages on the blog, which can occur when
	 * the ID of a sitewide post from another blog is the same as that of a
	 * page on the current blog.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_page_urls;

	/**
	 * Initialize WordPress hooks for the widget and the main page.
	 */
	public function __construct()
	{

		parent::__construct();

		// Enable the admin menu and the widget if on the admin side,
		// and enable the root blog hooks if posts are to be shown on the root blog
		if ( ! is_admin() && $this->get_option( 'root_show_posts' ) ) {
			add_action( 'pre_get_posts', array( $this, '_initialize_root_blog_hooks' ) );
		}
		add_action( 'widgets_init', array( $this, '_enable_widget' ) );
	}

	/**
	 * Initializes the hooks required to make the root blog show sitewide posts.
	 *
	 * This functionality, if required, can be overridden by other code by
	 * defining the constant CLASS_BLOGS_SHOW_SITEWIDE_POSTS_ON_FRONT_PAGE.  If
	 * it is given a value of false, the root blog will never be populated with
	 * the sitewide post data.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _initialize_root_blog_hooks()
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
	 * Replaces the actual posts of the root blog with the sitewide posts.
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
	 * Uses the sitewide post's excerpt as its content.
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
	 * Uses the correct URL for any pages on the root blog.
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
	 * Sorts a list of posts by user by the published date of the first post.
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
	 * Returns the creation date of the first post in a list of posts.
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

	/**
	 * Enables the widget for showing recent sitewide posts.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_Aggregation_SitewidePostsWidget' );
	}

	/**
	 * Configures the plugin's admin pages.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Sitewide Post Options', 'classblogs' ), array( $this, '_options_admin_page' ) );
		$admin->add_admin_page( $this->get_uid(), __( 'Student Posts', 'classblogs' ), array( $this, '_posts_admin_page' ) );
	}

	/**
	 * Handles the admin page logic for the sitewide posts plugin.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _options_admin_page()
	{

		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$options = $this->get_options();
			$options['root_excerpt_words']     = absint( ClassBlogs_Utils::sanitize_user_input( $_POST['root_excerpt_words'] ) );
			$options['root_show_posts']        = ClassBlogs_Utils::checkbox_as_bool( $_POST, 'root_show_posts' );
			$options['root_strip_formatting']  = ClassBlogs_Utils::checkbox_as_bool( $_POST, 'root_strip_formatting' );
			$options['root_use_excerpt']       = ClassBlogs_Utils::checkbox_as_bool( $_POST, 'root_use_excerpt' );
			$this->update_options( $options );

			ClassBlogs_Admin::show_admin_message( __( 'Your sitewide post options have been updated', 'classblogs' ) );
		}
	?>
		<div class="wrap">

			<h2><?php _e( 'Sitewide Post Options', 'classblogs' ); ?></h2>

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
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Sitewide Post Options', 'classblogs' ); ?>" /></p>
			</form>
		</div>
	<?php
	}

	/**
	 * Shows a professor a list of student posts.
	 *
	 * @uses ClassBlogs_Plugins_StudentBlogList to get student blog URLs
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _posts_admin_page()
	{

		// Create a lookup table for student names and blogs keyed by user ID
		$students = array();
		$student_blogs = ClassBlogs::get_plugin( 'student_blogs' );
		foreach ( $student_blogs->get_student_blogs() as $blog ) {
			$user_data = get_userdata( $blog->user_id );
			$students[$blog->user_id] = array(
				'blog_url' => $blog->url,
				'name' => sprintf( '%s %s', $user_data->first_name, $user_data->last_name ) );
		}

		// Paginate the data, restricting the data set to student-only posts
		$student_posts = array();
		foreach ( $this->get_sitewide_posts() as $post ) {
			if ( array_key_exists( $post->post_author, $students ) ) {
				$student_posts[] = $post;
			}
		}
		$paginator = new ClassBlogs_Paginator( $student_posts, self::POSTS_PER_ADMIN_PAGE );
		$current_page = ( array_key_exists( 'paged', $_GET ) ) ? absint( $_GET['paged'] ) : 1;
?>
		<div class="wrap">

			<h2><?php _e( 'Student Posts', 'classblogs' );  ?></h2>

			<p>
				<?php _e( 'This page allows you to view all of the posts that your students have published.', 'classblogs' );  ?>
			</p>

			<?php $paginator->show_admin_page_links( $current_page ); ?>

			<table class="widefat" id="cb-sw-student-posts-list">

				<thead>
					<tr>
						<th class="student"><?php _e( 'Student', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="excerpt"><?php _e( 'Excerpt', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Posted', 'classblogs' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="student"><?php _e( 'Student', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="excerpt"><?php _e( 'Excerpt', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Posted', 'classblogs' ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php foreach ( $paginator->get_items_for_page( $current_page ) as $post ): ?>
						<tr>
							<td class="student">
								<strong>
									<?php
										echo get_avatar( $post->post_author, 32 ) . ' ';
										printf( '<a href="%s">%s</a>',
											$students[$post->post_author]['blog_url'],
											$students[$post->post_author]['name'] );
									?>
								</strong>
							</td>
							<td class="post">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											get_blog_permalink( $post->cb_sw_blog_id, $post->ID ),
											$post->post_title );
									?>
								</strong>
							</td>
							<td class="excerpt"><?php echo ClassBlogs_Utils::make_post_excerpt( $post->post_content, 25 ); ?></td>
							<td class="posted">
								<?php
									echo mysql2date(
										get_option( 'date_format' ),
										$post->post_date );
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>

			</table>

		</div>
<?php
	}

	/**
	 * Returns an array of sitewide posts.
	 *
	 * These posts returned are limited only by the options of the sitewide
	 * aggregator, which can restrict which blogs populate the sitewide tables.
	 * The returned posts are in descending order by their published date.
	 *
	 * Since the posts will be from multiple different blogs, certain values
	 * are precomputed and added to each post.  The values are as follows:
	 *
	 *     cb_sw_excerpt   - the post's excerpt
	 *     cb_sw_from_blog - the ID of the blog on which the post was made
	 *
	 * @return array a list of sitewide posts
	 *
	 * @since 0.1
	 */
	public function get_sitewide_posts()
	{

		// Return the cached version if we've already built the sitewide post list
		$cached = $this->get_site_cache( 'all_posts' );
		if ( $cached !== null ) {
			return $cached;
		}

		global $wpdb;
		$posts = array();
		$use_root_excerpt = $this->get_option( 'root_use_excerpt' );
		$excerpt_words = $this->get_option( 'root_excerpt_words' );

		foreach ( $wpdb->get_results( "SELECT * FROM {$this->sw_tables->posts} ORDER BY post_date DESC" ) as $post ) {

			// Precompute each post's excerpt
			if ( $use_root_excerpt ) {
				$post->cb_sw_excerpt = ClassBlogs_Utils::make_post_excerpt( $post->post_content, $excerpt_words );
			}

			$posts[] = $post;
		}

		$this->set_site_cache( 'all_posts', $posts );
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
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sw_tables->posts} ORDER BY post_date DESC LIMIT 1" ) );
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
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sw_tables->posts} ORDER BY post_date LIMIT 1" ) );
	}

	/**
	 * Returns a subset of the sitewide posts, filtered by user and date.
	 *
	 * @param  int    $user_id  the ID of the desired post author
	 * @param  object $start_dt an optional DateTime after which to retrieve posts
	 * @param  object $end_dt   an optional DateTime before which to retrieve posts
	 * @return array            a list of the posts matching the given filters
	 *
	 * @since 0.1
	 */
	public function filter_posts( $user_id, $start_dt=null, $end_dt=null )
	{
		return $this->filter_sitewide_resources(
			$this->sw_tables->posts,
			'post_author', $user_id,
			'post_date', $start_dt, $end_dt );
	}

	/**
	 * Gets a list of recent posts formatted for display in a widget.
	 *
	 * The array of returned posts contains custom object instances with the
	 * following properties that can be used by the widget:
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
	 * @since 0.2
	 */
	public function get_posts_for_widget( $max_posts, $max_posts_per_blog, $meta_format )
	{

		// Use cache values if possible
		$cached = $this->get_site_cache( 'widget' );
		if ( $cached !== null ) {
			return $cached;
		}

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
					get_blogaddress_by_id( $post->cb_sw_blog_id ),
					get_blog_option( $post->cb_sw_blog_id, 'blogname' ) );
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
				'permalink' => get_blog_permalink( $post->cb_sw_blog_id, $post->ID ),
				'title'     => $post->post_title );
		}

		$this->set_site_cache( 'widget', $posts );
		return $posts;
	}

	/**
	 * Provides a list of posts made by each user of the blog.
	 *
	 * The returned list of posts is represented as an array containing objects.
	 * The array is in descending order by the published date of the most recent
	 * post written by each user.
	 *
	 * Each object in the returned list has the following properties:
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

		// Use cached values if possible
		$cached = $this->get_site_cache( 'by_user' );
		if ( $cached !== null ) {
			return $cached;
		}

		// Build the list of posts by user
		foreach ( $this->get_sitewide_posts() as $post ) {
			if ( ! array_key_exists( $post->post_author, $by_user ) ) {
				$by_user[$post->post_author] = (object) array(
					'posts'       => array(),
					'total_posts' => 0,
					'user_id'     => $post->post_author );
			}
			if ( $by_user[$post->post_author]->total_posts < $limit ) {
				$post->from_blog = $post->cb_sw_blog_id;
				$by_user[$post->post_author]->posts[] = $post;
			}
			$by_user[$post->post_author]->total_posts++;
		}

		// Sort the posts by the published date of the first post of each user
		usort( $by_user, array( $this, '_sort_posts_by_user_by_date' ) );
		$values = array_values( $by_user );
		$this->set_site_cache( 'by_user', $values );
		return $values;
	}
}

ClassBlogs::register_plugin( 'sitewide_posts', new ClassBlogs_Plugins_Aggregation_SitewidePosts() );

?>
