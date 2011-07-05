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
	protected static $default_options = array(
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

		$plugin = ClassBlogs::get_plugin( 'random_image' );
		$image = $plugin->get_random_image();

		if ( $image ) {
			$this->start_widget( $args, $instance );
			switch_to_blog( $image->blog_id );
			printf(
				'<ul>
					<li>
						<a href="%1$s"><img src="%1$s" alt="%2$s" title="%2$s" width="80%%" /></a>
						<br />
						' . __( 'From the blog', 'classblogs' ) . ' <a href="%3$s">%4$s</a>
					</li>
				</ul>',
				$image->url,
				esc_attr( $image->title ),
				get_blogaddress_by_id( $image->blog_id ),
				get_blog_option( $image->blog_id, 'blogname' ) );
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
				SELECT post_title, guid FROM $wpdb->posts
				WHERE post_type = %s AND ( $mime_filter )
				ORDER BY RAND() LIMIT 1",
				self::_MEDIA_ID );
			$upload = $wpdb->get_row( $image_search );
			if ( $upload ) {
				$image = (object) array(
					'blog_id' => $blog_id,
					'title'   => $upload->post_title,
					'url'     => $upload->guid );
				break;
			}
			restore_current_blog();
		}

		return $image;
	}
}

ClassBlogs::register_plugin( 'random_image', new ClassBlogs_Plugins_RandomImage() );

?>
