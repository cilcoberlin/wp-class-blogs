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
			</style>
			<link rel="stylesheet" href="%3$sadmin.css" />',
			ClassBlogs_Utils::get_base_images_url(),
			( $this->_get_admin_color_scheme() === 'fresh' ) ? 'icon32.png' : 'icon32-vs.png',
			ClassBlogs_Utils::get_base_css_url() );
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
		if ( ! empty( $current_user ) ) {
			return get_user_option( 'admin_color', $current_user->ID );
		} else {
			return 'fresh';
		}
	}

	/**
	 * Handles the display of the class blogs base admin page.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _class_blogs_admin_page()
	{

		// Disable any plugins that are not checked
		if ( $_POST ) {

			// Get a list of all plugins that are checked to be enabled
			$enabled = array();
			foreach ( $_POST as $field => $value ) {
				$plugin = str_replace( 'plugin_', '', $field );
				if ( $plugin !== $field ) {
					$enabled[$plugin] = true;
				}
			}

			// Enable any plugins that were previously disabled, and disable any
			// that were previously enabled
			foreach ( ClassBlogs::get_user_controlled_plugins() as $plugin ) {
				if ( array_key_exists( $plugin->id, $enabled ) ) {
					if ( ! $plugin->enabled ) {
						ClassBlogs::enable_plugin( $plugin->id );
					}
				} else {
					if ( $plugin->enabled ) {
						ClassBlogs::disable_plugin( $plugin->id );
					}
				}
			}

			self::show_admin_message( __( 'Enabled class-blogs plugins have been updated.  You must refresh the page to see the effects  of this.' ), 'classblogs' );
		}

?>
		<div class="wrap">
			<?php ClassBlogs_Admin::show_admin_icon();  ?>
			<h2><?php _e( 'Class Blogs', 'classblogs' ); ?></h2>

			<p>
				<?php _e(
					'The class blogs plugin	suite will help you manage a blog for a class where you have control over the main blog and each student has full ownership of a child blog.', 'classblogs' );
				?>
			</p>
			<p>
				<?php _e( '
					The plugins that are part of this suite are provided in the list below.
					Not every plugin has configurable options, but the ones that do will appear as links in the Class Blogs admin menu.
					If you do not wish to use a certain component of the class-blogs suite, you can uncheck it in the list below and click on the "Update Enabled Plugins" button.', 'classblogs' )
				?>
			</p>

			<h3><?php _e( 'Enabled Plugins', 'classblogs' ); ?></h3>

			<form method="post" action="">

				<table id="cb-enabled-plugins">

					<thead>
						<tr>
							<th class="toggle"><?php _e( 'Enabled', 'classblogs' ); ?></th>
							<th class="name"><?php _e( 'Name', 'classblogs' ); ?></th>
							<th class="description"><?php _e( 'Description', 'classblogs' ); ?></th>
						</tr>
					</thead>

					<tbody>
						<?php

							// Display each user-controlled plugin
							$plugins = ClassBlogs::get_user_controlled_plugins();
							foreach ( $plugins as $plugin ) {
								$field = 'plugin_' . $plugin->id;
								$name = ( $plugin->name ) ? $plugin->name : get_class( $plugin->plugin );
								printf('
									<tr>
										<td class="toggle">
											<input type="checkbox" id="%1$s" name="%1$s" %2$s />
										</td>
										<td class="name">
											<label for="%1$s">%3$s</label>
										</td>
										<td class="description">%4$s</td>
									</tr>',
									esc_attr( $field ),
									( $plugin->enabled ) ? 'checked="checked"' : '',
									esc_html( $name ),
									esc_html( $plugin->description ) );
							}
						?>
					</tbody>

				</table>

				<?php wp_nonce_field( 'classblogs_admin' ); ?>
				<p class="submit">
					<input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Enabled Plugins', 'classblogs' ); ?>" />
				</p>
			</form>
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
