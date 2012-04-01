<?php

class ClassBlogs_Plugins_StudentPortfolio extends ClassBlogs_BasePlugin
{

	/**
	 * Default options for the student portfolio plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.3
	 */
	protected $default_options = array(
		'page_title' => 'Portfolio',
		'tag_list'   => array( 'portfolio' )
	);

	/**
	 * Default per-blog options for any student blogs that have a portfolio.
	 *
	 * @access protected
	 * @var array
	 * @since 0.3
	 */
	protected $default_per_blog_options = array(
		'page_id' => null,
		'posts'   => array()
	);

	/**
	 * Register hooks to customize the portfolio page and watch for posts on
	 * student blogs that use one of the portfolio tags.
	 *
	 * @since 0.3
	 */
	public function __construct()
	{
		parent::__construct();

		// Register the listener for posts using portfolio tags and the hook for
		// showing portfolio posts on the portfolio pageif we're on the admin
		// side of a student blog
		if ( ClassBlogs_Utils::on_student_blog_admin() ) {
			add_action( 'add_meta_boxes', array( $this, '_enable_portfolio_post_list' ) );
			add_action( 'save_post',      array( $this, '_track_tag_usage' ) );
		}
	}

	/**
	 * Register a hook to show a list of portfolio posts on the editing interface
	 * for a student's portfolio page.
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _enable_portfolio_post_list()
	{
		global $post;
		$options = $this->get_per_blog_options();
		if ( (int) $post->ID === $options['page_id'] && ! empty( $options['posts'] ) ) {
			add_meta_box(
				'cb-portfolio-posts',
				__( 'Portfolio Posts', 'classblogs' ),
				array( $this, '_render_portfolio_post_list' ),
				'page',
				'side'
			);
		}
	}

	/**
	 * Render the portfolio post list on the editing page for a portfolio page.
	 *
	 * @param object $post a WordPress post object
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _render_portfolio_post_list( $post )
	{
		wp_nonce_field( $this->get_uid() );

		// TODO: make each of the links in the following HTML use some JavaScript
		// code to embed the relevant data in the post body

		// Display each portfolio post with buttons for inserting content related
		// to them into the body of the editor
		echo '<div id="cb-portfolio-posts-list">';
		foreach ( $this->get_per_blog_option( 'posts' ) as $post_id ) {
			$post = get_post( $post_id );
			printf( '
				<div class="cb-portfolio-posts-post misc-pub-section">
					<strong class="cb-portfolio-posts-title">%1$s</strong>
					<p class="cb-portfolio-posts-published">%7$s</p>
					<p class="cb-portfolio-posts-links">
						<a href="#portfolio-link" class="%2$s" id="cb-portfolio-link__%3$d">%4$s</a>
						<a href="#portfolio-excerpt" class="%2$s" id="cb-portfolio-excerpt__%3$d">%5$s</a>
						<a href="#portfolio-text" class="%2$s" id="cb-portfolio-text__%3$d">%6$s</a>
					</p>
				</div>',
				$post->post_title,
				'cb-portfolio-posts-link',
				$post->ID,
				__( 'Insert Link', 'classblogs' ),
				__( 'Insert Excerpt', 'classblogs' ),
				__( 'Insert Text', 'classblogs' ),
				mysql2date( get_option( 'date_format' ), $post->post_date ) );
		}
		echo '</div>';
	}

	/**
	 * Monitors a newly saved post to see if it uses one of the portfolio tags.
	 *
	 * If the post does use one of the portfolio tags set by the professor, a
	 * page is created on the blog hosting the post if one does not exist.
	 *
	 * @param int $post_id the ID of the post being saved
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _track_tag_usage( $post_id )
	{
		global $blog_id;

		// Abort early if the post is a revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Add the post to the portfolio if a published post is found that
		// uses one of the approved portfolio tags, or remove the post from the
		// portfolio if a published post that uses the tag no longer does, or if
		// a post that uses the tag is no longer publicly visible
		if ( get_post_status( $post_id ) == 'publish' ) {
			if ( $this->_post_uses_portfolio_tag( $post_id ) ) {
				$this->_add_portfolio_post( $post_id );
			} elseif ( $this->_post_in_portfolio( $post_id) ) {
				$this->_delete_portfolio_post( $post_id );
			}
		} else {
			if ( $this->_post_uses_portfolio_tag( $post_id ) && $this->_post_in_portfolio( $post_id ) ) {
				$this->_delete_portfolio_post( $post_id );
			}
		}
	}

	/**
	 * Returns true if the given posts uses one of the tags provided by the
	 * professor that mark a post as being part of a student's portfolio.
	 *
	 * @param  int  $post_id the ID of a post on a student blog
	 * @return bool          whether or not the post uses a portfolio tag
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _post_uses_portfolio_tag( $post_id )
	{
		$post_tags = array();
		foreach ( wp_get_post_tags( $post_id ) as $post_tag ) {
			$post_tags[] = $post_tag->slug;
		}
		foreach ( $this->get_option( 'tag_list' ) as $tag ) {
			if ( false !== array_search( $tag, $post_tags ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns true if the given post is currently part of a student's portfolio.
	 *
	 * @param  int  $post_id the ID of a post on a student blog
	 * @return bool          whether the post is currently part of a portfolio
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _post_in_portfolio( $post_id )
	{
		return false !== array_search( $post_id, $this->get_per_blog_option( 'posts' ) );
	}

	/**
	 * Adds a post to the list of those that will be included in a student's portfolio.
	 *
	 * If the post is already in the list of portfolio posts, no change will be
	 * made.  If it is not, it will be added to the list.
	 *
	 * @param int $post_id the ID of a post to add to the portfolio list
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _add_portfolio_post( $post_id )
	{
		$posts = $this->get_per_blog_option( 'posts' );
		if ( false === array_search( $post_id, $posts ) ) {
			$posts[] = $post_id;
			$this->update_per_blog_option( 'posts', $posts );
		}
		$this->_ensure_portfolio_page_exists();
	}

	/**
	 * Removes a post from the list of a student's portfolio posts.
	 *
	 * If the post is not in the list of portfolio posts, no change will be
	 * made.  If it is, it will be removed from to the list.
	 *
	 * @param int $post_id the ID of a post to remove from the portfolio list
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _delete_portfolio_post( $post_id )
	{
		$posts = $this->get_per_blog_option( 'posts' );
		$position = array_search( $post_id, $posts );
		if ( $position !== false ) {
			$posts = array_splice( $posts, $position + 1, 1 );
			$this->update_per_blog_option( 'posts', $posts );
		}
	}

	/**
	 * Sorts the posts whose IDs are in the given list by their published date.
	 *
	 * @param  array $post_ids the IDs of all posts in a student's portfolio
	 * @return array
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _sort_posts_by_date( $post_ids )
	{
		$posts = array();
		foreach ( $posts_ids as $post_id ) {
			$post = get_post( $post_id );
			$posts[$post_id] = $post->post_date;
		}
		// TODO: return the $posts array sorted by key

	}

	/**
	 * Ensure that a portfolio page exists on the student's blog.
	 *
	 * This is called whenever a student post that contains a portfolio tag
	 * is updated.  If no portfolio page exists or a portfolio page ID is set
	 * but refers to an invalid page, a new page is created.
	 *
	 * @access private
	 * @since 0.3
	 */
	private function _ensure_portfolio_page_exists()
	{
		// Check for a defined page ID or a valid page
		$page_exists = false;
		$page_id = $this->get_per_blog_option( 'page_id' );
		if ( $page_id ) {
			$page = get_page( $page_id );
			$page_exists = ! empty( $page );
		}

		// If no portfolio page was found, create one now
		if ( ! $page_exists ) {
			$current_user = get_currentuserinfo();
			$new_page = array(
				'post_author' => $current_user->ID,
				'post_status' => 'publish',
				'post_title'  => $this->get_option( 'page_title' ),
				'post_type'   => 'page' );
			$page_id = wp_insert_post( $new_page );
			$this->update_per_blog_option( 'page_id', $page_id );
		}
	}

	/**
	 * Updates the student portfolio data when a student saves their portfolio page.
	 *
	 * This takes care of setting things like the order of the portfolio posts
	 * chosen by the student and their commentary on each post.
	 *
	 * @param int $post_id the ID of the portfolio page
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _update_portfolio( $post_id )
	{

		// Abort if the current post does not match our portfolio page ID
		if ( $post_id !== $this->get_per_blog_option( 'page_id' ) ) {
			return;
		}

		// TODO: update the portfolio post data

	}

	/**
	 * Adds an admin page for the plugin to the class blogs admin menu.
	 *
	 * @access protected
	 * @since 0.3
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Student Portfolio', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Handles the admin page for the plugin.
	 *
	 * @access private
	 * @since 0.3
	 */
	public function _admin_page() {

		// Update the plugin options
		if ( $_POST ) {

			check_admin_referer( $this->get_uid() );

			$this->update_option( 'page_title', ClassBlogs_Utils::sanitize_user_input( $_POST['page_title'] ) );

			$tags = explode( ',', ClassBlogs_Utils::sanitize_user_input( $_POST['tag_list'] ) );
			$tags = array_map( 'trim', $tags );
			$tags = array_map( array( 'ClassBlogs_Utils', 'slugify' ), $tags );
			$this->update_option( 'tag_list', $tags );

			ClassBlogs_Admin::show_admin_message( __( 'Your student portfolio settings have been updated.', 'classblogs' ) );
		}
	?>
		<div class="wrap">

			<?php ClassBlogs_Admin::show_admin_icon();  ?>
			<h2><?php _e( 'Student Portfolio', 'classblogs' ); ?></h2>

			<p>
				<?php _e( 'This plugin lets you set a list of tags that students can use to mark certain posts as being part of their portfolio.', 'classblogs' ); ?>
				<?php _e( 'If a student uses one of these tags on a post, they will have a portfolio page created for them on their blog that displays all portfolio posts.', 'classblogs' ); ?>
			</p>

			<form method="post" action="">

				<h3><?php _e( 'Options', 'classblogs' ); ?></h3>

					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Default Portfolio Page Title', 'classblogs' ); ?></th>
							<td>
								<input type="text" name="page_title" id="page-title" value="<?php echo esc_attr( $this->get_option( 'page_title' ) ); ?>" /><br />
								<label for="page-title"><?php _e( 'The default title for the portfolio page created on a student blog.', 'classblogs' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Portfolio Tags', 'classblogs' ); ?></th>
							<td>
								<input size="50" type="text" name="tag_list" id="tag-list" value="<?php echo esc_attr( implode( ', ', $this->get_option( 'tag_list' ) ) ); ?>" /><br />
								<label for="tag-list"><?php _e( 'A comma-separated list of tag slugs that students can apply to a post to mark it as being part of their portfolio.', 'classblogs' ); ?></label>
							</td>
						</tr>
					</table>

				<?php wp_nonce_field( $this->get_uid() ); ?>
				<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Student Portfolio Options', 'classblogs' ); ?>" /></p>
			</form>
		</div>
	<?php
	}
}

ClassBlogs::register_plugin(
	'student_portfolio',
	'ClassBlogs_Plugins_StudentPortfolio',
	__( 'Student Portfolio', 'classblogs' ),
	__( 'Allows students to build a portfolio by tagging posts with certain professor-determined tags.', 'classblogs' )
);

?>
