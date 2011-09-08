<?php

/**
 * A widget that shows a list of all student blogs
 *
 * @access private
 * @package Class Blogs
 * @since 0.1
 */
class _ClassBlogs_Plugins_StudentBlogListWidget extends ClassBlogs_Plugins_SidebarWidget
{

	/**
	 * Default options for the student blogs widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'display' => '%blog%',
		'title'   => 'Student Blogs'
	);

	/**
	 * Creates the student blogs widget
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Student Blog List', 'classblogs' ),
			__( 'A list of all student blogs on the site', 'classblogs' ),
			'cb-student-blogs' );
	}

	/**
	 * Displays the student blogs widget
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
			echo '<li class="cb-student-blog"><a class="cb-student-blog-link" href="' . $blog->url . '">' . $blog->name . '</a></li>';
		}
		echo '</ul>';
		$this->end_widget( $args );
	}

	/**
	 * Updates the student blogs widget
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['display'] = ClassBlogs_Utils::sanitize_user_input( $new['display'] );
		$instance['title'] = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the student blogs widget
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
		<p>
			<strong><?php _e( 'Format variables you can use', 'classblogs' ) ?></strong>
			<dl>
				<dt>%blog%</dt>
				<dd><?php _e( "The name of the student's blog", 'classblogs' ); ?></dd>
				<dt>%firstname%</dt>
				<dd><?php _e( "The student's first name", 'classblogs' ); ?></dd>
				<dt>%lastname%</dt>
				<dd><?php _e( "The student's last name", 'classblogs' ); ?></dd>
				<dt>%nickname%</dt>
				<dd><?php _e( "The student's nickname", 'classblogs' ); ?></dd>
			</dl>
		</p>
<?php
	}
}

/**
 * The student-blog list plugin
 *
 * This provides a widget available only on the main blog that displays
 * a list of student blogs.  A student blog in this case is any blog on which
 * a single user without admin privileges on the root blog has admin rights.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_StudentBlogList extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * Registers WordPress hooks to enable the student blog list widget
	 */
	function __construct() {
		parent::__construct();
		add_action( 'widgets_init',  array( $this, '_enable_widget' ) );
	}

	/**
	 * Sorts the internal blog list by the display name of each blog
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _sort_blogs_by_name( $x, $y )
	{
		return strcmp( $x->name, $y->name );
	}

	/**
	 * Returns a list of information about each student blog
	 *
	 * Each element in the returned array will have a 'name' key containing the
	 * display name of the blog and a 'url' key containing its URL.  The blogs
	 * will be sorted by the last and then first name of the student owning it.
	 *
	 * @param  string $format the formatting string to use for the blog title
	 * @return array          a list of information on all student blogs
	 *
	 * @since 0.1
	 */
	public function get_student_blogs( $title_format = "" )
	{
		// Determine the URL and display name for each student blog
		$student_blogs = array();
		foreach ( $this->_get_blog_list() as $blog ) {
			$student_blogs[$blog['user_id']] = (object) array(
				'blog_id' => $blog['blog_id'],
				'name'    => $this->format_blog_display_name( $title_format, $blog['user_id'], $blog['blog_id'] ),
				'user_id' => $blog['user_id'],
				'url'     => get_blogaddress_by_id( $blog['blog_id'] ) );
		}

		// Return the blogs sorted by their computed display name
		usort( $student_blogs, array( $this, "_sort_blogs_by_name" ) );
		return $student_blogs;
	}

	/**
	 * Formats the blog display name based upon the formatting string
	 *
	 * @param  string $format  the formatting string for the display name
	 * @param  int    $user_id the ID of the student who owns the blog
	 * @param  int    $blog_id the ID of the blog
	 * @return string          the formatted blog display name
	 *
	 * @since 0.1
	 */
	public function format_blog_display_name( $format, $user_id, $blog_id )
	{
		return ClassBlogs_Utils::format_user_string(
			$format,
			array(
				'blog'      => get_blog_option( $blog_id, 'blogname' ),
				'firstname' => get_user_meta( $user_id, 'first_name', true ),
				'lastname'  => get_user_meta( $user_id, 'last_name', true ),
				'nickname'  => get_user_meta( $user_id, 'nickname', true ) ),
			'cb-student-blog' );
	}

	/**
	 * Returns the address of the student's blog
	 *
	 * @param  int    $user_id the user ID of the student
	 * @return string          the address of their blog, or a blank string
	 *
	 * @since 0.1
	 */
	public function get_blog_url_for_student( $user_id )
	{
		foreach ( $this->get_student_blogs() as $blog ) {
			if ( $blog->user_id === $user_id ) {
				return $blog->url;
			}
		}
		return "";
	}

	/**
	 * Gets a list of student blogs on the current site
	 *
	 * A student blog in this sense is defined as any non-root blog that has a
	 * single admin user on it that does not have admin rights on any other
	 * blogs on the site.  This user is assumed to be a student.
	 *
	 * Each entry in the array will have a 'user_id' key containing the user ID
	 * of the student owning the blog and a 'blog_id' key containing the ID of
	 * the blog that they own.
	 *
	 * @return array a list of info about the student blogs on the site
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_blog_list()
	{

		global $wpdb;
		$student_blogs = array();

		// Build a list of the user IDs of all users who are site admins or
		// admins on the root blog
		$admins = array();
		foreach ( get_super_admins() as $admin ) {
			$admins[] = username_exists( $admin );
		}
		foreach ( get_users( 'blog_id=' . ClassBlogs_Settings::get_root_blog_id() . '&role=administrator' ) as $user ) {
			$admins[] = $user->ID;
		}
		$admins = array_unique( $admins );

		// Cycle through every blog on the current site, adding any blogs that
		// have only one admin user that is not a site admin or an admin on the
		// root blog to the list of student blogs
		foreach ( $this->get_all_blog_ids() as $blog_id ) {
			$blog_admins = get_users( 'blog_id=' . $blog_id . '&role=administrator' );
			if ( count( $blog_admins ) == 1 ) {
				$blog_admin = $blog_admins[0];
				if ( array_search( $blog_admin->ID, $admins ) === false ) {
					$student_blogs[] = array(
						'user_id' => $blog_admin->ID,
						'blog_id' => $blog_id );
				}
			}
		}

		return $student_blogs;
	}

	/**
	 * Enables the sidebar widget and its controller
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_StudentBlogListWidget' );
	}
}

ClassBlogs::register_plugin( 'student_blogs', new ClassBlogs_Plugins_StudentBlogList() );

?>
