<?php

/**
 * A widget that displays a list of recent sitewide comments
 *
 * @access private
 * @package Class Blogs
 * @since 0.1
 */
class _ClassBlogs_Plugins_Aggregation_SitewideCommentsWidget extends ClassBlogs_Plugins_SidebarWidget
{

	/**
	 * The length of the comment excerpt in words
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _EXCERPT_LENGTH_WORDS = 15;

	/**
	 * Default options for the sitewide comments widget
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
	 * A container for comment totals by student
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private $_comment_totals_by_student;

	/**
	 * Creates the sitewide comments widget
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'Recent Sitewide Comments', 'classblogs' ),
			__( 'A list of recent comments from across all student blogs', 'classblogs' ),
			'cb-sitewide-recent-comments' );
	}

	/**
	 * Displays the sitewide comments widget
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
	 * Updates the sitewide comments widget
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
	 * Handles the admin logic for the sitewide comments widget
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
 * The sitewide comments plugin
 *
 * This provides a main-blog-only widget that can display a list of recent
 * sitewide comments.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_Aggregation_SitewideComments extends ClassBlogs_Plugins_Aggregation_SitewidePlugin
{

	/**
	 * Cached sitewide comments
	 *
	 * @access private
	 * @var array
	 */
	private $_sitewide_comments;

	/**
	 * The number of comments to show per page on the professor's admin page
	 *
	 * @var int
	 * @since 0.1
	 */
	const COMMENTS_PER_ADMIN_PAGE = 20;

	/**
	 * Enable the recent comments sidebar widget
	 */
	function __construct()
	{
		parent::__construct();
		add_action( 'admin_head',   array( $this, 'add_admin_css' ) );
		add_action( 'widgets_init', array( $this, 'enable_widget' ) );
	}

	/**
	 * Returns a list of all sitewide comments
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
		$cached = $this->get_sw_cache( $cache_key );
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
			WHERE p.ID = c.comment_post_ID AND c.from_blog = p.from_blog $approved_filter
			ORDER BY c.comment_date DESC" );

		// Even if all comments are allowed, don't display spam comments
		if ( ! $approved_only ) {
			global $blog_id;
			$current_blog_id = $blog_id;
			$no_spam = array();
			foreach ( $comments as $comment ) {
				switch_to_blog( $comment->from_blog );
				if ( wp_get_comment_status( $comment->comment_ID ) != 'spam' ) {
					$no_spam[] = $comment;
				}
			}
			ClassBlogs::restore_blog( $current_blog_id );
			$comments = $no_spam;
		}

		$this->set_sw_cache( $cache_key, $comments );
		return $comments;
	}

	/**
	 * Gets a list of recent comments formatted for display in a sidebar widget
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
		$cached = $this->get_sw_cache( 'sidebar' );
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
					get_blogaddress_by_id( $comment->from_blog ),
					get_blog_option( $comment->from_blog, 'blogname' ) );
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
				get_blog_permalink( $comment->from_blog, $comment->comment_post_ID ),
				$comment->comment_ID );

			$comments[] = (object) array(
				'author_name' => $comment->comment_author,
				'content'     => $comment->comment_content,
				'meta'        => $meta,
				'permalink'   => $permalink,
				'post_title'  => $comment->post_title );
		}

		$this->set_sw_cache( 'sidebar', $comments );
		return $comments;
	}

	/**
	 * Enables the recent sitewide comments sidebar widget
	 *
	 * @since 0.1
	 */
	public function enable_widget()
	{
		$this->register_root_only_widget( '_ClassBlogs_Plugins_Aggregation_SitewideCommentsWidget' );
	}

	/**
	 * Configures the plugin's admin page
	 *
	 * @since 0.1
	 */
	public function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'Student Comments', 'classblogs' ), array( $this, 'admin_page' ) );
	}

	/**
	 * Adds CSS for small styling tweaks to the admin pages
	 *
	 * @since 0.1
	 */
	public function add_admin_css()
	{
		printf( '<link rel="stylesheet" href="%ssitewide-comments.css" />',
			esc_url( ClassBlogs_Utils::get_plugin_css_url() ) );
	}

	/**
	 * Shows a professor a list of student comments
	 *
	 * @uses ClassBlogs_Plugins_StudentBlogList
	 *
	 * @since 0.1
	 */
	public function admin_page()
	{
		global $blog_id;
		$current_blog_id = $blog_id;

		// Create a lookup table for student names and blog URLs keyed by blog ID
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
			if ( array_key_exists( $comment->from_blog, $students ) ) {
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

			<table class="widefat" id="cb-sw-student-comments-list">

				<thead>
					<tr>
						<th class="author"><?php _e( 'Author', 'classblogs' ); ?></th>
						<th class="comment"><?php _e( 'Comment', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'For Post', 'classblogs' ); ?></th>
						<th class="student"><?php _e( 'Student Blog', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="left"><?php _e( 'Left On', 'classblogs' ); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="author"><?php _e( 'Author', 'classblogs' ); ?></th>
						<th class="comment"><?php _e( 'Comment', 'classblogs' ); ?></th>
						<th class="post"><?php _e( 'For Post', 'classblogs' ); ?></th>
						<th class="student"><?php _e( 'Student Blog', 'classblogs' ); ?></th>
						<th class="status"><?php _e( 'Status', 'classblogs' ); ?></th>
						<th class="left"><?php _e( 'Left On', 'classblogs' ); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php
						foreach ( $paginator->get_items_for_page( $current_page ) as $comment ):
							switch_to_blog( $comment->from_blog );
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
							<td class="comment">
								<?php comment_text( $comment->comment_ID ); ?>
							</td>
							<td class="post">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( get_blog_permalink( $comment->from_blog, $comment->comment_post_ID ) ),
											esc_html( $comment->post_title ) );
									?>
								</strong>
							</td>
							<td class="student">
								<strong>
									<?php
										printf( '<a href="%s">%s</a>',
											esc_url( $students[$comment->from_blog]['blog_url'] ),
											esc_html( $students[$comment->from_blog]['name'] ) );
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
							<td class="left">
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
	 * Calculates the total number of comments left by each student
	 *
	 * @return array a list of totals for student, keyed by user ID
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _calculate_comment_totals_for_students()
	{
		$cached = $this->get_sw_cache( 'totals' );
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
		$this->set_sw_cache( 'totals', $totals );
		return $totals;
	}

	/**
	 * Gets the total number of comments left by a student
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
		return $wpdb->get_row( $wpdb->prepare( "SELECT comment_date FROM {$this->sw_tables->comments} ORDER BY comment_date DESC LIMIT 1" ) );
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
		return $wpdb->get_row( $wpdb->prepare( "SELECT comment_date FROM {$this->sw_tables->comments} ORDER BY comment_date LIMIT 1" ) );
	}

	/**
	 * Returns a subset of the sitewide comments, filtered by user and date.
	 *
	 * @param  int    $user_id  the ID of the desired comment author
	 * @param  object $start_dt a DateTime instance after which to retrieve comments
	 * @param  object $end_dt   a DateTime instance before which to retrieve comments
	 * @return array            a list of the comments matching the given filters
	 *
	 * @since 0.1
	 */
	public function filter_comments( $user_id, $start_dt, $end_dt )
	{
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->sw_tables->comments} WHERE user_id=%s AND comment_date >= %s AND comment_date <= %s",
			$user_id,
			$start_dt->format( 'YmdHis' ),
			$end_dt->format( 'YmdHis' ) ) );
	}
}

ClassBlogs::register_plugin( 'sitewide_comments', new ClassBlogs_Plugins_Aggregation_SitewideComments() );

?>
