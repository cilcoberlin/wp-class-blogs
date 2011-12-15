<?php

/**
 * A class to handle shared administrative class blogs functions.
 *
 * This mainly provides an interface that allows class-blogs plugins to add one
 * or more menu items to the class-blogs admin group.  This also provides
 * a few helpers to display common notifications on any admin pages.
 *
 * To access the admin interface, you will need to get a reference to an
 * instance of it by calling the `ClassBlogs_Admin::get_admin()` static method.
 * Trying to create an instance using the constructor will not work.
 *
 * An example of a plugin using this to register and manage a page is as follows:
 *
 *     $admin = ClassBlogs_Admin::get_admin();
 *     $admin->add_admin_page(
 *         'page-id',
 *         'My Admin Page',
 *         'admin_page_view'
 *     );
 *     echo 'Visit the admin page at ' . $admin->get_admin_page_url( 'page_id' );
 *
 *     $admin->show_admin_message( 'You did something right on the admin page' );
 *     $admin->show_admin_error( 'You did something wrong on the admin page' );
 *
 * @package ClassBlogs
 * @subpackage Admin
 * @since 0.1
 */
class ClassBlogs_Admin
{

	/**
	 * The ID used for the class blogs admin menu.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _MENU_ID = "class-blogs";

	/**
	 * The capability required for a user to see the admin menu.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _MENU_CAPABILITY = "manage_options";

	/**
	 * An instance of the class, used to keep it a singleton.
	 *
	 * @access private
	 * @var object
	 * @since 0.1
	 */
	private static $_instance;

	/**
	 * A mapping of plugin UIDs to their admin page IDs.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_page_ids;

	/**
	 * Registers admin hooks.
	 *
	 * @access private
	 */
	private function __construct()
	{
		add_action( 'admin_menu', array( $this, '_configure_admin_interface' ) );
		add_action( 'admin_head', array( $this, '_add_admin_styles' ) );
	}

	/**
	 * Creates the base class blogs admin menu that is available to any admin
	 * user with administrative rights on the root blog who is on the admin side.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _configure_admin_interface()
	{
		if ( ClassBlogs_Utils::on_root_blog_admin() ) {
			$icon = ( $this->_get_admin_color_scheme() === 'fresh' ) ? 'icon16.png' : 'icon16-vs.png';
			$page = add_menu_page(
				__( 'Class Blogs', 'classblogs' ),
				__( 'Class Blogs', 'classblogs' ),
				self::_MENU_CAPABILITY,
				self::_MENU_ID,
				array( $this, '_class_blogs_admin_page' ),
				ClassBlogs_Utils::get_base_images_url() . $icon );
		}
	}

	/**
	 * Outputs markup for making minor changes to the admin page's styles
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _add_admin_styles()
	{
		printf( '
			<style type="text/css">
				#icon-class-blogs {
					background-image: url("%1$s/%2$s");
					height: 32px;
					width: 32px;
				}
			</style>',
			ClassBlogs_Utils::get_base_images_url(),
			( $this->_get_admin_color_scheme() === 'fresh' ) ? 'icon32.png' : 'icon32-vs.png' );
	}

	/**
	 * Returns WordPress's identifier for the current user's admin color scheme.
	 *
	 * As of WordPress version 3.0, this will either return "classic", for the
	 * blue color scheme and "fresh" for the gray one.
	 *
	 * @return string the admin color-scheme identifier
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _get_admin_color_scheme()
	{
		$current_user = get_currentuserinfo();
		return get_user_option( 'admin_color', $current_user->ID );
	}

	/**
	 * Handles the display of the class blogs base admin page.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _class_blogs_admin_page()
	{
?>
		<div class="wrap">
			<?php ClassBlogs_Admin::show_admin_icon();  ?>
			<h2><?php _e( 'Class Blogs', 'classblogs' ); ?></h2>

			<p>
				<?php _e(
					'The class blogs plugin suite will help you manage a blog for a class where you have control over the main blog and each student has full ownership of a child blog.', 'classblogs' );
				?>
			</p>
			<p>
				<?php _e(
					'The plugins that are part of this suite are provided in the list below.  Not every plugin has configurable options, but the ones that do should appear as links in the admin menu in the lower left.', 'classblogs' )
				?>
			</p>

			<h3><?php _e( 'Plugins', 'classblogs' ); ?></h3>

			<h4><?php _e( 'Classmate Comments', 'classblogs' ); ?></h4>
			<p><?php _e( "Automatically approves any comment left by a logged-in student on another student's blog.", 'classblogs' ); ?></p>

			<h4><?php _e( 'Disable Comments', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides an admin option to disable commenting on all blogs used by this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Gravatar Signup', 'classblogs' ); ?></h4>
			<p><?php _e( 'Adds a link for the user to sign up for a gravatar to each account activation email sent out.', 'classblogs' ); ?></p>

			<h4><?php _e( 'New User Configuration', 'classblogs' ); ?></h4>
			<p><?php _e( 'Creates a first and last name for a newly added user based on their email address.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Random Image', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that displays a randomly selected image chosen from all the images used on all blogs that are part of this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Comments', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows recent comments left on all student blogs, as well as a professor-only admin page showing a table of all student comments and a student-only admin page showing a table of all comments that they have left.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Posts', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows recent posts made on all student blogs and allows for displaying all recent sitewide posts on the main blog.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Sitewide Tags', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget sitewide tag cloud widget, and allows all usages of a single tag on all student blogs to be viewed.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Student Blog Links', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides an admin option that allows you to add links of your choosing as the first widget on all student blogs.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Student Blog List', 'classblogs' ); ?></h4>
			<p><?php _e( 'Provides a main-blog-only widget that shows a list of all student blogs that are part of this class.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Student Pseudonym', 'classblogs' ); ?></h4>
			<p><?php _e( 'Adds a page to the Users group on the admin side of any student blog that allows them to quickly change their username, blog URL and display name.', 'classblogs' ); ?></p>

			<h4><?php _e( 'Word Counter', 'classblogs' ); ?></h4>
			<p><?php _e( 'Adds a page for the professor on the admin side to view student word counts by week, and adds a dashboard widget to each student blog that shows the word counts for the current and previous weeks.  Word counts are drawn from any posts or comments that students have made.', 'classblogs' ); ?></p>

			<h4><?php _e( 'YouTube Class Playlist', 'classblogs' ); ?></h4>
			<p><?php _e( 'Allows you to link a YouTube playlist with this blog that is automatically updated whenever students embed YouTube videos in a post.', 'classblogs' ); ?></p>

		</div>
<?php
	}

	/**
	 * Return an instance of the admin class, instantiating one if it doesn't exist.
	 *
	 * @return object an instance of a ClassBlogs_Admin class
	 *
	 * @since 0.1
	 */
	public static function get_admin()
	{
		if ( ! isset(self::$_instance ) ) {
			self::$_instance = new ClassBlogs_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Adds a new page to the class blogs admin group.
	 *
	 * @param  string $uid   the calling plugin's unique identifier
	 * @param  string $title the title of the admin page
	 * @param  object $view  a reference to the view that handles the page
	 * @return string        the page ID of the page created
	 *
	 * @since 0.1
	 */
	public function add_admin_page( $uid, $title, $view_function )
	{
		$page_id = strtolower( sanitize_title_with_dashes( $title ) );
		$this->_page_ids[$uid] = $page_id;
		add_submenu_page(
			self::_MENU_ID,
			$title,
			$title,
			self::_MENU_CAPABILITY,
			$page_id,
			$view_function );
		return $page_id;
	}

	/**
	 * Returns the URL for the admin page registered with the given UID.
	 *
	 * @param  string $uid the calling plugin's unique identifier
	 * @return string      the admin page's URL
	 *
	 * @since 0.2
	 */
	public function get_admin_page_url( $uid )
	{
		return sprintf( '%sadmin.php?page=%s',
			get_admin_url(),
			$this->_page_ids[ $uid ]
		);
	}

	/**
	 * Prints markup for an admin notification message.
	 *
	 * @param string $message the text of the message to display
	 *
	 * @since 0.2
	 */
	public static function show_admin_message( $message )
	{
		echo sprintf( '<div id="message" class="updated fade"><p>%s</p></div>', $message );
	}

	/**
	 * Prints markup for an admin error message.
	 *
	 * @param string $message the text of the error to display
	 *
	 * @since 0.2
	 */
	public static function show_admin_error( $error )
	{
		echo sprintf( '<div id="message" class="error fade"><p>%s</p></div>', $error );
	}

	/**
	 * Prints markup to show the class-blogs admin icon.
	 *
	 * @since 0.2
	 */
	public static function show_admin_icon()
	{
		echo '<div id="icon-class-blogs" class="icon32"></div>';
	}
}

$admin = ClassBlogs_Admin::get_admin();

?>
