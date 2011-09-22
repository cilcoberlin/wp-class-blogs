<?php

/**
 * A widget that displays a random image with a caption
 *
 * @access private
 * @package Class Blogs
 * @since 0.1
 */
class _ClassBlogs_Plugins_RandomImageWidget extends ClassBlogs_Plugins_SidebarWidget
{

	/**
	 * Default options for the random image widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'title' => 'Random Image'
	);

	/**
	 * Creates the random image widget
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Random Image', 'classblogs' ),
			__( 'A random image from one of the blogs on the site', 'classblogs' ),
			'cb-random-image' );
	}

	/**
	 * Displays the random image widget
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'random_image' );
		$image = $plugin->get_random_image();

		if ( $image ) {
			$this->start_widget( $args, $instance );
			switch_to_blog( $image->blog_id );

			// If the image is associated with a specific post, provide a link
			// to the post.  If it has no post linkages, show a link to the blog.
			if ( $image->post_id ) {
				$caption = sprintf( __( 'From the post %1$s on %2$s', 'classblogs' ),
					sprintf( '<a href="%s">%s</a>',
						esc_url( get_permalink( $image->post_id ) ),
						esc_html( get_post( $image->post_id )->post_title ) ),
					sprintf( '<a href="%s">%s</a>',
						esc_url( get_blogaddress_by_id( $image->blog_id ) ),
						esc_html( get_blog_option( $image->blog_id, 'blogname' ) ) ) );
			} else {
				$caption = sprintf( __( 'From the blog %s', 'classblogs' ),
					sprintf( '<a href="%s">%s</a>',
						esc_url( get_blogaddress_by_id( $image->blog_id ) ),
						esc_html( get_blog_option( $image->blog_id, 'blogname' ) ) ) );
			}

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
			restore_current_blog();
			$this->end_widget( $args );
		}
	}

	/**
	 * Updates the random image widget
	 */
	public function update( $new, $old )
	{
		$old['title'] = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $old;
	}

	/**
	 * Handles the admin logic for the random image widget
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
 * The random-image plugin
 *
 * This plugin allows an admin on the root blog to add a widget that displays
 * a random image chosen from all the blogs on the site and shows the blog on
 * which the image was used.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_RandomImage extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The media ID used to identify attachments
	 *
	 * @access private
	 * @var string
	 */
	const _MEDIA_ID = "attachment";

	/**
	 * A list of MIME types that count as valid images
	 *
	 * @access private
	 * @var array
	 */
	private static $_image_mimes = array(
		"image/gif",
		"image/jpeg",
		"image/png"
	);

	/**
	 * Registers the random image sidebar widget
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'widgets_init', array( $this, '_enable_widget' ) );
	}

	/**
	 * Enables the random image sidebar widget
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_RandomImageWidget' );
	}

	/**
	 * Returns a random image from one of the blogs on the site
	 *
	 * The returned image object, if not null, will have the following properties:
	 *
	 *     blog_id - the ID of the blog on which the image was posted
	 *     title   - the image's title
	 *     url     - the absolute URL to the image
	 *
	 * @return object the random image, or null if none can be found
	 *
	 * @since 0.1
	 */
	public function get_random_image()
	{

		global $wpdb;
		$image = null;
		$urls = array();

		// Build the search for any valid images
		$mime_searches = array();
		foreach ( self::$_image_mimes as $mime ) {
			$mime_searches[] = "post_mime_type = '$mime'";
		}
		$mime_filter = implode( ' OR ', $mime_searches );

		// Search through every blog for a usable image.  If an image is found, build
		// the link to it and add a possible caption.
		$blogs = $this->get_all_blog_ids();
		shuffle( $blogs );
		foreach ( $blogs as $blog_id ) {
			switch_to_blog( $blog_id );
			$image_search = $wpdb->prepare( "
				SELECT ID, post_title, GUID FROM $wpdb->posts
				WHERE post_type = %s AND ( $mime_filter )
				AND post_content <> guid
				ORDER BY RAND() LIMIT 1",
				self::_MEDIA_ID );
			$upload = $wpdb->get_row( $image_search );
			if ( $upload ) {
				$urls[] = $upload->GUID;
				$info = wp_get_attachment_image_src( $upload->ID );
				if ( ! empty( $info ) ) {
					$image = array(
						'blog_id' => $blog_id,
						'title'   => $upload->post_title,
						'url'     => $info[0] );
					$urls[] = $info[0];
				}
				restore_current_blog();
				break;
			}
			restore_current_blog();
		}

		// If we have a valid image, try to find the first post on which it was
		// used and add its ID to the image data
		if ( $image ) {
			$post_id = null;
			foreach ( $urls as $url ) {
				$post_id = $this->_find_first_post_to_use_image(
					$image['blog_id'], $url );
				if ( $post_id ) {
					break;
				}
			}
			$image['post_id'] = $post_id;
			$image = (object) $image;
		}

		return $image;
	}

	/**
	 * Find the ID of the first post that uses the given image
	 *
	 * @param  int    $blog_id the ID of the blog on which the image was uploaded
	 * @param  string $url     the absolute URL of the image
	 * @return int             the ID of the first post that uses the image
	 */
	private function _find_first_post_to_use_image( $blog_id, $url )
	{

		global $wpdb;
		$post_id = null;

		// Search for the first post that references the image
		switch_to_blog( $blog_id );
		$post_search = $wpdb->prepare( "
			SELECT ID FROM $wpdb->posts
			WHERE post_status='publish'
			AND post_content LIKE '%%" . like_escape( $url ) . "%%'
			ORDER BY post_date
			LIMIT 1" );
		$post = $wpdb->get_row( $post_search );
		if ( ! empty( $post ) ) {
			$post_id = $post->ID;
		}
		restore_current_blog( $blog_id );

		return $post_id;
	}
}

ClassBlogs::register_plugin( 'random_image', new ClassBlogs_Plugins_RandomImage() );

?>
