<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Settings.php' );
ClassBlogs::require_cb_file( 'Utils.php' );
ClassBlogs::require_cb_file( 'WordPress.php' );

/**
 * A plugin that allows a professor to make certain links always appear in
 * a widget shown in the first widgetized area of any student blogs.
 *
 * This plugin provides an admin menu that allows a professor to
 * add unlimited links of their choosing to student blogs.  These links will
 * appear on every student blog as the first widget in the first widgetized
 * area of the theme in use.
 *
 * @package ClassBlogs_Plugins
 * @subpackage StudentBlogLinks
 * @since 0.1
 */
class ClassBlogs_Plugins_StudentBlogLinks extends ClassBlogs_BasePlugin
{
	/**
	 * The default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'links' => array(),
		'title' => 'Class Links'
	);

	/**
	 * Admin media files.
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $admin_media = array(
		'js' => array( 'student-blog-links.js' )
	);

	/**
	 * A list of regex searches for unused sidebar IDs.
	 *
	 * This includes sidebar IDs used to collect inactive or orphaned widgets,
	 * which should not be viewed as actual widgetized areas.
	 *
	 * @access private
	 * @var array
	 * @since 0.3
	 */
	private $_INACTIVE_SIDEBARS = array(
		'/^wp_inactive_widgets$/',
		'/^orphaned_widgets(_\d+)?$/'
	);

	/**
	 * The base ID WordPress uses for the meta widget.
	 *
	 * @var string
	 * @since 0.3
	 */
	const _META_WIDGET_BASE_ID = 'meta';

	/**
	 * The base ID WordPress uses for the search widget.
	 *
	 * @var string
	 * @since 0.3
	 */
	const _SEARCH_WIDGET_BASE_ID = 'search';

	/**
	 * Registers plugin hooks once all plugins have loaded.
	 */
	function __construct()
	{
		parent::__construct();
		add_action( 'plugins_loaded', array( $this, '_register_hooks' ) );
	}

	/**
	 * Registers hooks and sets default options for the plugin.
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _register_hooks()
	{
		// Update the default links options to be a single link pointing back
		// to the main class blog
		ClassBlogs_WordPress::switch_to_blog( ClassBlogs_Settings::get_root_blog_id() );
		$this->default_options['links'] = array(
			array(
				'url'   => site_url(),
				'title' => __( 'Return to Course Blog', 'classblogs' )
			)
		);
		ClassBlogs_WordPress::restore_current_blog();

		// If there are any links defined and we're not in the admin side,
		// inject the professor's links into a widget in the first widgetized
		// area of all student blogs in the network
		$links = $this->get_option( 'links' );
		if ( ! is_admin() && ! empty( $links ) ) {
			add_action( 'init', array( $this, '_register_widget' ) );
			add_filter( 'sidebars_widgets', array( $this, '_add_widget' ) );
		}
	}

	/**
	 * Register the link-list widget if not on the root blog.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _register_widget()
	{
		if ( ! ClassBlogs_Utils::is_root_blog() ) {
			$uid = $this->get_uid();
			wp_register_sidebar_widget(
				$uid,
				$this->get_option( 'title' ),
				array( $this, '_render_link_list' ),
				array( 'classname' => $uid ) );
		}
	}

	/**
	 * Adds the link-list widget to those registered by the user at display time.
	 *
	 * This allows the link-list widget to play well with WordPress, being able
	 * to receive theme-appropriate parameters, but to not appear in a user's
	 * list of available widgets.
	 *
	 * @param array $areas a list of widgetized areas and their registered widgets
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _add_widget( $areas )
	{
		// Remove inactive sidebars from the list of widgetized areas
		$inactive_keys = array();
		foreach ( $areas as $area => $contents ) {
			foreach ( $this->_INACTIVE_SIDEBARS as $search ) {
				if ( preg_match( $search, $area ) ) {
					$inactive_keys[] = $area;
				}
			}
		}
		$active_areas = $areas;
		foreach ( $inactive_keys as $inactive ) {
			unset( $active_areas[$inactive] );
		}

		// If there is at least one active widgetized area, try to add our widget to
		// the first one that declares a meta widget, and, failing that, add
		// it to the first area in the list
		if ( !empty( $active_areas ) ) {
			$add_to_area = null;
			foreach ( $active_areas as $area => $widgets ) {
				if ( ! $add_to_area ) {
					$add_to_area = $area;
				}
				if ( ClassBlogs_Utils::widget_search( self::_META_WIDGET_BASE_ID, $widgets ) !== false ) {
					$add_to_area = $area;
					break;
				}
			}

			// If the given area does not yet contain an instance of the blog-links
			// widget, examine its contents.  If itstarts with a search widget,
			// place the link list widget below that.  Otherwise, make it the
			// first widget in the list.
			if ( ClassBlogs_Utils::widget_search( $this->get_uid(), $areas[$add_to_area] ) === false ) {
				$search_index = ClassBlogs_Utils::widget_search( self::_SEARCH_WIDGET_BASE_ID, $areas[$add_to_area] );
				if ( $search_index === 0 ) {
					array_splice( $areas[$add_to_area], 1, 0, $this->get_uid() );
				} else {
					array_unshift( $areas[$add_to_area], $this->get_uid() );
				}
			}
		}

		return $areas;
	}

	/**
	 * Outputs markup for the link-list widget.
	 *
	 * @param array $params a hash of parameters for rendering the widget
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _render_link_list( $params )
	{
		echo $params['before_widget'] . $params['before_title'] . esc_html( $this->get_option( 'title' ) ) . $params['after_title'] . '<ul>';

		$link_count = count( $this->get_option( 'links' ) );
		for ( $i = 0; $i < $link_count; $i++ ) {
			$links = $this->get_option( 'links' );
			$link = $links[$i];
			if ( $link['url'] ) {
				printf( '<li><a %s href="%s">%s</a></li>',
					( preg_match( '!^https?://' . get_current_site()->domain . '!', $link['url'] ) ) ? "" : 'rel="external"',
					esc_url( $link['url'] ),
					esc_html( $link['title'] ) );
			}
		}

		echo '</ul>' . $params['after_widget'];
	}

	/**
	 * Adds an admin page for the plugin to the class blogs admin menu.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Student Blog Links', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Parses the list of links added by a user on the admin side.
	 *
	 * This is passed the POST data submitted by the user, and looks for any
	 * keys referencing a link name or URL, using the numeric index of each
	 * key to order them in the list.
	 *
	 * @param  array $post the admin form's POST data
	 * @return array       an ordered list of the user's links
	 *
	 * @access private
	 * @since 0.2
	 */
	private function _parse_link_list( $post )
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
						'url'   => esc_url_raw( ClassBlogs_Utils::sanitize_user_input( $link_url ) ),
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
	 * Handles the admin page for the plugin.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _admin_page() {

		// Update the plugin options
		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$this->update_option( 'title', ClassBlogs_Utils::sanitize_user_input( $_POST['widget_title'] ) );
			$this->update_option( 'links', $this->_parse_link_list( $_POST ) );

			ClassBlogs_Admin::show_admin_message( __( 'Your links have been updated.', 'classblogs' ) );
		}
	?>
		<div class="wrap">

			<?php ClassBlogs_Admin::show_admin_icon();  ?>
			<h2><?php _e( 'Student Blog Links', 'classblogs' ); ?></h2>

			<p>
				<?php _e( "This plugin lets you display links of your choosing in the first widgetized area of every student's blog. You can use this to have a link back to the main blog appear on every student's blog, for example.", 'classblogs' );
				?>
			</p>

			<form method="post" action="">

				<h3><?php _e( 'Options', 'classblogs' ); ?></h3>

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Widget Title', 'classblogs' ); ?></th>
							<td>
								<input type="text" name="widget_title" id="widget-title" value="<?php echo esc_attr( $this->get_option( 'title' ) ); ?>" /><br />
								<label for="widget-title"><?php _e( 'The title for the the list of links.', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<h3><?php _e( 'Links', 'classblogs' ); ?></h3>
					<table class="form-table" id="student-blog-links">
						<tfoot>
							<th scope="row">
								<a href="#add-link" class="add-link"><?php _e( 'Add another link', 'classblogs' ); ?></a>
							</th>
							<td></td>
						</tfoot>
						<tbody>
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
								<label for="<?php echo esc_attr( $title_id ); ?>"><?php _e( 'Title', 'classblogs' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $title_id ); ?>" id="<?php echo esc_attr( $title_id ); ?>" value="<?php echo esc_attr( $link['title'] ); ?>" />
								<label style="margin-left: 2em;" for="<?php echo esc_attr( $url_id ); ?>"><?php _e( 'URL', 'classblogs' ); ?></label>
								<input size="40" type="text" name="<?php echo esc_attr( $url_id ); ?>" id="<?php echo esc_attr( $url_id ); ?>" value="<?php echo esc_url( $link['url'] ); ?>" />
								<a href="#delete-link" class="delete-link"><?php _e( 'Delete', 'classblogs' ); ?></a>
							</td>
						</tr>

						<?php
							}
						?>
						</tbody>
					</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Links', 'classblogs' ); ?>" /></p>
			</form>
		</div>
	<?php
	}
}

ClassBlogs::register_plugin(
	'student_links',
	'ClassBlogs_Plugins_StudentBlogLinks',
	__( 'Student Blog Links', 'classblogs' ),
	__( 'Allows you to put links of your choosing in a sidebar on all student blogs.', 'classblogs' )
);

?>
