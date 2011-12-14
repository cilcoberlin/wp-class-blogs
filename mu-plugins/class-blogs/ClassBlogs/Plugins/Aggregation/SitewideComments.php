<?php

/**
 * A widget that displays a list of recent sitewide comments.
 *
 * A user can change what information about each comment is shown using a simple
 * string that can contain placeholder variables chosen from a list.  An excerpt
 * of the comment can also be displayed, and the total number of comments, as
 * well as the maximum number of comments shown from each blog, can also be
 * controlled via the widget's admin panel.
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewideCommentsWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_Aggregation_SitewideCommentsWidget extends ClassBlogs_Plugins_SidebarWidget
{

	/**
	 * The length of the comment excerpt in words.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _EXCERPT_LENGTH_WORDS = 15;

	/**
	 * Default options for the sitewide comments widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'max_comments'		    => 5,
		'max_comments_per_blog' => 2,
		'meta_format'           => 'On %date% %time%',
		'show_excerpt'          => true,
		'title' 	   		    => 'Recent Comments'
	);

	/**
	 * Creates the sitewide comments widget.
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Recent Sitewide Comments', 'classblogs' ),
			__( 'A list of recent comments from across all student blogs', 'classblogs' ),
			'cb-sitewide-recent-comments' );
	}

	/**
	 * Displays the sitewide comments widget.
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewideComments to get all sitewide comments
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'sitewide_comments' );

		$sitewide_comments = $plugin->get_comments_for_sidebar(
			$instance['max_comments'],
			$instance['max_comments_per_blog'],
			$instance['meta_format'] );
		if ( empty( $sitewide_comments ) ) {
			return;
		}

		$this->start_widget( $args, $instance );
		echo '<ul>';

		foreach ( $sitewide_comments as $comment ) {
?>
			<li class="cb-sitewide-comment">
				<?php
					printf( _x( '%1$s on %2$s', 'comment author, then post', 'classblogs' ),
						'<span class="cb-sitewide-comment-author">' . esc_html( $comment->author_name ) . '</span>',
						'<a class="cb-sitewide-comment-post" href="' . esc_url( $comment->permalink ) . '">' . esc_html( $comment->post_title ) . '</a>' );
				?>
				<?php if ( $comment->meta ): ?>
					<p class="cb-sitewide-comment-meta"><?php echo $comment->meta; ?></p>
				<?php endif; ?>
				<?php if ( $instance['show_excerpt'] ): ?>
					<p class="cb-sitewide-comment-excerpt"><?php echo esc_html( ClassBlogs_Utils::make_post_excerpt( $comment->content, self::_EXCERPT_LENGTH_WORDS ) ); ?></p>
				<?php endif; ?>
			</li>
<?php
		}

		echo '</ul>';
		$this->end_widget( $args );
	}

	/**
	 * Updates the sitewide comments widget.
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['max_comments']          = absint( ClassBlogs_Utils::sanitize_user_input( $new['max_comments'] ) );
		$instance['max_comments_per_blog'] = absint( ClassBlogs_Utils::sanitize_user_input( $new['max_comments_per_blog'] ) );
		$instance['meta_format']           = ClassBlogs_Utils::sanitize_user_input( $new['meta_format'] );
		$instance['show_excerpt']          = ClassBlogs_Utils::checkbox_as_bool( $new, 'show_excerpt' );
		$instance['title']                 = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the sitewide comments widget.
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
			<label for="<?php echo $this->get_field_id( 'max_comments' ); ?>"><?php _e( 'Comment Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_comments' ); ?>" name="<?php echo $this->get_field_name( 'max_comments' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'max_comments' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_comments_per_blog' ); ?>"><?php _e( 'Comments-per-Blog Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_comments_per_blog' ); ?>" name="<?php echo $this->get_field_name( 'max_comments_per_blog' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'max_comments_per_blog' ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show Comment Excerpt', 'classblogs' ); ?></label>
			<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php if ( $this->safe_instance_attr( $instance, 'show_excerpt' ) ): ?>checked="checked"<?php endif; ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'meta_format' ); ?>"><?php _e( 'Meta Format', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'meta_format' ); ?>" name="<?php echo $this->get_field_name( 'meta_format' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'meta_format' ); ?>" />
		</p>
		<div>
			<h3><?php _e( 'Format variables you can use:', 'classblogs' ) ?></h3>
			<dl>
				<dt>%blog%</dt>
				<dd><?php _e( 'The name of the blog on which the comment was made', 'classblogs' ); ?></dd>
				<dt>%date%</dt>
				<dd><?php _e( 'The date on which the comment was made', 'classblogs' ); ?></dd>
				<dt>%time%</dt>
				<dd><?php _e( 'The time at which the comment was made', 'classblogs' ); ?></dd>
			</dl>
		</div>
<?php
	}
}

/**
 * A plugin that displays data on all of the comments left on any blogs on the site.
 *
 * Sitewide comment data can be shown to any visitors of the blog through a
 * widget that can only appear on the root blog that shows a list of recent
 * comments.  Furthermore, a list of all student comments can be viewed by a
 * professor on the main blog via an admin page that is part of the class-blogs
 * menu group.  Lastly, any students viewing the admin page of their blog are
 * able to see a list of comments that they have left on other blogs on the
 * site through a link available under the comments admin menu group.
 *
 * In addition to these WordPress functions, this plugin provides a few
 * programmatic features that can be used to get direct access to the sitewide
 * comment data.  An example of this is as follows:
 *
 *     // A user with a user ID of 2 leaves three comments on a blog, with ten
 *     // minutes passing between each comment.  Five minutes later, another
 *     // user with a user ID of 3 leaves four comments on another blog, with
 *     // ten minutes passing between each comment.
 *     $sw_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
 *
 *     $all = $sw_comments->get_sitewide_comments();
 *     assert( count( $all ) === 7 );
 *
 *     $filtered = $sw_comments->filter_comments( 2 );
 *     assert( count( $filtered ) === 3 );
 *
 *     assert( $sw_comments->get_total_comments_for_student( 2 ) === 3 );
 *     assert( $sw_comments->get_total_comments_for_student( 3 ) === 4 );
 *
 *     $newest = $sw_comments->get_newest_comment();
 *     $oldest = $sw_comments->get_oldest_comment();
 *     assert( $newest->comment_date > $oldest->comment_date );
 *     assert( $oldest->user_id === 2 );
 *     assert( $newest->user_id === 3 );
 *
 * @package ClassBlogs_Plugins_Aggregation
 * @subpackage SitewideComments
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_SitewideComments extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * Cached sitewide comments.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_sitewide_comments;

	/**
	 * A container for comment totals by student.
	 *
	 * This is used to restrict the number of comments returned if a limit
	 * is imposed on the number of comments allowed per blog.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_comment_totals_by_student;

	/**
	 * Admin media files.
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $admin_media = array(
		'css' => array( 'sitewide-comments.css' )
	);

	/**
	 * The number of comments to show per page on the professor's admin page.
	 *
	 * @var int
	 * @since 0.1
	 */
	const COMMENTS_PER_ADMIN_PAGE = 20;

	/**
	 * Enable the recent comments sidebar widget and the student comment list.
	 */
	function __construct()
	{
		parent::__construct();
		add_action( 'admin_menu',   array( $this, '_add_student_comment_list' ) );
		add_action( 'widgets_init', array( $this, '_enable_widget' ) );
	}

	/**
	 * Enables the recent sitewide comments sidebar widget.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_Aggregation_SitewideCommentsWidget' );
	}

	/**
	 * Configures the plugin's professor-only admin page.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Student Comments', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Shows a professor a list of all student comments.
	 *
	 * @uses ClassBlogs_Plugins_StudentBlogList to get student blog URLs
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _admin_page()
	{
		global $blog_id;
		$current_blog_id = $blog_id;

		// Create a lookup table of student data keyed by blog ID
		$students = array();
		$student_blogs = ClassBlogs::get_plugin( 'student_blogs' );
		foreach ( $student_blogs->get_student_blogs() as $blog ) {
			$user_data = get_userdata( $blog->user_id );
			$students[$blog->blog_id] = array(
				'blog_url' => $blog->url,
				'name' => sprintf( '%s %s', $user_data->first_name, $user_data->last_name ) );
		}

		// Paginate the data, restricting the data set to student-only posts
		$comments = array();
		foreach ( $this->get_sitewide_comments( false ) as $comment ) {
			if ( array_key_exists( $comment->cb_sw_blog_id, $students ) ) {
				$comments[] = $comment;
			}
		}
		$paginator = new ClassBlogs_Paginator( $comments, self::COMMENTS_PER_ADMIN_PAGE );
		$current_page = ( array_key_exists( 'paged', $_GET ) ) ? absint( $_GET['paged'] ) : 1;
?>
		<div class="wrap">

			<h2><?php _e( 'Student Comments', 'classblogs' );  ?></h2>

			<p>
				<?php _e( "This page allows you to view all of the comments that have been left on yours students' blogs.", 'classblogs' );  ?>
			</p>

			<?php $paginator->show_admin_page_links( $current_page ); ?>

			<table class="widefat cb-cw-comments-table" id="cb-sw-student-comments-list">

				<thead>
					<tr>
						<th class="author"><?php _e( 'Author', 'classblogs' ); ?></th>
						<th class="content"><?php _e( 'Comment', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="student"><?php _e( 'Student Blog', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Date', 'classblogs' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="author"><?php _e( 'Author', 'classblogs' ); ?></th>
						<th class="content"><?php _e( 'Comment', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="student"><?php _e( 'Student Blog', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Date', 'classblogs' ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php
						foreach ( $paginator->get_items_for_page( $current_page ) as $comment ):
							switch_to_blog( $comment->cb_sw_blog_id );
							$status = wp_get_comment_status( $comment->comment_ID );
					?>
						<tr class="<?php echo $status; ?>">
							<td class="author">
									<?php
										printf( '%s <strong>%s</strong> <br /> <a href="mailto:%s">%s</a>',
											get_avatar( $comment->comment_author_email, 32 ),
											esc_html( $comment->comment_author ),
											esc_attr( $comment->comment_author_email ),
											esc_html( $comment->comment_author_email ) );
									?>
							</td>
							<td class="content">
								<?php comment_text( $comment->comment_ID ); ?>
							</td>
							<td class="post">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( get_blog_permalink( $comment->cb_sw_blog_id, $comment->comment_post_ID ) ),
											esc_html( $comment->post_title ) );
									?>
								</strong>
							</td>
							<td class="student">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( $students[$comment->cb_sw_blog_id]['blog_url'] ),
											esc_html( $students[$comment->cb_sw_blog_id]['name'] ) );
									?>
								</strong>
							</td>
							<td class="status">
								<?php
									if ( $status == 'approved' ) {
										_e( 'Approved', 'classblogs' );
									} elseif ( $status == 'deleted' || $status == 'trash' ) {
										_e( 'Deleted', 'classblogs' );
									} elseif ( $status == 'spam' ) {
										_e( 'Spam', 'classblogs' );
									} elseif ( $status == 'unapproved' ) {
										_e( 'Unapproved', 'classblogs' );
									} else {
										_e( 'Unknown', 'classblogs' );
									}
								?>
							</td>
							<td class="posted">
								<?php
									printf( '<span class="date">%s</span> <span class="time">%s</span>',
										mysql2date(
											get_option( 'date_format' ),
											$comment->comment_date ),
										mysql2date(
											get_option( 'time_format' ),
											$comment->comment_date ) );
								?>
							</td>
						</tr>
					<?php
						endforeach;
						ClassBlogs::restore_blog( $current_blog_id );
					?>
				</tbody>

			</table>

		</div>
<?php
	}

	/**
	 * Renders the student-only page showing all a list of all comments that
	 * they have left on other blogs on the site.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _student_admin_page()
	{
		global $blog_id;
		$current_blog_id = $blog_id;
		$student_id = wp_get_current_user()->ID;

		// Create a lookup table for blog names and URLs
		$all_blogs = array();
		foreach ( $this->get_all_blog_ids() as $blog_id ) {
			$all_blogs[$blog_id] = array(
				'name' => get_blog_option( $blog_id, 'blogname' ),
				'url' => get_blogaddress_by_id( $blog_id ) );
		}

		// Paginate the data, restricting the data set to only posts that the
		// current student wrote
		$comments = array();
		foreach ( $this->get_sitewide_comments( false ) as $comment ) {
			if ( $comment->user_id === $student_id ) {
				$comments[] = $comment;
			}
		}
		$paginator = new ClassBlogs_Paginator( $comments, self::COMMENTS_PER_ADMIN_PAGE );
		$current_page = ( array_key_exists( 'paged', $_GET ) ) ? absint( $_GET['paged'] ) : 1;
?>

		<div class="wrap">

			<div id="icon-edit-comments" class="icon32"></div>
			<h2><?php _e( 'My Comments', 'classblogs' );  ?></h2>

			<p>
				<?php _e( "This page allows you to view all of the comments that you have left on other students' blogs.", 'classblogs' );  ?>
			</p>

			<?php $paginator->show_admin_page_links( $current_page ); ?>

			<table class="widefat cb-sw-comments-table" id="cb-sw-my-comments-list">

				<thead>
					<tr>
						<th class="blog"><?php _e( 'Blog', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="content"><?php _e( 'Content', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Date', 'classblogs' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="blog"><?php _e( 'Blog', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'Post', 'classblogs' ); ?></th>
						<th class="content"><?php _e( 'Content', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="posted"><?php _e( 'Date', 'classblogs' ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php
						foreach ( $paginator->get_items_for_page( $current_page ) as $comment ):
							switch_to_blog( $comment->cb_sw_blog_id );
							$status = wp_get_comment_status( $comment->comment_ID );
					?>
						<tr class="<?php echo $status; ?>">
							<td class="blog">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( $all_blogs[$comment->cb_sw_blog_id]['url'] ),
											esc_html( $all_blogs[$comment->cb_sw_blog_id]['name'] ) );
									?>
								</strong>
							</td>
							<td class="post">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( get_comment_link( $comment ) ),
											esc_html( $comment->post_title ) );
									?>
								</strong>
							</td>
							<td class="content">
								<?php comment_text( $comment->comment_ID ); ?>
							</td>
							<td class="status">
								<?php
									if ( $status == 'approved' ) {
										_e( 'Approved', 'classblogs' );
									} elseif ( $status == 'deleted' || $status == 'trash' ) {
										_e( 'Deleted', 'classblogs' );
									} elseif ( $status == 'spam' ) {
										_e( 'Spam', 'classblogs' );
									} elseif ( $status == 'unapproved' ) {
										_e( 'Unapproved', 'classblogs' );
									} else {
										_e( 'Unknown', 'classblogs' );
									}
								?>
							</td>
							<td class="posted">
								<?php
									printf( '<span class="date">%s</span> <span class="time">%s</span>',
										mysql2date(
											get_option( 'date_format' ),
											$comment->comment_date ),
										mysql2date(
											get_option( 'time_format' ),
											$comment->comment_date ) );
								?>
							</td>
						</tr>
					<?php
						endforeach;
						ClassBlogs::restore_blog( $current_blog_id );
					?>
				</tbody>

			</table>

		</div>

<?php
	}

	/**
	 * Adds a link that will appear in the comments admin menu group on the
	 * admin side of a student's blog that allows them to view all comments
	 * that they have left on other students' blogs.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _add_student_comment_list()
	{
		if ( is_admin() && ! ClassBlogs_Utils::is_root_blog() ) {
			add_comments_page(
				__('My Comments'),
				__('My Comments'),
				'manage_options',
				$this->get_uid() . '-my-comments',
				array( $this, '_student_admin_page' ) );
		}
	}

	/**
	 * Calculates the total number of comments left by each student.
	 *
	 * @return array a list of totals for student, keyed by user ID
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _calculate_comment_totals_for_students()
	{
		$cached = $this->get_site_cache( 'totals' );
		if ( $cached !== null ) {
			return $cached;
		}

		global $wpdb;
		$totals = array();
		$counts = $wpdb->get_results( "
			SELECT user_id, COUNT(*) AS total
			FROM {$this->sw_tables->comments}
			GROUP BY user_id" );
		foreach ( $counts as $count ) {
			$totals[$count->user_id] = $count->total;
		}
		$this->set_site_cache( 'totals', $totals );
		return $totals;
	}

	/**
	 * Gets the total number of comments left by a student.
	 *
	 * @param  int    $user_id the user ID of a student
	 * @return string          the total number of comments left by the student
	 *
	 * @since 0.1
	 */
	public function get_total_comments_for_student( $user_id )
	{
		if ( ! isset ( $this->_comment_totals_by_student ) ) {
			$this->_comment_totals_by_student = $this->_calculate_comment_totals_for_students();
		}
		if ( array_key_exists( $user_id, $this->_comment_totals_by_student ) ) {
			return $this->_comment_totals_by_student[ $user_id ];
		} else {
			return 0;
		}
	}

	/**
	* Gets the sitewide comment with the newest creation date.
	*
	* @return object a single row from the comments table
	*
	* @since 0.1
	*/
	public function get_newest_comment()
	{
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sw_tables->comments} ORDER BY comment_date DESC LIMIT 1" ) );
	}

	/**
	 * Gets the sitewide comment with the oldest creation date.
	 *
	 * @return object a single row from the comments table
	 *
	 * @since 0.1
	 */
	public function get_oldest_comment()
	{
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->sw_tables->comments} ORDER BY comment_date LIMIT 1" ) );
	}

	/**
	 * Returns a subset of the sitewide comments, filtered by user and date.
	 *
	 * @param  int    $user_id  the ID of the desired comment author
	 * @param  object $start_dt an optional DateTime after which to retrieve comments
	 * @param  object $end_dt   an optional DateTime before which to retrieve comments
	 * @return array            a list of the comments matching the given filters
	 *
	 * @since 0.1
	 */
	public function filter_comments( $user_id, $start_dt=null, $end_dt=null )
	{
		return $this->filter_sitewide_resources(
			$this->sw_tables->comments,
			'user_id', $user_id,
			'comment_date', $start_dt, $end_dt );
	}

	/**
	 * Returns a list of all sitewide comments.
	 *
	 * If desired, this function can be passed a boolean indicating whether or
	 * not to return only approved comments, which is the action performed when
	 * `$approved_only` is true.  By default, this value is true.
	 *
	 * @param  bool  $approved_only only return approved comments
	 * @return array                all sitewide comments
	 *
	 * @since 0.1
	 */
	public function get_sitewide_comments( $approved_only=true )
	{
		// Set a proper cache key based upon which sort of comments are allowed
		$cache_key = 'comments_';
		$cache_key .= ( $approved_only ) ? 'approved' : 'all';

		// Return the cached comments if possible
		$cached = $this->get_site_cache( $cache_key );
		if ( $cached !== null ) {
			return $cached;
		}

		$approved_filter = "";
		if ( $approved_only ) {
			$approved_filter = "AND c.comment_approved = '1'";
		}

		global $wpdb;
		$comments = $wpdb->get_results( "
			SELECT c.*, p.post_title
			FROM {$this->sw_tables->comments} AS c, {$this->sw_tables->posts} AS p
			WHERE p.ID = c.comment_post_ID AND c.cb_sw_blog_id = p.cb_sw_blog_id $approved_filter
			ORDER BY c.comment_date DESC" );

		// Even if all comments are allowed, don't display spam comments
		if ( ! $approved_only ) {
			global $blog_id;
			$current_blog_id = $blog_id;
			$no_spam = array();
			foreach ( $comments as $comment ) {
				switch_to_blog( $comment->cb_sw_blog_id );
				if ( wp_get_comment_status( $comment->comment_ID ) != 'spam' ) {
					$no_spam[] = $comment;
				}
			}
			ClassBlogs::restore_blog( $current_blog_id );
			$comments = $no_spam;
		}

		$this->set_site_cache( $cache_key, $comments );
		return $comments;
	}

	/**
	 * Gets a list of recent comments formatted for display in a sidebar widget.
	 *
	 * The array of returned comments contains custom object instances with the
	 * following properties that can be used by the sidebar:
	 *
	 *      author_name - the display name of the comment's author
	 *      content     - the content of the comment
	 *      meta        - a string describing the comment's meta, constructed from
	 *                    the meta formatting string passed to this method
	 *      permalink   - the permalink URL for the comment
	 *      post_title  - the name of the post on which the comment was made
	 *
	 * @param  int    $max_comments          the maximum number of comments to return
	 * @param  int    $max_comments_per_blog the most comments allowed per blog
	 * @param  string $meta_format           the formatting string for the comment meta
	 * @return array                         an array of formatted comments
	 *
	 * @since 0.1
	 */
	public function get_comments_for_sidebar( $max_comments, $max_comments_per_blog, $meta_format )
	{

		// Use cached values if possible
		$cached = $this->get_site_cache( 'sidebar' );
		if ( $cached !== null ) {
			return $cached;
		}

		$comments = array();
		$raw_comments = $this->limit_sitewide_resources(
			$this->get_sitewide_comments(),
			$max_comments,
			$max_comments_per_blog );

		foreach ( $raw_comments as $comment ) {

			// Create a string for the comment metadata
			$meta = "";
			if ( $meta_format ) {
				$blog = sprintf( '<a href="%s">%s</a>',
					get_blogaddress_by_id( $comment->cb_sw_blog_id ),
					get_blog_option( $comment->cb_sw_blog_id, 'blogname' ) );
				$meta = ClassBlogs_Utils::format_user_string(
					$meta_format,
					array(
						'blog' => $blog,
						'date' => mysql2date( get_option( 'date_format' ), $comment->comment_date ),
						'time' => mysql2date( get_option( 'time_format' ), $comment->comment_date ) ),
					'cb-sitewide-comment' );
			}

			// Build the permalink to the comment using the post URL and an anchor
			$permalink = sprintf( '%s#comment-%d',
				get_blog_permalink( $comment->cb_sw_blog_id, $comment->comment_post_ID ),
				$comment->comment_ID );

			$comments[] = (object) array(
				'author_name' => $comment->comment_author,
				'content'     => $comment->comment_content,
				'meta'        => $meta,
				'permalink'   => $permalink,
				'post_title'  => $comment->post_title );
		}

		$this->set_site_cache( 'sidebar', $comments );
		return $comments;
	}
}

ClassBlogs::register_plugin( 'sitewide_comments', new ClassBlogs_Plugins_Aggregation_SitewideComments() );

?>
