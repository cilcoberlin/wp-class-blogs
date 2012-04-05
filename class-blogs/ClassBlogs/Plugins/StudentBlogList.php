<?php

ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Settings.php' );
ClassBlogs::require_cb_file( 'Students.php' );
ClassBlogs::require_cb_file( 'Utils.php' );
ClassBlogs::require_cb_file( 'Widget.php' );

/**
 * A widget that shows a list of links to every student blog on the site.
 *
 * @package ClassBlogs_Plugins
 * @subpackage StudentBlogListWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_StudentBlogListWidget extends ClassBlogs_Widget
{

	/**
	 * Default options for the student-blogs widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'display' => '%firstname% %lastname%',
		'title'   => 'Students'
	);

	/**
	 * The name of the plugin.
	 */
	protected function get_name()
	{
		return __( 'Student List', 'classblogs' );
	}

	/**
	 * The description of the plugin.
	 */
	protected function get_description()
	{
		return __( 'A list of all students blogging for this class', 'classblogs' );
	}

	/**
	 * Displays the student-blogs widget.
	 *
	 * @uses ClassBlogs_Plugins_StudentBlogList to get all student blogs
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'student_blogs' );
		$student_blogs = $plugin->get_student_blogs( $instance['display'] );
		if ( empty( $student_blogs ) ) {
			return;
		}

		$this->start_widget( $args, $instance );
		echo '<ul class="cb-student-blogs">';
		foreach ( $student_blogs as $blog ) {
			echo '<li class="cb-student-blog"><a class="cb-student-blog-link" href="' . esc_url( $blog->url ) . '">' . $blog->name . '</a></li>';
		}
		echo '</ul>';
		$this->end_widget( $args );
	}

	/**
	 * Updates the student-blogs widget.
	 *
	 * @uses ClassBlogs_Plugins_StudentBlogList to clear the blog cache
	 */
	public function update( $new, $old )
	{
		// Update the widget options
		$instance = $old;
		$instance['display'] = ClassBlogs_Utils::sanitize_user_input( $new['display'] );
		$instance['title'] = ClassBlogs_Utils::sanitize_user_input( $new['title'] );

		// Clear the cached blog list and return the new instance
		$plugin = ClassBlogs::get_plugin( 'student_blogs' );
		$plugin->clear_blog_cache();
		return $instance;
	}

	/**
	 * Handles the admin logic for the student-blogs widget.
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
			<label for="<?php echo $this->get_field_id( 'display' ); ?>"><?php _e( 'Display Format', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'display' ); ?>" name="<?php echo $this->get_field_name( 'display' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'display' ); ?>" />
		</p>
		<h4><?php _e( 'Format variables you can use', 'classblogs' ) ?>:</h4>
		<p>
			<?php if ( ClassBlogs_Utils::is_multisite() ): ?>
				<strong>%blog%</strong><br />
				<?php _e( "The name of the student's blog", 'classblogs' ); ?><br /><br />
			<?php endif; ?>
			<strong>%firstname%</strong><br />
			<?php _e( "The student's first name", 'classblogs' ); ?><br /><br />
			<strong>%lastname%</strong><br />
			<?php _e( "The student's last name", 'classblogs' ); ?><br /><br />
			<strong>%nickname%</strong><br />
			<?php _e( "The student's nickname", 'classblogs' ); ?>
		</p>
<?php
	}
}

/**
 * A plugin that provides a widget, available only on the main blog, that displays
 * a list of student blogs.  A student blog in this case is any blog on which
 * a single user without admin privileges on the root blog has admin rights.
 *
 * This plugin also provides a programmatic interface to information about
 * students and their blogs, which can be seen in the following example:
 *
 *     // A blog with an ID of 2 is created for a student with an ID of 3 whose
 *     // nickname is 'Student'.  This blog is called 'Example' and is located
 *     // at http://www.example.com.
 *     $plugin = ClassBlogs::get_plugin( 'student_blogs' );
 *
 *     $blogs = $plugin->get_student_blogs( '%blog% (%nickname%)' );
 *     assert( count( $blogs ) === 1 );
 *     $blog = $blogs[0];
 *     assert( $blog->blog_id === 2 );
 *     assert( $blog->user_id === 3 );
 *     assert( $blog->title === 'Example (Student)' );
 *     assert( $blog->url === 'http://www.example.com' );
 *
 * @package ClassBlogs_Plugins
 * @subpackage StudentBlogList
 * @since 0.1
 */
class ClassBlogs_Plugins_StudentBlogList extends ClassBlogs_BasePlugin
{
	/**
	 * A lookup table containing student blog URLs, keyed by user ID.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_blog_urls;

	/**
	 * Registers WordPress hooks to enable the student blog list widget.
	 */
	function __construct() {
		parent::__construct();
		add_action( 'widgets_init',  array( $this, '_enable_widget' ) );
	}

	/**
	 * Clears the cache of student blogs.
	 *
	 * This is registered as a callback for any actions that could change the
	 * display name of a blog, such as the user updating their personal information
	 * or changing their blog's name.
	 *
	 * @since 0.3
	 */
	public function clear_blog_cache( $one = null, $two = null )
	{
		$this->clear_site_cache( 'student_blogs' );
	}

	/**
	 * Formats the blog display name based upon the formatting string.
	 *
	 * @param  string $format  the formatting string for the display name
	 * @param  int    $user_id the ID of the student who owns the blog
	 * @param  int    $blog_id the ID of the blog
	 * @return string          the formatted blog display name
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _format_blog_display_name( $format, $user_id, $blog_id )
	{
		$blog_title = ClassBlogs_WordPress::get_blog_option( $blog_id, 'blogname' );
		$first_name = get_user_meta( $user_id, 'first_name', true );
		$last_name = get_user_meta( $user_id, 'last_name', true );
		$blog_name = ClassBlogs_Utils::format_user_string(
			$format,
			array(
				'blog'      => $blog_title,
				'firstname' => $first_name,
				'lastname'  => $last_name,
				'nickname'  => get_user_meta( $user_id, 'nickname', true ) ),
			'cb-student-blog' );

		// If the blog name is the same as that of the main blog, use the
		// student's full name instead
		$main_blog_title = ClassBlogs_WordPress::get_blog_option( ClassBlogs_Settings::get_root_blog_id(), 'blogname' );
		if ( $blog_title === $main_blog_title ) {
			$blog_name = $first_name . " " . $last_name;
		}

		return $blog_name;
	}

	/**
	 * Enables the widget and its controller.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		ClassBlogs_Widget::register_root_only_widget( '_ClassBlogs_Plugins_StudentBlogListWidget' );
	}

	/**
	 * Sorts the internal blog list by the display name of each blog.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _sort_blogs_by_name( $x, $y )
	{
		return strcmp( $x->name, $y->name );
	}

	/**
	 * Returns a list of information about each student blog.
	 *
	 * The blogs in the returned list will be sorted by the last and then
	 * first name of the student owning it.  Each item in this list will be
	 * an object with the following properties:
	 *
	 *     blog_id - the ID of the blog
	 *     name    - the formatted display name of the blog
	 *     user_id - the ID of the student who owns the blog
	 *     url     - the URL of the blog
	 *
	 * @param  string $format the formatting string to use for the blog title
	 * @return array          a list of information on all student blogs
	 *
	 * @since 0.1
	 */
	public function get_student_blogs( $title_format = "" )
	{
		// Use cached data if possible
		$cached = $this->get_site_cache( 'student_blogs' );
		if ( $cached !== null ) {
			return $cached;
		}

		// Format the display of the
		$student_blogs = array();
		foreach ( ClassBlogs_Students::get_student_blogs() as $student_id => $blog ) {
			$student_blogs[$student_id] = (object) array(
				'blog_id' => $blog->blog_id,
				'name'    => $this->_format_blog_display_name( $title_format, $student_id, $blog->blog_id ),
				'user_id' => $student_id,
				'url'     => $blog->url );
		}

		// Return the blogs sorted by their computed display name
		usort( $student_blogs, array( $this, "_sort_blogs_by_name" ) );
		$this->set_site_cache( 'student_blogs', $student_blogs, 300 );
		return $student_blogs;
	}
}

ClassBlogs::register_plugin(
	'student_blogs',
	'ClassBlogs_Plugins_StudentBlogList',
	__( 'Student List', 'classblogs' ),
	__( 'Provides a widget that shows a list of all the student bloggers in your class.', 'classblogs' )
);

?>
