<?php

ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Utils.php' );
ClassBlogs::require_cb_file( 'Widget.php' );
ClassBlogs::require_cb_file( 'WordPress.php' );

/**
 * A widget that displays an chosen randomly from all of the posts on the site.
 *
 * This image is displayed with a caption beneath that provides information on
 * its provenance.  If the image is associated with a specific post, a link to
 * that post is provided.  If the image is simply in the media library but not
 * linked to an actual post, a link to the blog in whose media library the image
 * exists is provided.
 *
 * @package ClassBlogs_Plugins
 * @subpackage RandomImageWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_RandomImageWidget extends ClassBlogs_Widget
{

	/**
	 * Default options for the random-image widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'title' => 'Random Image'
	);

	/**
	 * The name of the plugin.
	 */
	protected function get_name()
	{
		return __( 'Random Image', 'classblogs' );
	}

	/**
	 * The description of the plugin.
	 */
	protected function get_description()
	{
		return __( 'A random image from one of the blogs on the site', 'classblogs' );
	}

	/**
	 * Displays the random-image widget.
	 *
	 * @uses ClassBlogs_Plugins_RandomImage to get a random image to display
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'random_image' );
		$image = $plugin->get_random_image();

		if ( $image ) {
			$this->start_widget( $args, $instance );
			ClassBlogs_WordPress::switch_to_blog( $image->blog_id );

			// If the image is associated with a specific post, provide a link
			// to the post.  If it has no post linkages, show a link to the blog.
			if ( $image->post_id ) {
				$post_link = sprintf( '<a href="%s">%s</a>',
							esc_url( get_permalink( $image->post_id ) ),
							esc_html( get_post( $image->post_id )->post_title ) );

				// Show a link to a post on a blog when running in multisite
				// mode, or a link to the post by a particular user when not
				if ( ClassBlogs_Utils::is_multisite() ) {
					$caption = sprintf( __( 'From the post %1$s on %2$s', 'classblogs' ),
						$post_link,
						sprintf( '<a href="%s">%s</a>',
							esc_url( ClassBlogs_WordPress::get_blogaddress_by_id( $image->blog_id ) ),
							esc_html( ClassBlogs_WordPress::get_blog_option( $image->blog_id, 'blogname' ) ) ) );
				} else {
					$user = get_userdata( $image->user_id );
					$caption = sprintf( __( 'From the post %1$s by %2$s', 'classblogs' ),
						 $post_link,
						sprintf( '<a href="%s">%s</a>',
							esc_url( get_author_posts_url( $image->user_id ) ),
							esc_html( $user->display_name ) ) );
				}
			} else {

				// Show a link to the source blog when running in multisite mode,
				// or show the image's title when not
				if ( ClassBlogs_Utils::is_multisite() ) {
					$caption = sprintf( __( 'From the blog %s', 'classblogs' ),
						sprintf( '<a href="%s">%s</a>',
							esc_url( ClassBlogs_WordPress::get_blogaddress_by_id( $image->blog_id ) ),
							esc_html( ClassBlogs_WordPress::get_blog_option( $image->blog_id, 'blogname' ) ) ) );
				} else {
					$caption = sprintf( '<a href="%s">%s</a>',
						esc_url( $image->url ),
						esc_attr( $image->title ) );
				}
			}

			// Display the link to the image with an appropriate caption
			printf(
				'<ul>
					<li>
						<a href="%1$s"><img src="%1$s" alt="%2$s" title="%2$s" width="80%%" /></a>
						<br />
						%3$s
					</li>
				</ul>',
				esc_url( $image->url ),
				esc_attr( $image->title ),
				$caption );
			ClassBlogs_WordPress::restore_current_blog();
			$this->end_widget( $args );
		}
	}

	/**
	 * Updates the random-image widget.
	 */
	public function update( $new, $old )
	{
		$old['title'] = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $old;
	}

	/**
	 * Handles the admin logic for the random-image widget.
	 */
	public function form( $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
<?php
	}
}

/**
 * A plugin that provides a widget, available only on the root blog, that
 * displays an image randomly selected from the media libraries and posts of
 * all blogs on the site.
 *
 * This also provides a simple interface to getting random images, an example
 * of which is shown below:
 *
 *     // An image named 'example' is used in a post with an ID of 2 on a blog
 *     // with an ID of 3.
 *     $plugin = ClassBlogs::get_plugin( 'random_image' );
 *
 *     $image = $plugin->get_random_image();
 *     assert( $image->title === 'example );
 *     assert( $image->blog_id === 3 );
 *     assert( $image->post_id === 2 );
 *     echo "The image's URL is " . $image->url . "\n";
 *
 * @package ClassBlogs_Plugins
 * @subpackage RandomImage
 * @since 0.1
 */
class ClassBlogs_Plugins_RandomImage extends ClassBlogs_BasePlugin
{
	/**
	 * Registers the random-image widget.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'widgets_init', array( $this, '_enable_widget' ) );
	}

	/**
	 * Enables the random-image widget.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		ClassBlogs_Widget::register_root_only_widget( '_ClassBlogs_Plugins_RandomImageWidget' );
	}

	/**
	 * Finds the post object for the first post that uses the given image.
	 *
	 * @param  int    $blog_id the ID of the blog on which the image was uploaded
	 * @param  string $url     the absolute URL of the image
	 * @return object          a WordPress post instance for the image
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _find_first_post_to_use_image( $blog_id, $url )
	{
		global $wpdb;
		$post = null;

		// Search for the first post that references the image
		ClassBlogs_WordPress::switch_to_blog( $blog_id );
		$post_search = $wpdb->prepare( "
			SELECT * FROM $wpdb->posts
			WHERE post_status='publish'
			AND post_content LIKE '%%" . like_escape( $url ) . "%%'
			ORDER BY post_date
			LIMIT 1" );
		$post = $wpdb->get_row( $post_search );
		ClassBlogs_WordPress::restore_current_blog();

		return $post;
	}

	/**
	 * Returns a random image from one of the blogs on the site.
	 *
	 * The returned image object, if not null, will have the following properties:
	 *
	 *     blog_id - the ID of the blog on which the image was posted
	 *     title   - the image's title
	 *     url     - the absolute URL to the image
	 *
	 * If the image is associated with a particular post, it will also have
	 * the following properties on it:
	 *
	 *     post_id - the ID of the post that uses the image
	 *     user_id - the ID of the user who created a post using the image
	 *
	 * @return mixed the random image object, or null if none can be found
	 *
	 * @since 0.1
	 */
	public function get_random_image()
	{

		global $blog_id, $wpdb;
		$current_blog_id = $blog_id;
		$image = null;
		$urls = array();

		// Search through every blog for a usable image.  If an image is found, build
		// the link to it and add a possible caption.
		$blogs = ClassBlogs_Utils::get_all_blog_ids();
		shuffle( $blogs );
		foreach ( $blogs as $blog ) {
			ClassBlogs_WordPress::switch_to_blog( $blog );
			$images = $wpdb->get_results( "
				SELECT ID, post_title, GUID FROM $wpdb->posts
				WHERE post_mime_type LIKE 'image/%%'
				AND post_content <> guid" );
			if ( $images ) {
				$image = $images[array_rand( $images )];
				$urls[] = $image->GUID;
				$info = wp_get_attachment_image_src( $image->ID );
				if ( ! empty( $info ) ) {
					$image = array(
						'blog_id' => $blog,
						'title'   => $image->post_title,
						'url'     => $info[0] );
					$urls[] = $info[0];
				}
				break;
			}
		}
		ClassBlogs_Utils::restore_blog( $current_blog_id );

		// If we have a valid image, try to find the first post on which it was
		// used and add its ID to the image data
		if ( $image ) {
			$info = array();
			$post_id = null;
			$user_id = null;
			foreach ( $urls as $url ) {
				$post = $this->_find_first_post_to_use_image(
					$image['blog_id'], $url );
				if ( ! empty( $post ) ) {
					break;
				}
			}
			if ( ! empty( $post ) ) {
				$post_id = $post->ID;
				$user_id = $post->post_author;
			}
			$image['post_id'] = $post_id;
			$image['user_id'] = $user_id;
			$image = (object) $image;
		}

		return $image;
	}
}

ClassBlogs::register_plugin(
	'random_image',
	'ClassBlogs_Plugins_RandomImage',
	__( 'Random Image', 'classblogs' ),
	__( 'Provides a widget that shows a random image.', 'classblogs' )
);

?>
