<?php

/**
 * The student-blog links plugin
 *
 * This plugin provides a network-admin menu that allows a professor to
 * add unlimited links of their choosing to student blogs.  These links will
 * appear on every student blog as the first widget in the first widgetized
 * area of the theme in use.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_StudentBlogLinks extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The default options for the plugin
	 *
	 * @access protected
	 * @var array
	 */
	protected static $default_options = array(
		'links' => array(),
		'title' => 'Class Links'
	);

	/**
	 * Registers plugin hooks and sets default options
	 */
	function __construct()
	{

		parent::__construct();

		// Add an admin menu for the plugin to the network admin pages
		if ( is_admin() ) {
			add_action( 'network_admin_menu', array( $this, 'configure_admin_interface' ) );
			add_action( 'admin_footer', array( $this, 'add_admin_scripts' ) );
		}

		// If there are any links defined and we're not in the admin side,
		// inject the professor's links into a widget in the sidebar of all
		// student blogs in the network
		$links = $this->get_option( 'links' );
		if ( ! is_admin() && ! empty( $links ) ) {
			add_action( 'init', array( $this, "register_sidebar_widget" ) );
			add_filter( 'sidebars_widgets', array( $this, 'add_sidebar_widget' ) );
		}
	}

	/**
	 * Register the link list sidebar widget if not on the root blog
	 *
	 * @since 0.1
	 */
	public function register_sidebar_widget()
	{
		if ( ! ClassBlogs_Utils::is_root_blog() ) {
			$uid = $this->get_uid();
			wp_register_sidebar_widget(
				$uid,
				$this->get_option( 'title' ),
				array( $this, 'render_link_list' ),
				array( 'classname' => $uid ) );
		}
	}

	/**
	 * Returns the default widget list used when a sidebar is empty
	 *
	 * @return array a list of the default widgets
	 *
	 * @since 0.1
	 */
	public function get_default_widget_list()
	{
		return array( $this->get_uid(), ClassBlogs_Settings::META_WIDGET_ID );
	}

	/**
	 * Adds the link-list widget to those registered by the user at display time
	 *
	 * This allows the link-list widget to play well with WordPress, being able
	 * to receive theme-appropriate parameters, but to not appear in a user's
	 * list of available widgets.
	 *
	 * @param array $sidebars a list of sidebars and their registered widgets
	 *
	 * @since 0.1
	 */
	public function add_sidebar_widget( $sidebars )
	{

		// Remove a possible inactive widgets key for purposes of testing
		// whether or not any sidebars have been defined
		$active_sidebars = $sidebars;
		if ( array_key_exists( ClassBlogs_Settings::INACTIVE_WIDGETS_ID, $active_sidebars ) ) {
			unset( $active_sidebars[ClassBlogs_Settings::INACTIVE_WIDGETS_ID] );
		}

		// If no sidebars have been declared, add a single sidebar the list
		// containing the meta widget and the link list
		if ( empty( $active_sidebars ) ) {
			$sidebars[] = $this->get_default_widget_list();
		}

		// If there is one or more active sidebars, try to add our widget to
		// the first one that declares a meta widget, and, failing that, add
		// it to the first sidebar in the list
		else {
			$add_to_sidebar = null;
			foreach ( $active_sidebars as $sidebar => $widgets ) {
				if ( ! $add_to_sidebar ) {
					$add_to_sidebar = $sidebar;
				}
				if ( in_array( ClassBlogs_Settings::META_WIDGET_ID, $widgets ) ) {
					$add_to_sidebar = $sidebar;
					break;
				}
			}

			// If the given sidebar starts with a search widget, place the
			// link list widget below that.  Otherwise, make it the first wdiget
			$search_index = array_search( ClassBlogs_Settings::SEARCH_WIDGET_ID, $sidebars[$add_to_sidebar] );
			if ( $search_index === 0 ) {
				array_splice( $sidebars[$add_to_sidebar], 1, 0, $this->get_uid() );
			} else {
				array_unshift( $sidebars[$add_to_sidebar], $this->get_uid() );
			}
		}

		return $sidebars;
	}

	/**
	 * Outputs markup for the the sidebar link-list widget
	 *
	 * @param array $params a hash of parameters for rendering the sidebar
	 *
	 * @since 0.1
	 */
	public function render_link_list( $params )
	{
		echo $params['before_widget'] . $params['before_title'] . $this->get_option( 'title' ) . $params['after_title'] . '<ul>';

		$link_count = count( $this->get_option( 'links' ) );
		for ( $i = 0; $i < $link_count; $i++ ) {
			$links = $this->get_option( 'links' );
			$link = $links[$i];
			if ( $link['url'] ) {
				$external = ( preg_match( '!^https?://' . get_current_site()->domain . '!', $link['url'] ) ) ? "" : 'rel="external"';
				echo '<li><a ' . $external . ' href="' . $link['url'] . '">' . $link['title'] . '</a></li>';
			}
		}

		echo '</ul>' . $params['after_widget'];
	}

	/**
	 * Adds an admin page for the plugin to the class blogs network admin menu
	 *
	 * @since 0.1
	 */
	public function configure_admin_interface()
	{
		if ( is_super_admin() ) {
			$this->_admin = ClassBlogs_Admin::get_admin();
			$this->_admin->add_admin_page( $this->get_uid(), __( 'Student Blog Links', 'classblogs' ), array( $this, 'admin_page' ) );
		}
	}

	/**
	 * Adds JavaScript files for the network admin interface
	 *
	 * @since 0.1
	 */
	public function add_admin_scripts()
	{
		if ( is_super_admin() ) {
			wp_register_script(
				$this->get_uid(),
				ClassBlogs_Utils::get_plugin_js_url() . 'student-blog-links.js',
				array( 'jquery' ),
				ClassBlogs_Settings::VERSION,
				true );
			wp_print_scripts( $this->get_uid() );
		}
	}

	/**
	 * Parses the list of links added by a user on the admin side
	 *
	 * This is passed the POST data submitted by the user, and looks for any
	 * keys referencing a link name or URL, using the numeric index of each
	 * key to order them in the list.
	 *
	 * @param  array $post the admin form's POST data
	 * @return array       an ordered list of the user's links
	 *
	 * @since 0.1
	 */
	public function parse_link_list( $post )
	{

		$links = array();
		$valid_links = true;
		$current_link = 0;

		// Cycle through all of the user's added links and add any that
		// have a valid URL and name to the options
		while ( $valid_links ) {
			if ( array_key_exists( 'link_url_' . $current_link, $post ) ) {
				$link_url = $post['link_url_' . $current_link];
				$link_title = $post['link_title_' . $current_link];
				if ( $link_url && $link_title ) {
					$links[] = array(
						'url'   => ClassBlogs_Utils::sanitize_user_input( $link_url ),
						'title' => ClassBlogs_Utils::sanitize_user_input( $link_title )
					);
				}
				$current_link++;
			} else {
				$valid_links = false;
			}
		}

		return $links;
	}

	/**
	 * Handles the admin page for the plugin
	 *
	 * @since 0.1
	 */
	public function admin_page() {

		// Update the plugin options
		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$this->update_option( 'title', ClassBlogs_Utils::sanitize_user_input( $_POST['sidebar_title'] ) );
			$this->update_option( 'links', $this->parse_link_list( $_POST ) );

			echo '<div id="message" class="updated fade"><p>' . __( 'Your links have been updated.', 'classblogs' ) . '</p></div>';
		}
	?>
		<div class="wrap">

			<h2><?php _e( 'Student Blog Links', 'classblogs' ); ?></h2>

			<p>
				<?php _e( "This plugin lets you display links of your choosing in the sidebar of every student's blog. You can use this to have a link back to the main blog appear on every student's blog, for example.", 'classblogs' );
				?>
			</p>

			<form method="post" action="">

				<h3><?php _e( 'Options', 'classblogs' ); ?></h3>

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Sidebar Widget Title', 'classblogs' ); ?></th>
							<td>
								<input type="text" name="sidebar_title" id="sidebar-title" value="<?php echo esc_attr( $this->get_option( 'title' ) ); ?>" /><br />
								<label for="sidebar-title"><?php _e( 'The title for the links section of the sidebar.', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<h3><?php _e( 'Links', 'classblogs' ); ?></h3>
					<table class="form-table" id="student-blog-links">
						<?php
							$link_count = max( count( $this->get_option( 'links' ) ), 1 );
							for ( $i = 0; $i < $link_count; $i++ ) {
								$url_id = 'link_url_' . $i;
								$title_id = 'link_title_' . $i;
								$links = $this->get_option( 'links' );
								$link = $links[$i];
						?>

						<tr valign="top" class="link">
							<th scope="row"><?php _e( 'Link', 'classblogs' ); ?></th>
							<td>
								<label for="<?php echo $title_id; ?>"><?php _e( 'Title', 'classblogs' ); ?></label>
								<input type="text" name="<?php echo $title_id; ?>" id="<?php echo $title_id; ?>" value="<?php echo esc_attr( $link['title'] ); ?>" />
								<label style="margin-left: 2em;" for="<?php echo $url_id; ?>"><?php _e( 'URL', 'classblogs' ); ?></label>
								<input size="40" type="text" name="<?php echo $url_id; ?>" id="<?php echo $url_id; ?>" value="<?php echo esc_attr( $link['url'] ); ?>" />
								<a href="#delete-link" class="delete-link"><?php _e( 'Delete', 'classblogs' ); ?></a>
							</td>
						</tr>

						<?php
							}
						?>
					</table>

				<p><a href="#add-link" id="add-student-blog-link"><?php _e( 'Add another link', 'classblogs' ); ?></a></p>
				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Update Student Blog Links Options', 'classblogs' ); ?>" /></p>
			</form>
		</div>
	<?php
	}
}

ClassBlogs::register_plugin( 'student_links', new ClassBlogs_Plugins_StudentBlogLinks() );

?>
