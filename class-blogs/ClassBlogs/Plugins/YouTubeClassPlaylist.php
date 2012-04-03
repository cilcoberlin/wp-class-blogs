<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Http.php' );
ClassBlogs::require_cb_file( 'PluginPage.php' );
ClassBlogs::require_cb_file( 'Schema.php' );
ClassBlogs::require_cb_file( 'Settings.php' );
ClassBlogs::require_cb_file( 'Utils.php' );
ClassBlogs::require_cb_file( 'Widget.php' );
ClassBlogs::require_cb_file( 'WordPress.php' );
ClassBlogs::require_cb_file( 'Plugins/Aggregation/SitewidePosts.php' );

/**
 * A widget that displays the most recent additions to the YouTube class playlist.
 *
 * This widget can be configured to adjust the maximum number of videos displayed
 * in the playlist.
 *
 * @package ClassBlogs_Plugins
 * @subpackage YouTubeClassPlaylistWidget
 * @access private
 * @since 0.1
 */
class _ClassBlogs_Plugins_YouTubeClassPlaylistWidget extends ClassBlogs_Widget
{

	/**
	 * Default options for the class playlist widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array(
		'title' => 'Our YouTube Playlist',
		'limit' => 3
	);

	/**
	 * The name of the plugin.
	 */
	protected function get_name()
	{
		return __( 'YouTube Class Playlist', 'classblogs' );
	}

	/**
	 * The description of the plugin.
	 */
	protected function get_description()
	{
		return __( 'A list of YouTube videos that have been recently added to the class playlist', 'classblogs' );
	}

	/**
	 * Displays the class playlist widget.
	 *
	 * @uses ClassBlogs_Plugins_YouTubeClassPlaylist to get recent playlist videos
	 */
	public function widget( $args, $instance )
	{
		$instance = $this->maybe_apply_instance_defaults( $instance );
		$plugin = ClassBlogs::get_plugin( 'youtube_class_playlist' );

		$recent_videos = $plugin->get_recent_videos_for_widget( $instance['limit'] );
		if ( empty( $recent_videos ) ) {
			return;
		}

		$this->start_widget( $args, $instance );
?>
		<ul>
			<?php foreach ( $recent_videos as $video ): ?>
				<li class="cb-youtube-video">
					<?php if ( ! empty( $video->thumbnail ) ): ?>
						<p class="cb-youtube-video-image-link">
							<a rel="external" href="<?php echo esc_url( $video->link ); ?>">
								<img alt="<?php echo esc_attr( $video->title ); ?>" class="cb-youtube-video-thumbnail" src="<?php echo esc_url( $video->thumbnail ); ?>" width="100%" />
							</a>
						</p>
					<?php endif; ?>
					<a class="cb-youtube-video-title" href="<?php echo esc_url( $video->link ); ?>" rel="external"><?php echo esc_html( $video->title ); ?></a>
				</li>
			<?php endforeach; ?>

			<li class="cb-youtube-local-playlist-link">
				<a href="<?php echo esc_url( $plugin->get_local_playlist_page_url() ); ?>"><?php _e( 'View all embedded videos', 'classblogs' ); ?></a>
			</li>

		</ul>
<?php
		$this->end_widget( $args );
	}

	/**
	 * Updates the class playlist widget.
	 */
	public function update( $new, $old )
	{
		$instance = $old;
		$instance['limit'] = absint( ClassBlogs_Utils::sanitize_user_input( $new['limit'] ) );
		$instance['title'] = ClassBlogs_Utils::sanitize_user_input( $new['title'] );
		return $instance;
	}

	/**
	 * Handles the admin logic for the class playlist widget.
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
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'Video Limit', 'classblogs' ); ?></label>
			<input size="3" id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" type="text" value="<?php echo $this->safe_instance_attr( $instance, 'limit' ); ?>" />
		</p>
<?php
	}
}

/**
 * A plugin that allows a YouTube playlist to be associated with the blog, making
 * any YouTube videos embedded on any blogs on the site be added to the playlist.
 *
 * This plugin displays information about the playlist in two different ways.
 * First, it provides a widget available on the root blog that shows the most
 * recent additions to the playlist.  Second, it allows a user to view a list
 * of all videos in the playlist and the posts that reference them.
 *
 * This plugin also provides a simple interface to get information about the
 * YouTube class playlist, which is demonstrated below:
 *
 *     // A post using 2 embedded YouTube videos is created
 *     $plugin = ClassBlogs::get_plugin( 'youtube_class_playlist' );
 *     assert( count( $plugin->get_playlist_videos() ) === 2 );
 *     echo "The local playlist page be viewed at " . $plugin->get_local_playlist_page_url() . "\n";
 *
 * @package ClassBlogs_Plugins
 * @subpackage YouTubeClassPlaylist
 * @since 0.1
 */
class ClassBlogs_Plugins_YouTubeClassPlaylist extends ClassBlogs_BasePlugin
{

	/**
	* The URL for viewing a full-size thumbnail of a YouTube video.
	*
	* @access private
	* @var string
	* @since 0.1
	*/
	const _YOUTUBE_FULL_SIZE_THUMBNAIL_URL_TEMPLATE = 'http://img.youtube.com/vi/%s/0.jpg';

	/**
	 * The URL for requesting information about a single YouTube video.
	 *
	 * @access private
	 * @var string
	 * @since 0.3
	 */
	const _YOUTUBE_VIDEO_INFO_URL_TEMPLATE = 'https://gdata.youtube.com/feeds/api/videos/%s?v=2';

	/**
	 * The URL for viewing a video on YouTube.
	 *
	 * @access private
	 * @var string
	 * @since 0.3
	 */
	const _YOUTUBE_VIDEO_PAGE_URL_TEMPLATE = 'http://www.youtube.com/watch?v=%s';

	/**
	 * The expected length of a YouTube video ID.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _YOUTUBE_VIDEO_ID_LENGTH = 11;

	/**
	 * The namespace used for YouTube-specific tags in API responses.
	 *
	 * @access private
	 * @var string
	 * @since 0.4
	 */
	const _YT_NAMESPACE = "http://gdata.youtube.com/schemas/2007";

	/**
	 * The length in seconds to cache the playlist locally.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _PLAYLIST_CACHE_LENGTH = 300;

	/**
	 * The prefix for any tables created by this plugin.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _TABLE_PREFIX = 'yt_';

	/**
	 * The base name for the videos table.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _VIDEOS_TABLE = 'videos';

	/**
	 * The base name for the video usage table.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _VIDEO_USAGE_TABLE = 'video_usage';

	/**
	 * The default name of the playlist page.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _PLAYLIST_PAGE_DEFAULT_NAME = 'Our YouTube Class Playlist';

	/**
	 * A list of functions used to extract YouTube video IDs from post content.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private static $_video_searchers = array(
		'_find_videos_by_url'
	);

	/**
	 * Default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'info_queue'       => array(),
		'playlist_page_id' => null
	);

	/**
	 * A mapping of publicly accessible table short names to base names.
	 *
	 * @access private
	 * @var array
	 * @since 0.1
	 */
	private static $_table_map = array(
		'videos'      => self::_VIDEOS_TABLE,
		'video_usage' => self::_VIDEO_USAGE_TABLE
	);

	/**
	 * Gets the schema used for the videos table.
	 *
	 * @return ClassBlogs_Schema an instance of the videos schema
	 *
	 * @access private
	 * @since 0.2
	 */
	private static function _get_videos_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'id',         'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'youtube_id', 'varchar(11) NOT NULL' ),
				array( 'title',      'varchar(255)' ),
				array( 'date_added', 'datetime NOT NULL' )
			),
			'id',
			array(
				array( 'youtube_id', 'youtube_id' ),
			)
		);
	}

	/**
	 * Gets the schema used for the video usage table.
	 *
	 * @return ClassBlogs_Schema an instance of the video usage schema
	 *
	 * @access private
	 * @since 0.2
	 */
	private static function _get_video_usage_schema()
	{
		return new ClassBlogs_Schema(
			array(
				array( 'id',         'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'blog_id',    'bigint(20) unsigned NOT NULL' ),
				array( 'post_id',    'bigint(20) unsigned NOT NULL' ),
				array( 'video_id',   'bigint(20) unsigned NOT NULL' )
			),
			'id',
			array(
				array( 'video_id',   'video_id' ),
				array( 'blog_usage', array( 'blog_id', 'post_id', 'video_id' ) )
			)
		);
	}

	/**
	 * The names of the tables used by the plugin.
	 *
	 * The table names available are as follows:
	 *
	 *     videos      - a table containing a record of each video on the site
	 *     video_usage - a table mapping videos to posts
	 *
	 * @access protected
	 * @var object
	 * @since 0.2
	 */
	protected $tables;

	/**
	 * Registers the necessary WordPress hooks to make the playlist work.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->tables = $this->_make_table_names();

		// Register hooks for finding videos in post content and for showing the
		// playlist archive page
		add_action( 'deleted_post',  array( $this, '_update_videos_on_post_delete' ) );
		add_action( 'pre_get_posts', array( $this, '_maybe_enable_playlist_page' ) );
		add_action( 'save_post',     array( $this, '_update_videos_on_post_save' ) );
		add_action( 'widgets_init',  array( $this, '_enable_widget' ) );
	}

	/**
	 * Create tables and pages when the plugin is activated.
	 *
	 * @since 0.4
	 */
	public function activate()
	{
		$this->_create_tables();
		$this->_ensure_playlist_page_is_created();
		$this->_sync_playlist();
	}

	/**
	 * Clean up tables and pages on deactivation.
	 */
	public function deactivate()
	{

		// Remove all created tables
		global $wpdb;
		$delete_tables = array(
			$this->tables->videos,
			$this->tables->video_usage
		);
		foreach ( $delete_tables as $table ) {
			$wpdb->query( "DROP TABLE $table" );
		}

		// Delete the local playlist page
		$page_id = $this->get_option( 'playlist_page_id' );
		if ( $page_id ) {
			ClassBlogs_WordPress::switch_to_blog( ClassBlogs_Settings::get_root_blog_id() );
			wp_delete_post( $page_id, true);
			$this->update_option( 'playlist_page_id', null );
			ClassBlogs_WordPress::restore_current_blog();
		}
	}

	/**
	 * Returns an object whose properties are the names of tables used by this plugin.
	 *
	 * @return object the tables used by this plugin
	 *
	 * @acces private
	 * @since 0.1
	 */
	private function _make_table_names()
	{
		$tables = array();
		foreach ( self::$_table_map as $short_name => $base_name ) {
			$tables[$short_name] = ClassBlogs_Utils::make_table_name( self::_TABLE_PREFIX . $base_name );
		}
		return (object) $tables;
	}

	/**
	 * Creates tables used by the plugin.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _create_tables()
	{
		$table_specs = array(
			array( $this->tables->videos, $this->_get_videos_schema() ),
			array( $this->tables->video_usage, $this->_get_video_usage_schema() )
		);
		foreach ( $table_specs as $spec ) {
			$spec[1]->apply_to_table( $spec[0] );
		}
	}

	/**
	 * Ensures that the page used for showing the list of all videos in the
	 * playlist and their per-blog usage exists.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _ensure_playlist_page_is_created()
	{
		if ( ClassBlogs_Utils::is_root_blog() ) {
			$current_page = $this->get_option( 'playlist_page_id' );
			$page_id = ClassBlogs_PluginPage::create_plugin_page( self::_PLAYLIST_PAGE_DEFAULT_NAME, $current_page );
			if ( $page_id != $current_page ) {
				$this->update_option( 'playlist_page_id', $page_id );
			}
		}
	}

	/**
	 * Populates the YouTube playlist from the videos in all posts on the site.
	 *
	 * @access private
	 * @since 0.4
	 */
	private function _sync_playlist()
	{
		$plugin = ClassBlogs::get_plugin( 'sitewide_posts' );
		if ( ! empty( $plugin ) ) {

			// Create records of all videos used
			foreach ( $plugin->get_sitewide_posts() as $post ) {
				ClassBlogs_WordPress::switch_to_blog( $post->cb_sw_blog_id );
				$this->_update_videos_on_post_save( $post->ID );
				ClassBlogs_WordPress::restore_current_blog();
			}

			// Request information on all added videos from YouTube
			$this->_retrieve_queued_video_info();
		}
	}

	/**
	 * Enables the recent playlist videos widgets.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _enable_widget()
	{
		ClassBlogs_Widget::register_root_only_widget( '_ClassBlogs_Plugins_YouTubeClassPlaylistWidget' );
	}

	/**
	 * Enables the display of the video-listing page if the user is on
	 * the correct page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _maybe_enable_playlist_page()
	{
		if ( ClassBlogs_Utils::is_page( $this->get_option( 'playlist_page_id' ) ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, '_add_playlist_page_scripts' ) );
			add_filter( 'the_content', array( $this, '_render_playlist_page' ) );
		}
	}

	/**
	* Enqueues the JavaScript needed for displaying the videos page.
	*
	* @access private
	* @since 0.1
	*/
	public function _add_playlist_page_scripts()
	{
		wp_register_script(
			$this->get_uid(),
			ClassBlogs_Utils::get_base_js_url() . 'youtube-class-playlist.js',
			array( 'jquery' ),
			ClassBlogs_Settings::VERSION
		);
		wp_enqueue_script( $this->get_uid() );
	}

	/**
	 * Returns markup for the local videos page.
	 *
	 * @param  string $content the current content of the page
	 * @return string          markup for the local playlist page
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _render_playlist_page( $content )
	{

		$markup = "";
		foreach ( $this->get_playlist_videos() as $index => $video ) {

			// Add the video with a title
			$markup .= '<div class="cb-youtube-local-playlist-page-video post hentry">';
			$markup .= '<h2 class="cb-youtube-local-playlist-page-title"><a href="' . esc_url( $video->link ) . '" title="' . __( 'View on YouTube', 'classblogs' ) . '">' . esc_html( $video->title ) . '</a></h2>';
			$markup .= '<div class="cb-youtube-local-playlist-page-video-thumbnail-container">';
			$markup .= sprintf( '<a href="%1$s"><img src="%2$s" title="%3$s" alt="%3$s" data-youtube-id="%4$s" class="cb-youtube-local-playlist-page-video-thumbnail" /></a>',
				esc_url( $video->link ),
				esc_url( $this->_get_large_thumbnail_url( $video->youtube_id ) ),
				esc_attr( $video->title ),
				esc_attr( $video->youtube_id ) );
			$markup .= '</div>';

			// Add metadata for the video
			$markup .= sprintf( '<p class="cb-youtube-local-playlist-page-meta">%s</p>', sprintf(
				__( 'Added to the playlist on %s', 'classblogs' ),
				sprintf( '<time datetime="%s" class="cb-youtube-local-playlist-page-date">%s</time>',
					date( 'c', strtotime( $video->date_added ) ),
					esc_html( date_i18n( get_option( 'date_format' ) ,strtotime( $video->date_added ) ) ) )
			) );
			if ( ! empty( $video->used_by ) ) {
				$markup .= '<p class="cb-youtube-local-playlist-page-usage">' . __( 'Embedded in', 'classblogs' ) . ' ';
				$links = array();
				foreach ( $video->used_by as $usage ) {
					$link = '<a class="cb-youtube-local-playlist-page-usage-post" ';
					ClassBlogs_WordPress::switch_to_blog( $usage->blog_id );
					$link .= sprintf( ' href="%s">%s</a>',
						esc_url( get_permalink( $usage->post_id ) ),
						get_post( $usage->post_id )->post_title );
					ClassBlogs_WordPress::restore_current_blog();
					$links[] = $link;
				}
				$markup .= implode( ', ', $links ) . '</p>';
			}
			$markup .= '</div>';
		}

		return $content . $markup;
	}

	/**
	 * Gets the URL of the full-size thumbnail of a YouTube video.
	 *
	 * @param  string $video_id the YouTube video ID
	 * @return string           the URL of the video's full-size thumbnail
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_large_thumbnail_url( $video_id )
	{
		return sprintf( self::_YOUTUBE_FULL_SIZE_THUMBNAIL_URL_TEMPLATE, $video_id );
	}

	/**
	 * Clears the playlist cache.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _clear_cached_playlist()
	{
		$this->clear_site_cache( 'playlist' );
	}

	/**
	 * Updates the YouTube playlist with the videos found in the just-saved post.
	 *
	 * @param int $post_id the ID of the just-saved post
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _update_videos_on_post_save( $post_id )
	{

		global $wpdb, $blog_id;

		// Ignore post revisions, but remove videos associated with any posts
		// that are not publicly visible
		$post = get_post( $post_id );
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( $post->post_status != "publish" ) {
			$this->_update_videos_on_post_delete( $post_id );
			return;
		}

		// Determine which videos were previously used in the post content but
		// are no longer present
		$current_videos = $this->_find_video_ids_in_post_content( $post->post_content );
		$previous_videos = $wpdb->get_col( $wpdb->prepare( "
			SELECT v.youtube_id FROM {$this->tables->video_usage} AS vu, {$this->tables->videos} AS v
			WHERE vu.blog_id = %d AND vu.post_id = %d AND vu.video_id = v.id",
			$blog_id, $post_id ) );
		$unused_videos = array_values( array_diff( $previous_videos, $current_videos ) );

		// Update our local video usage records
		foreach ( $current_videos as $video ) {
			$this->_add_video_usage( $video, $post_id, $blog_id );
			$this->_retrieve_queued_video_info();
		}
		foreach ( $unused_videos as $video ) {
			$this->_remove_video_usage( $video, $post_id, $blog_id );
		}
	}

	/**
	 * Updates the YouTube playlist by removing videos that were embedded in a
	 * post that is about to be deleted.
	 *
	 * @param int $post_id The ID of the just-deleted post
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _update_videos_on_post_delete( $post_id )
	{

		global $wpdb, $blog_id;

		// Remove any videos used by the post
		$used_videos = $wpdb->get_col( $wpdb->prepare( "
			SELECT v.youtube_id FROM {$this->tables->video_usage} AS vu, {$this->tables->videos} AS v
			WHERE vu.blog_id = %d AND vu.post_id = %d AND vu.video_id = v.id",
			$blog_id, $post_id ) );
		foreach ( $used_videos as $video ) {
			$this->_remove_video_usage( $video, $post_id, $blog_id );
		}
	}

	/**
	 * Adds a local record of the YouTube video being used by the given post.
	 *
	 * If the given YouTube ID does not actually map to a video, this will
	 * abort early and not add the video to the local playlist.
	 *
	 * @param  int $youtube_id the ID of an embedded YouTube video
	 * @param  int $post_id    the ID of the post using the video
	 * @param  int $blog_id    the ID of the blog on which the post was made
	 * @return int             the ID of the new usage record
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _add_video_usage( $youtube_id, $post_id, $blog_id )
	{
		global $wpdb;

		// Get the video's internal ID, creating a new record for it if none
		// can be found, and aborting if the video isn't an actual YouTube video
		$video_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT id FROM {$this->tables->videos}
			WHERE youtube_id = %s",
			$youtube_id ) );
		if ( ! $video_id ) {
			$info = $this->_add_video_to_info_queue( $youtube_id );
			$wpdb->insert(
				$this->tables->videos,
				array(
					'date_added' => date('Y-m-d G:i:s'),
					'youtube_id' => $youtube_id
				),
				array( '%s' ) );
			$video_id = $wpdb->insert_id;
		}

		// Add a new video usage record if there is not already a record of the
		// video being used by the current post and blog
		$record = $wpdb->get_row( $wpdb->prepare( "
			SELECT id FROM {$this->tables->video_usage}
			WHERE blog_id = %s AND post_id = %s AND video_id = %s",
			$blog_id, $post_id, $video_id ) );
		if ( empty( $record ) ) {
			$wpdb->insert(
				$this->tables->video_usage,
				array(
					'blog_id'  => $blog_id,
					'post_id'  => $post_id,
					'video_id' => $video_id
				),
				array( '%d', '%d', '%d' ) );
			$record_id = $wpdb->insert_id;
		} else {
			$record_id = $record->id;
		}
		return $record_id;
	}

	/**
	 * Adds a video ID to the queue of videos whose information should be fetched.
	 *
	 * Once one or more videos are added to the queue, their information can
	 * be retrieved using the `_retrieve_queued_video_info` method.
	 *
	 * @param int $youtube_id the ID of the YouTube video
	 *
	 * @access private
	 * @since 0.4
	 */
	private function _add_video_to_info_queue( $youtube_id )
	{

		// Get the existing queue of videos
		$queue = $this->get_option( 'info_queue' );
		if ( empty( $queue ) ) {
			$queue = array();
		}

		// Update the queue if the video is not already present in it
		if ( ! in_array( $youtube_id, $queue ) ) {
			$queue[] = $youtube_id;
			$this->update_option( 'info_queue', $queue );
		}
	}

	/**
	 * Gets information about all queued YouTube videos and applies it to the
	 * stored videos in the local playlist.
	 *
	 * After fetching the video info, the queue of videos is cleared.
	 *
	 * @access private
	 * @since 0.4
	 */
	private function _retrieve_queued_video_info()
	{

		// Abort if we have no videos in the queue
		global $wpdb;
		$queue = $this->get_option( 'info_queue' );
		if ( empty( $queue ) ) {
			return;
		}

		// Request information on all videos in the queue using a single connection
		$conn = new ClassBlogs_Http( self::_YOUTUBE_VIDEO_INFO_URL_TEMPLATE );
		$queue_length = count( $queue ) - 1;
		for ( $i=0; $i <= $queue_length; $i++ ) {
			$request = sprintf( self::_YOUTUBE_VIDEO_INFO_URL_TEMPLATE, $queue[$i] );
			$conn->add_request_line( "GET $request HTTP/1.1\r\n" );
			if ( $i != $queue_length ) {
				$conn->add_request_line( "\r\n" );
			}
		}
		$responses = $conn->get_responses();
		$conn->close_connection();

		// Update each video in the local playlist with the information found
		// in the YouTube API response
		foreach ( $responses as $response ) {
			$xml = ClassBlogs_Utils::xml_from_string( $response->body );
			$title = ClassBlogs_Utils::get_single_xml_tag_value( $xml, 'title' );
			$youtube_id = ClassBlogs_Utils::get_single_xml_tag_value( $xml, 'videoid', self::_YT_NAMESPACE );
			if ( $title && $youtube_id ) {
				$wpdb->query( $wpdb->prepare( "
					UPDATE {$this->tables->videos}
					SET title = %s
					WHERE youtube_id = %s",
					$title, $youtube_id ) );
			}
		}

		// Clear the queue
		$this->update_option( 'info_queue', array() );
	}

	/**
	 * Removes the local record of the YouTube video being used by the given post.
	 *
	 * @param  int $youtube_id the YouTube ID of an embedded YouTube video
	 * @param  int $post_id    the ID of the post no longer using the video
	 * @param  int $blog_id    the ID of the blog on which the post was made
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _remove_video_usage( $youtube_id, $post_id, $blog_id )
	{
		global $wpdb;

		// Get the embedded video's internal ID
		$video_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT id FROM {$this->tables->videos}
			WHERE youtube_id = %s",
			$youtube_id ) );

		// Remove the video usage record
		$wpdb->query( $wpdb->prepare( "
			DELETE FROM {$this->tables->video_usage}
			WHERE blog_id = %d AND post_id = %d AND video_id = %d",
			$blog_id, $post_id, $video_id ) );

		// If the removed video is no longer used by any posts, remove its
		// record from the database
		$uses = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) FROM {$this->tables->video_usage}
			WHERE video_id = %d",
			$video_id ) );
		if ( ! $uses ) {
			$wpdb->query( $wpdb->prepare( "
				DELETE FROM {$this->tables->videos}
				WHERE id = %d",
				$video_id ) );
		}
	}

	/**
	 * Checks whether or not the given ID appears to be a valid YouTube video ID.
	 *
	 * A valid ID will be any string made up of 11 characters chosen from the
	 * set [A-Za-z0-9_-].  This is not guaranteed to be a valid video ID, but
	 * it does fit the proper format for a video ID.
	 *
	 * @param  string $id a possible YouTube video ID
	 * @return bool       whether the ID appears to be valid
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _validate_video_ids( $id )
	{
		return preg_match( '!^[A-Za-z0-9-_]{' . self::_YOUTUBE_VIDEO_ID_LENGTH . '}$!', $id );
	}

	/**
	 * Searches for embedded YouTube video IDs in the post's content.
	 *
	 * This cycles through the list of video-ID search functions defined by
	 * this plugin and condenses each one's results into a final ID list.
	 *
	 * @param  string $content the plaintext content of a post
	 * @return array           a list of found YouTube video IDs
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _find_video_ids_in_post_content( $content )
	{

		// If the content is blank, return an empty array
		if ( empty( $content ) ) {
			return array();
		}

		// Find any embedded video IDs, removing duplicates or blanks
		$videos = array();
		foreach ( self::$_video_searchers as $search_function ) {
			$videos = array_merge(
				$videos,
				call_user_func( array( $this, $search_function ), $content ) );
		}
		$ids = array();
		foreach ( array_unique( array_filter( $videos, array( $this, '_validate_video_ids' ) ) ) as $video ) {
			$ids[] = $video;
		}
		return $ids;
	}

	/**
	 * Searches for YouTube videos by looking for any URLs pointing to YouTube
	 * and checking them for a valid video ID.
	 *
	 * @param  string $text the plaintext content of a post
	 * @return array        a list of YouTube video IDs used in the post
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _find_videos_by_url( $text )
	{

		// Assemble a list of all YouTube URLs in the post content
		$urls = array();
		preg_match_all( '!https?://www\.youtube\.com/[^\s\'"]+!', $text, $url_matches );
		if ( ! empty( $url_matches ) ) {
			foreach ( $url_matches[0] as $match ) {
				$urls[] = $match;
			}
		}

		// Return any YouTube embed URLs
		$videos = array();
		foreach ( $urls as $url ) {
			$videos[] = $this->_get_video_id_from_url( $url );
		}
		return $videos;
	}

	/**
	 * Returns a YouTube video from a URL that may reference an embedded video.
	 *
	 * @param  string $url a URL that might reference an embedded YouTube video
	 * @return string      a YouTube video ID, or a blank string
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_video_id_from_url( $url )
	{
		// Since a URL with a querystring might have escaped ampersands, we
		// want to undo that before proceeding
		$url = htmlspecialchars_decode( $url );
		$base_id = "";

		// Search for a URL using the direct link to the video page
		preg_match( '!https?://www\.youtube\.com/watch\?(.*)!', $url, $matches );
		if ( ! empty( $matches ) ) {
			parse_str( $matches[1], $query );
			if ( array_key_exists( 'v', $query ) ) {
				$base_id = $query['v'];
			}
		}

		// Search for a URL using the old and new embed URL formats
		preg_match( '!https?://www\.youtube\.com/(v|embed)/([^\&\?]+)!', $url, $matches );
		if ( count( $matches ) == 3 ) {
			$base_id = $matches[2];
		}

		// If the first 11 characters of the possible ID are within the set
		// of acceptable YouTube video ID characters, return these 11 characters
		// as our video ID.  Otherwise, return a blank string
		preg_match( '!^[A-Za-z0-9_-]{' . self::_YOUTUBE_VIDEO_ID_LENGTH . '}!', $base_id, $matches );
		if ( ! empty( $matches ) ) {
			return $matches[0];
		} else {
			return "";
		}
	}

	/**
	 * Gets information on all videos that are part of the class's YouTube class.
	 *
	 * The returned array is in the same order as the actual YouTube playlist.
	 * Each entry in the array will be an object with the following properties:
	 *
	 *     date_added - the date on which the video was added to the playlist
	 *     link       - the URL to the video's YouTube page
	 *     thumbnail  - the URL of the large thumbnail
	 *     title      - the name of the video
	 *     used_by    - an array of post- and blog-ID pairs indicating which
	 *                     posts have embedded the video
	 *     youtube_id - the YouTube ID for the video
	 *
	 * @return array a list of information about the class playlist videos
	 *
	 * @since 0.1
	 */
	public function get_playlist_videos()
	{
		global $wpdb;
		$playlist = array();

		// Return early if we have a cached playlist
		$cached = $this->get_site_cache( 'playlist' );
		if ( $cached !== null ) {
			return $cached;
		}

		// Build the playlist from the videos and video usage tables
		$all_videos = $wpdb->get_results( $wpdb->prepare( "
			SELECT id, youtube_id, title, date_added
			FROM {$this->tables->videos}
			ORDER BY date_added DESC" ) );
		foreach ( $all_videos as $video ) {
			$entry = array(
				'date_added' => $video->date_added,
				'link'       => sprintf( self::_YOUTUBE_VIDEO_PAGE_URL_TEMPLATE, $video->youtube_id ),
				'thumbnail'  => $this->_get_large_thumbnail_url( $video->youtube_id ),
				'title'      => $video->title,
				'youtube_id' => $video->youtube_id
			);
			$entry['used_by'] = $wpdb->get_results( $wpdb->prepare( "
				SELECT vu.blog_id, vu.post_id
				FROM {$this->tables->videos} AS v, {$this->tables->video_usage} AS vu
				WHERE v.youtube_id = %s AND vu.video_id = v.id",
				$video->youtube_id ) );
			$playlist[] = (object) $entry;
		}

		// Cache the full playlist
		$this->set_site_cache( 'playlist', $playlist, self::_PLAYLIST_CACHE_LENGTH );
		return $playlist;
	}

	/**
	 * Returns a list of recently added videos for use in the widget.
	 *
	 * @param  int   $limit the optional maximum number of videos to return
	 * @return array        a list of recently added videos
	 *
	 * @since 0.2
	 */
	public function get_recent_videos_for_widget( $limit = 5 )
	{
		$playlist = $this->get_playlist_videos();
		if ( $limit <= count( $playlist ) ) {
			return array_slice( $playlist, 0, $limit );
		} else {
			return $playlist;
		}
	}

	/**
	 * Returns the URL for viewing the local class playlist page.
	 *
	 * @return string the URL of the local class playlist page
	 *
	 * @since 0.1
	 */
	public function get_local_playlist_page_url()
	{
		return get_page_link( $this->get_option( 'playlist_page_id' ) );
	}

	/**
	 * Update the tables whenever an upgrade is needed.
	 *
	 * @since 0.3
	 */
	public function upgrade( $old, $new ) {
		$this->_create_tables();
	}
}

ClassBlogs::register_plugin(
	'youtube_class_playlist',
	'ClassBlogs_Plugins_YouTubeClassPlaylist',
	__( 'YouTube Class Playlist', 'classblogs' ),
	__( 'Maintains a playlist of all YouTube videos embedded in student posts.', 'classblogs' )
);

?>
