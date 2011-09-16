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
						'<span class="cb-sitewide-comment-author">' . $comment->author_name . '</span>',
						'<a class="cb-sitewide-comment-post" href="' . $comment->permalink . '">' . $comment->post_title . '</a>' );
				?>
				<?php if ( $comment->meta ): ?>
					<p class="cb-sitewide-comment-meta"><?php echo $comment->meta; ?></p>
				<?php endif; ?>
				<?php if ( $instance['show_excerpt'] ): ?>
					<p class="cb-sitewide-comment-excerpt"><?php echo ClassBlogs_Utils::make_post_excerpt( $comment->content, self::_EXCERPT_LENGTH_WORDS ); ?></p>
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
		$instance['max_comments']          = ClassBlogs_Utils::sanitize_user_input( $new['max_comments'] );
		$instance['max_comments_per_blog'] = ClassBlogs_Utils::sanitize_user_input( $new['max_comments_per_blog'] );
		$instance['meta_format']           = ClassBlogs_Utils::sanitize_user_input( $new['meta_format'] );
		$instance['show_excerpt']          = ClassBlogs_Utils::checkbox_as_bool( $new['show_excerpt'] );
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
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_comments' ); ?>"><?php _e( 'Comment Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_comments' ); ?>" name="<?php echo $this->get_field_name( 'max_comments' ); ?>" type="text" value="<?php echo esc_attr( $instance['max_comments'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'max_comments_per_blog' ); ?>"><?php _e( 'Comments-per-Blog Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'max_comments_per_blog' ); ?>" name="<?php echo $this->get_field_name( 'max_comments_per_blog' ); ?>" type="text" value="<?php echo esc_attr( $instance['max_comments_per_blog'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show Comment Excerpt', 'classblogs' ); ?></label>
			<input class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" <?php if ( $instance['show_excerpt'] ): ?>checked="checked"<?php endif; ?> />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'meta_format' ); ?>"><?php _e( 'Meta Format', 'classblogs' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'meta_format' ); ?>" name="<?php echo $this->get_field_name( 'meta_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['meta_format'] ); ?>" />
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
	 * Enable the recent comments sidebar widget
	 */
	function __construct()
	{
		parent::__construct();
		add_action( 'widgets_init', array( $this, 'enable_widget' ) );
	}

	/**
	 * Returns a list of all sitewide comments
	 *
	 * @return array all sitewide comments
	 *
	 * @since 0.1
	 */
	public function get_sitewide_comments()
	{

		// Return the cached comments if possible
		if ( isset( $this->_sitewide_comments ) ) {
			return $this->_sitewide_comments;
		}

		global $wpdb;

		$comments = $wpdb->get_results( "
			SELECT c.comment_ID, c.comment_post_ID, c.comment_content, c.from_blog, c.comment_author,
				   c.comment_date, c.user_id, p.post_title
			FROM {$this->sw_tables->comments} AS c, {$this->sw_tables->posts} AS p
			WHERE p.ID = c.comment_post_ID AND c.from_blog = p.from_blog
			ORDER BY c.comment_date DESC" );

		$this->_sitewide_comments = $comments;
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
	 * Gets the total number of comments left by a student
	 *
	 * @param  int    $user_id the user ID of a student
	 * @return string          the total number of comments left by the student
	 *
	 * @since 0.1
	 */
	public function get_total_comments_for_student( $user_id ) {
		$count = 0;
		foreach ( $this->get_sitewide_comments() as $comment ) {
			$count += $comment->user_id == $user_id;
		}
		return $count;
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
	 * @param  int    $user_id    the ID of the desired comment author
	 * @param  object $start_date the start date of the date filter window
	 * @param  object $end_date   the end date of the date filter window
	 * @return array              a list of the comments matching the given filters
	 *
	 * @since 0.1
	 */
	public function filter_comments( $user_id, $start_date, $end_date )
	{
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->sw_tables->comments} WHERE user_id=%s AND comment_date >= %s AND comment_date <= %s",
			$user_id,
			$start_date->format( 'Ymd' ),
			$end_date->format( 'Ymd' ) ) );
	}
}

ClassBlogs::register_plugin( 'sitewide_comments', new ClassBlogs_Plugins_Aggregation_SitewideComments() );

?>
