<?php

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
class _ClassBlogs_Plugins_YouTubeClassPlaylistWidget extends ClassBlogs_Plugins_SidebarWidget
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
	 * Creates the class playlist widget.
	 */
	public function __construct()
	{
		parent::__construct(
			__( 'YouTube Class Playlist', 'classblogs' ),
			__( 'A list of YouTube videos that have been recently added to the class playlist', 'classblogs' ),
			'cb-youtube-class-playlist' );
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

		$recent_videos = $plugin->get_recent_videos_for_sidebar( $instance['limit'] );
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
				<a href="<?php echo esc_url( $plugin->get_local_playlist_page_url() ); ?>"><?php _e( 'View videos used on blogs', 'classblogs' ); ?></a>
			</li>
			<li class="cb-youtube-remote-playlist-link">
				<a href="<?php echo esc_url( $plugin->get_youtube_playlist_page_url() ); ?>" rel="external"><?php _e( 'View playlist on YouTube', 'classblogs' ); ?></a>
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
 * In order for a playlist to be associated with a blog, a professor must go
 * to the admin page for this plugin under the class-blogs admin menu group and
 * click on the link to authorize their account.  Once they log in to YouTube and
 * grant this plugin access to their account, they can select a playlist.
 *
 * This plugin also provides a simple interface to get information about the
 * YouTube class playlist, which is demonstrated below:
 *
 *     // A post using 2 embedded YouTube videos is created, and the remote
 *     // playlist is synced with the local one.
 *     $plugin = ClassBlogs::get_plugin( 'youtube_class_playlist' );
 *
 *     assert( count( $plugin->get_playlist_videos() ) === 1 );
 *
 *     echo "The YouTube playlist can be viewed on YouTube at " . $plugin->get_youtube_playlist_page_url() . "\n";
 *     echo "The local playlist page be viewed at " . $plugin->get_local_playlist_page_url() . "\n";
 *
 * @package ClassBlogs_Plugins
 * @subpackage YouTubeClassPlaylist
 * @since 0.1
 */
class ClassBlogs_Plugins_YouTubeClassPlaylist extends ClassBlogs_Plugins_BasePlugin
{

	/**
	 * The developer key used to allow this plugin API access.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _GDATA_API_KEY = "AI39si5pRSIGylGT-Bh-BOJZ3LTU6QyWHw3D6mj3LE4fZDViHMGqYIEWEVzPf88owlcjD4A-JVu7IYOiF_kEF0ZuGzF6A4vCfw";

	/**
	 * The base URL for any Google data requests to YouTube.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _GDATA_REQUEST_BASE = 'https://gdata.youtube.com';

	/**
	 * The base URL for viewing a YouTube playlist created by a user.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _YOUTUBE_PLAYLIST_PAGE_TEMPLATE = 'http://www.youtube.com/playlist?p=%s';

	/**
	* The URL for viewing a full-size thumbnail of a YouTube video.
	*
	* @access private
	* @var string
	* @since 0.1
	*/
	const _YOUTUBE_FULL_SIZE_THUMBNAIL_URL_TEMPLATE = 'http://img.youtube.com/vi/%s/0.jpg';

	/**
	 * The base URL for any playlist API requests.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _PLAYLIST_API_BASE = '/feeds/api/playlists/';

	/**
	 * The URL from which a user's information can be obtained.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _USER_INFO_URL = '/feeds/api/users/default';

	/**
	 * The base URL for any user playlist API requests.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _USER_PLAYLISTS_URL = '/feeds/api/users/default/playlists?v=2';

	/**
	 * The expected length of a YouTube video ID.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _YOUTUBE_VIDEO_ID_LENGTH = 11;

	/**
	 * The maximum number of results returned by a YouTube API call.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _YOUTUBE_API_MAX_RESULTS = 50;

	/**
	 * The maximum number of entries that can be in a playlist.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _PLAYLIST_MAX_ENTRIES = 200;

	/**
	 * The version of the GData API used by this plugin.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _GDATA_API_VERSION = 2;

	/**
	 * The base URL for any Google accounts requests.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _GOOGLE_ACCOUNTS_BASE_URL = 'https://www.google.com';

	/**
	 * The relative URL at which an OAuth request token can be obtained.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_GET_REQUEST_TOKEN_URL = '/accounts/OAuthGetRequestToken';

	/**
	 * The relative URL at which an OAuth authorization token can be authorized.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_AUTHORIZE_TOKEN_URL = '/accounts/OAuthAuthorizeToken';

	/**
	 * The relative URL at which an OAuth access token can be obtained.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_GET_ACCESS_TOKEN_URL = '/accounts/OAuthGetAccessToken';

	/**
	 * The version of OAuth in use.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_VERSION = '1.0';

	/**
	 * The consumer key used to identify the plugin through OAuth.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_CONSUMER_KEY = 'anonymous';

	/**
	 * The secret key used to generate the OAuth signature.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _OAUTH_CONSUMER_SECRET = 'anonymous';

	/**
	 * The scope of any AuthSub requests made by this plugin.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _GDATA_SCOPE = 'https://gdata.youtube.com';

	/**
	 * The number of seconds that counts as a short timeout.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _SHORT_TIMEOUT = 7;

	/**
	 * The number of seconds that counts as a long timeout.
	 *
	 * @access private
	 * @var int
	 * @since 0.1
	 */
	const _LONG_TIMEOUT = 20;

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
	 * A template for the body payload used to add a video to a playlist.
	 *
	 * @access private
	 * @var string
	 * @since 0.1
	 */
	const _ADD_VIDEO_PAYLOAD_TEMPLATE = '<?xml version="1.0" encoding="UTF-8"?>
		<entry xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://gdata.youtube.com/schemas/2007">
		<id>%s</id>
		</entry>';

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
		'access_token'         => "",
		'access_token_secret'  => "",
		'account_linked'       => false,
		'playlist_full'        => false,
		'playlist_page_id'     => null,
		'request_token'        => "",
		'request_token_secret' => "",
		'tables_created'       => false,
		'youtube_playlist'     => "",
		'youtube_user_id'      => ""
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
				array( 'id',                'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'youtube_id',        'varchar(11) NOT NULL' ),
				array( 'playlist_entry_id', 'varchar(32)' ),
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
				array( 'id',       'bigint(20) unsigned NOT NULL AUTO_INCREMENT' ),
				array( 'blog_id',  'bigint(20) unsigned NOT NULL' ),
				array( 'post_id',  'bigint(20) unsigned NOT NULL' ),
				array( 'video_id', 'bigint(20) unsigned NOT NULL' ),
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
	 * @var object
	 * @since 0.1
	 */
	public $tables;

	/**
	 * Registers the necessary WordPress hooks to make the playlist work.
	 */
	public function __construct() {

		parent::__construct();

		// Perform initialization and sanity checks
		$this->tables = $this->_make_table_names();
		if ( ! $this->get_option( 'tables_created' ) ) {
			$this->_create_tables();
		}

		add_action( 'init', array( $this, '_ensure_playlist_page_is_created' ) );

		// If we have an active account and playlist, register hooks for finding
		// videos in post content and for showing the playlist archive page
		if ( $this->_playlist_is_valid() ) {
			add_action( 'deleted_post',  array( $this, '_update_videos_on_post_delete' ) );
			add_action( 'pre_get_posts', array( $this, '_maybe_enable_playlist_page' ) );
			add_action( 'save_post',     array( $this, '_update_videos_on_post_save' ) );
			add_action( 'widgets_init',  array( $this, '_enable_widget' ) );
		}
	}

	/**
	 * Returns true if the user has associated a valid YouTube account and a
	 * playlist with the current class blog.
	 *
	 * @return bool whether or not the plugin can be used
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _playlist_is_valid()
	{
		return $this->get_option( 'account_linked' ) && $this->get_option( 'youtube_playlist' );
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
	private function _create_tables() {

		// Create each table from its schema
		$table_specs = array(
			array( $this->tables->videos, $this->_get_videos_schema() ),
			array( $this->tables->video_usage, $this->_get_video_usage_schema() )
		);
		foreach ( $table_specs as $spec ) {
			$spec[1]->apply_to_table( $spec[0] );
		}

		// Flag that the tables have been created
		$this->update_option( 'tables_created', true );
	}

	/**
	 * Ensures that the page used for showing the list of all videos in the
	 * playlist and their per-blog usage exists.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _ensure_playlist_page_is_created()
	{
		if ( ClassBlogs_Utils::is_root_blog() ) {
			$current_page = $this->get_option( 'playlist_page_id' );
			$page_id = $this->create_plugin_page( self::_PLAYLIST_PAGE_DEFAULT_NAME, $current_page );
			if ( $page_id != $current_page ) {
				$this->update_option( 'playlist_page_id', $page_id );
			}
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
		$this->register_root_only_widget( '_ClassBlogs_Plugins_YouTubeClassPlaylistWidget' );
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
		if ( ClassBlogs::is_page( $this->get_option( 'playlist_page_id' ) ) ) {
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
			ClassBlogs_Utils::get_plugin_js_url() . 'youtube-class-playlist.js',
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
			$markup .= '<div class="cb-youtube-local-playlist-page-video-thumbnail" id="video__' . esc_attr( $video->video_id ) . '">';
			$markup .= sprintf( '<a href="%1$s"><img src="%2$s" title="%3$s" alt="%3$s" /></a>',
				esc_url( $video->link ),
				esc_url( $this->_get_large_thumbnail_url( $video->video_id ) ),
				esc_attr( $video->title ) );
			$markup .= '</div>';

			// Add metadata for the video
			$markup .= '<p class="cb-youtube-local-playlist-page-meta">' . sprintf( __( 'Added to the playlist on %s', 'classblogs' ), '<span class="cb-youtube-local-playlist-page-date">' . esc_html( $video->published ) . '</span>' ) . '</p>';
			if ( ! empty( $video->used_by ) ) {
				$markup .= '<p class="cb-youtube-local-playlist-page-usage">' . __( 'Embedded in', 'classblogs' ) . ' ';
				$links = array();
				foreach ( $video->used_by as $usage ) {
					$link = '<a class="cb-youtube-local-playlist-page-usage-post" ';
					switch_to_blog( $usage->blog_id );
					$link .= sprintf( ' href="%s">%s</a>',
						esc_url( get_permalink( $usage->post_id ) ),
						get_post( $usage->post_id )->post_title );
					restore_current_blog();
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
		$this->clear_cache( 'playlist' );
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

		// Extract YouTube video IDs from the post and determine which videos
		// have yet to be added to the playlist
		$playlist_videos = $wpdb->get_col( "SELECT youtube_id FROM {$this->tables->videos}" );
		$current_videos = $this->_find_video_ids_in_post_content( $post->post_content );
		$unadded_videos = array_values( array_diff( $current_videos, $playlist_videos ) );

		// Determine which videos were previously used in the post content but
		// are no longer present
		$previous_videos = $wpdb->get_col( $wpdb->prepare( "
			SELECT v.youtube_id FROM {$this->tables->video_usage} AS vu, {$this->tables->videos} AS v
			WHERE vu.blog_id = %d AND vu.post_id = %d AND vu.video_id = v.id",
			$blog_id, $post_id ) );
		$unused_videos = array_values( array_diff( $previous_videos, $current_videos ) );

		// Update our local video usage records
		foreach ( $unadded_videos as $video ) {
			$this->_add_video_usage( $video, $post_id, $blog_id );
		}
		foreach ( $unused_videos as $video ) {
			$this->_remove_video_usage( $video, $post_id, $blog_id );
		}

		// Update the remote YouTube playlist
		$this->_sync_youtube_playlist();
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

		// Sync the YouTube playlist
		$this->_sync_youtube_playlist();
	}

	/**
	 * Adds a local record of the YouTube video being used by the given post.
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
		// can be found
		$video_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT id FROM {$this->tables->videos}
			WHERE youtube_id = %s",
			$youtube_id ) );
		if ( ! $video_id ) {
			$wpdb->insert(
				$this->tables->videos,
				array( 'youtube_id' => $youtube_id ),
				array( '%s' ) );
			$video_id = $wpdb->insert_id;
		}

		// Add a new video usage record
		$wpdb->insert(
			$this->tables->video_usage,
			array(
				'blog_id'  => $blog_id,
				'post_id'  => $post_id,
				'video_id' => $video_id
			),
			array( '%d', '%d', '%d' ) );
		return $wpdb->insert_id;
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
	 * Custom diff function for comparing playlist arrays.
	 *
	 * @param  array $a the base array
	 * @param  array $b the array to compare against
	 * @return array    a list of all videos in $a but not $b
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _make_playlist_diff( $a, $b )
	{
		$diff = array();
		foreach ( $a as $a_val ) {
			$found = false;
			foreach ( $b as $b_val ) {
				if ( $a_val == $b_val ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$diff[] = $a_val;
			}
		}
		return $diff;
	}

	/**
	 * Syncs the remote YouTube playlist with the local playlist.
	 *
	 * This calculates the differences between the local record of video usage,
	 * which is viewed as authoritative, and the remote YouTube playlist, which
	 * is viewed as needing to be synced with the local copy.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _sync_youtube_playlist()
	{
		global $wpdb;
		$playlist_url = self::_PLAYLIST_API_BASE . $this->get_option( 'youtube_playlist' );

		// If the local playlist exceeds the allowed number of playlist entries
		// and we have already flagged that the remote YouTube playlist is full,
		// abort early, as we won't be able to add any videos
		$local_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->tables->videos}" ) );
		if ( $this->get_option( 'playlist_full' ) && $local_count >= self::_PLAYLIST_MAX_ENTRIES ) {
			return;
		}

		// Create a diff of the local and remote playlists
		$remote_videos = array();
		foreach ( $this->get_playlist_videos() as $video ) {
			$remote_videos[] = array(
				'playlist_entry_id' => $video->playlist_id,
				'youtube_id'        => $video->video_id );
		}
		$local_videos = $wpdb->get_results( $wpdb->prepare( "
			SELECT youtube_id, playlist_entry_id
			FROM {$this->tables->videos} " ),
			ARRAY_A );

		// Add unadded videos to the YouTube playlist
		foreach ( $this->_make_playlist_diff( $local_videos, $remote_videos ) as $video ) {
			$response = $this->_make_gdata_request(
				$playlist_url,
				'POST',
				sprintf( self::_ADD_VIDEO_PAYLOAD_TEMPLATE, $video['youtube_id'] ),
				false );

			switch ( $response->status ) {

				// If the request was successful, get the ID of the new playlist
				// entry from the returned Location header
				case 201:
					preg_match( '!/([^/]+)$!', $response->headers['Location'], $matches );
					$wpdb->update(
						$this->tables->videos,
						array( 'playlist_entry_id' => $matches[1] ),
						array( 'youtube_id' => $video['youtube_id'] ) );
					break;

				// If the request returned a 400 bad request code, we're likely
				// trying to add a nonexistent YouTube video, so we should remove
				// any record of it from our local playlist
				case 400:
					$wpdb->query( $wpdb->prepare( "
						DELETE FROM {$this->tables->video_usage} AS vu, {$this->tables->videos} AS v
						WHERE v.youtube_id=%s AND vu.video_id=v.id",
						$video['youtube_id'] ) );
					$wpdb->query( $wpdb->prepare(
						"DELETE FROM {$this->tables->videos} WHERE youtube_id=%s",
						$video['youtube_id'] ) );
					break;

				// If the request returned a 403 forbidden code, don't add any more
				// videos, as we've likely exceeded a recent-request quota
				case 403:
					break 2;
			}
		}

		// Remove unused videos from the playlist
		foreach ( $this->_make_playlist_diff( $remote_videos, $local_videos ) as $video ) {
			$this->_make_gdata_request(
				$playlist_url . '/' . $video['playlist_entry_id'],
				'DELETE' );
		}

		$this->_clear_cached_playlist();
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
	 * Makes a GData request and returns its body content as an XML document.
	 *
	 * @param  string $url     the relative YouTube GData URL to request
	 * @param  string $method  an optional string specifying the HTTP method to use
	 * @param  string $payload an optional string to use for the body payload
	 * @param  bool   $ax_xml  a flag for returning the document as XML or a response
	 * @return object          the XML DOMDocument instance of the body content
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _make_gdata_request( $url, $method = 'GET', $payload = "", $as_xml = true )
	{

		// Build the parameters using the access token and secret key
		$params = array(
			'oauth_token' => $this->get_option( 'access_token' )
		);
		$this->_add_common_oauth_params( $params );
		$this->_add_oauth_signature_params( self::_GDATA_REQUEST_BASE . $url, $params, $method, $this->get_option( 'access_token_secret' ) );

		// Make the request
		$url_parts = parse_url( self::_GDATA_REQUEST_BASE );
		$conn = $this->_connect_to_server( self::_GDATA_REQUEST_BASE, self::_SHORT_TIMEOUT );
		fputs( $conn, "$method $url HTTP/1.1\r\n" );
		fputs( $conn, "Host: " . $url_parts['host'] . "\r\n" );
		fputs( $conn, "Content-Type: application/atom+xml\r\n" );
		fputs( $conn, "GData-Version: 2.0\r\n" );
		fputs( $conn, "X-GData-Key: key=" . self::_GDATA_API_KEY . "\r\n" );
		if ( 'POST' == $method ) {
			fputs( $conn, "Content-Length: " . strlen( $payload ) . "\r\n" );
		}
		$this->_add_oauth_headers( $conn, $params );
		if ( $payload ) {
			fputs( $conn, "\r\n" );
			fputs( $conn, $payload );
		}

		// Return the response body as XML
		$this->_close_connection( $conn );
		if ( $as_xml ) {
			$response = $this->_response_as_xml( $conn );
		} else {
			$response = $this->_read_http_response( $conn );
		}
		fclose( $conn );
		return $response;
	}

	/**
	 * Makes a connection to the Google accounts servers.
	 *
	 * @return object a connection to a Google accounts server
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _make_google_accounts_connection()
	{
		return $this->_connect_to_server( self::_GOOGLE_ACCOUNTS_BASE_URL, self::_LONG_TIMEOUT );
	}

	/**
	 * A convenience function for making a connection to a remote server.
	 *
	 * @param  string $server  the URL of the remote server
	 * @param  int    $timeout the timeout in seconds to use when connecting
	 * @return object          the connection object
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _connect_to_server( $server, $timeout )
	{
		$port   = preg_match( '/^https:/', $server ) ? 443 : 80;
		$server = preg_replace( '/^http:\/\//', "", $server );
		$server = preg_replace( '/^https/', 'ssl', $server );

		return fsockopen( $server, $port, $errno, $errst, $timeout );
	}

	/**
	 * Closes a connection to a remote server.
	 *
	 * @param object $conn an open connection to a remote server
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _close_connection( &$conn )
	{
		fputs( $conn, "Connection: close\r\n\r\n" );
	}

	/**
	 * Gets information on the token and secret key for an OAuth request token.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_oauth_request_token()
	{
		$admin = ClassBlogs_Admin::get_admin();
		$url = self::_GOOGLE_ACCOUNTS_BASE_URL . self::_OAUTH_GET_REQUEST_TOKEN_URL;

		// Build the query params
		$params = array(
			'oauth_callback'     => $admin->get_admin_page_url( $this->get_uid() ),
			'scope'              => self::_GDATA_SCOPE,
			'xoauth_displayname' => 'YouTube Class Playlist'
		);
		$this->_add_common_oauth_params( $params );
		$this->_add_oauth_signature_params( $url, $params, 'GET' );
		$query_string = $this->_non_oauth_params_as_query_vars( $params );

		// Ask for the request token
		$conn = $this->_make_google_accounts_connection();
		fputs( $conn, "GET " . self::_OAUTH_GET_REQUEST_TOKEN_URL . $query_string . " HTTP/1.1\r\n" );
		fputs( $conn, "Host: www.google.com\r\n" );
		fputs( $conn, "Content-type: application/x-www-form-urlencoded\r\n" );
		$this->_add_oauth_headers( $conn, $params );
		$this->_close_connection( $conn );
		$response = $this->_read_http_response( $conn );
		fclose( $conn );

		// Read the token and secret key from the response
		if ( $response->body ) {
			parse_str( $response->body, $parts );
			$this->update_option( 'request_token', $parts['oauth_token'] );
			$this->update_option( 'request_token_secret', $parts['oauth_token_secret'] );
		}
	}

	/**
	 * Returns a link that allows the user to start the OAuth authentication process.
	 *
	 * @return string the OAuth authentication URL
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_oauth_signin_link()
	{
		return sprintf( '%s%s?oauth_token=%s',
			self::_GOOGLE_ACCOUNTS_BASE_URL,
			self::_OAUTH_AUTHORIZE_TOKEN_URL,
			$this->get_option( 'request_token' ) );
	}

	/**
	 * Adds any OAuth parameters to the connection's headers in-place.
	 *
	 * @param  object $conn   a connection to a server
	 * @param  array  $params a list of parameters
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _add_oauth_headers( &$conn, $params )
	{
		$oauth_params = array();
		foreach ( $params as $key => $value ) {
			if ( 0 === strpos( $key, 'oauth_' ) ) {
				$oauth_params[] = $key . '="' . $value . '"';
			}
		}
		fputs( $conn, "Authorization: OAuth " . implode( ',', $oauth_params ) . "\r\n" );
	}

	/**
	 * Returns a GET queryvar string of any non-OAuth params.
	 *
	 * @param  array $params query parameters for an HTTP request
	 * @return string        a GET query string
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _non_oauth_params_as_query_vars( $params )
	{
		$get_vars = array();
		foreach ( $params as $key => $value ) {
			if ( strpos( $key, 'oauth_' ) !== 0 ) {
				$get_vars[] = $key . '=' . rawurlencode( $value ) . '';
			}
		}
		if ( count( $get_vars ) ) {
			return '?' . implode( '&', $get_vars );
		} else {
			return "";
		}
	}

	/**
	 * Adds OAuth request params common to all OAuth requests in-place.
	 *
	 * @param array $params the current parameters to be sent
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _add_common_oauth_params( &$params )
	{
		$params['oauth_consumer_key'] = self::_OAUTH_CONSUMER_KEY;
		$params['oauth_nonce']        = md5( uniqid( rand(), true ) );
		$params['oauth_timestamp']    = time();
		$params['oauth_version']      = self::_OAUTH_VERSION;
	}

	/**
	 * Adds OAuth signature parameters in-place to the given list of parameters.
	 *
	 * @param string $url          the URL being requested
	 * @param array  $params       the current OAuth parameters
	 * @param string $method       the HTTP transfer method used
	 * @param string $token_secret the optional secret key of a token
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _add_oauth_signature_params( $url, &$params, $method, $token_secret = "" )
	{
		$params['oauth_signature_method'] = 'HMAC-SHA1';

		// Add any query parameters to the list of params used to make the signature
		$sig_params = $params;
		$query = parse_url( $url, PHP_URL_QUERY );
		if ( $query ) {
			foreach ( explode( '&', $query ) as $query_var ) {
				$query_parts = explode( '=', $query_var );
				$sig_params[$query_parts[0]] = $query_parts[1];
			}
		}

		// Normalize the parameters as per the OAuth spec
		ksort( $sig_params );
		$param_parts = array();
		foreach ( $sig_params as $key => $value ) {
			$param_parts[] = $key . '=' . rawurlencode( $value );
		}

		// Construct the signature base string and key according to the OAuth spec
		$url_parts = explode( '?', $url );
		$base_string_parts = array(
			$method,
			rawurlencode( $url_parts[0] ),
			rawurlencode( implode( '&', $param_parts ) )
		);
		$key_parts = array(
			rawurlencode( self::_OAUTH_CONSUMER_SECRET ),
			rawurlencode( $token_secret )
		);

		// Return the signature
		$signature = base64_encode( hash_hmac(
			'sha1',
			implode( '&', $base_string_parts ),
			implode( '&', $key_parts ),
			true ) );
		$params['oauth_signature'] = rawurlencode( $signature );
	}

	/**
	 * Gets an OAuth access token allowing access to the user's YouTube account.
	 *
	 * @param string $verifier the OAuth verification code
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_access_token( $verifier )
	{
		$url = self::_GOOGLE_ACCOUNTS_BASE_URL . self::_OAUTH_GET_ACCESS_TOKEN_URL;

		// Build the query params
		$params = array(
			'oauth_token'    => $this->get_option( 'request_token' ),
			'oauth_verifier' => $verifier
		);
		$this->_add_common_oauth_params( $params );
		$this->_add_oauth_signature_params( $url, $params, 'POST', $this->get_option( 'request_token_secret' ) );

		// Ask for the request token
		$conn = $this->_make_google_accounts_connection();
		fputs( $conn, "POST " . self::_OAUTH_GET_ACCESS_TOKEN_URL . " HTTP/1.1\r\n" );
		fputs( $conn, "Host: www.google.com\r\n" );
		fputs( $conn, "Content-type: application/x-www-form-urlencoded\r\n" );
		fputs( $conn, "Content-length: 0\r\n" );
		$this->_add_oauth_headers( $conn, $params );
		$this->_close_connection( $conn );
		$response = $this->_read_http_response( $conn );
		fclose( $conn );

		// Read the token and secret key from the response
		if ( $response->body && $response->status == 200 ) {
			parse_str( $response->body, $parts );
			$this->update_option( 'access_token', $parts['oauth_token'] );
			$this->update_option( 'access_token_secret', $parts['oauth_token_secret'] );
		}
	}

	/**
	 * Links the user's account if they have yet to be linked but have a valid token.
	 *
	 * This makes a request to YouTube with the token that the user has.  If a
	 * valid response is received, the user is marked as having a linked account,
	 * and their YouTube user ID is fetched and cached.
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _maybe_link_account()
	{
		$token = $this->get_option( 'access_token' );
		if ( ! $this->get_option( 'account_linked' ) && $token ) {
			$response = $this->_make_gdata_request( self::_USER_INFO_URL );
			if ( $response ) {
				$user_id = $response->getElementsByTagNameNS(
					$this->_get_namespace( $response, 'yt', 'entry' ),
					'username' )->item(0)->nodeValue;

				// If we were able to obtain a YouTube user ID, store this, mark
				// the account as linked and clear the request tokens
				if ( $user_id ) {
					$options = $this->get_options();
					$options['youtube_user_id'] = $user_id;
					$options['account_linked'] = true;
					unset( $options['request_token'] );
					unset( $options['request_token_secret'] );
					$this->update_options( $options );
				}
			}
		}
	}

	/**
	 * Returns a list of all playlists associated with the current user's account.
	 *
	 * The returned array of playlists will be sorted alphabetically by the
	 * playlist title, and each element will contain an object with the
	 * following properties:
	 *
	 *     id   - the YouTube ID of the playlist
	 *     name - the name of the playlist
	 *
	 * @return array a list of all the user's playlists
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_user_playlists()
	{

		$playlists = array();

		// Build an array of playlists from the response to our API query
		$response = $this->_make_gdata_request( self::_USER_PLAYLISTS_URL );
		if ( $response ) {
			foreach ( $response->getElementsByTagName( 'entry' ) as $playlist ) {
				preg_match( '/:([^:]+)$/', $this->_get_single_tag_value( $playlist, 'id' ), $id_matches );
				$playlists[$this->_get_single_tag_value( $playlist, 'title' )] = array(
					'id' => $id_matches[1] );
			}
		}

		// Sort the playlists by playlist name and objectify each playlist
		$playlist_objs = array();
		if ( ! empty( $playlists ) ) {
			ksort( $playlists );
			foreach ( $playlists as $name => $info ) {
				$playlist_objs[] = (object) array(
					'id'   => $info['id'],
					'name' => $name );
			}
		}

		return $playlist_objs;
	}

	/**
	 * Reads the response made given to an HTTP connection.
	 *
	 * The returned response will be an object with the following properties:
	 *
	 *     body    - a string of the body content
	 *     headers - an array of key-value pairs of the headers
	 *     status  - an int of the returned HTTP status code
	 *
	 * @param  object $conn a connection that has received a response
	 * @return object       an object describing the response
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _read_http_response( &$conn )
	{
		$chunk_size  = 0;
		$eol_size    = strlen( "\r\n" );
		$is_chunked  = false;
		$new_chunk   = "";
		$past_header = false;
		$response    = "";

		while( !feof( $conn ) ) {
			$line = fgets( $conn, 4096 );
			$add = "";

			//  If using a chunked transfer encoding, add each chunk to the response
			//  as it's read.  Otherwise, just add the body string.
			if ( $past_header ) {
				if ( $is_chunked ) {
					if ( ! $chunk_size || strlen( $new_chunk ) == $chunk_size + $eol_size ) {
						$chunk_size = hexdec( trim( $line ) );
						if ( $new_chunk ) { $response .= preg_replace( '/\\r\\n$/', "", $new_chunk ); }
						$new_chunk = "";
					} else {
						$new_chunk .= $line;
					}
				} else {
					$response .= $line;
				}
			}
			else { $response .= $line; }

			//  Detect whether or not we're using chunked encoding
			if ( ! $past_header && $line == "\r\n" ) {
				$past_header = true;
				$is_chunked = preg_match( '/Transfer-Encoding:\s+chunked/', $response );
			}
		}

		// Parse the parts of the response
		$return = array();
		$parts = explode( "\r\n\r\n", $response, 2 );
		$headers = explode( "\r\n", $parts[0] );
		$return['body'] = $parts[1];

		$status = array_shift( $headers );
		preg_match( '/\s+(\d+)\s+/', $status, $status_matches );
		$return['status'] = $status_matches[1];

		$return['headers'] = array();
		foreach ( $headers as $header ) {
			$header_parts = explode( ':', $header, 2 );
			$return['headers'][$header_parts[0]] = $header_parts[1];
		}

		return (object) $return;
	}

	/**
	 * Interprets an HTTP response as an XML document.
	 *
	 * @param  object $conn a connection that has received a response
	 * @return object       a DOMDocument instance of the response
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _response_as_xml( &$conn )
	{

		$response = $this->_read_http_response( $conn );

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		if ( $response->body ) {
			$dom->loadXML( $response->body );
		}
		libxml_use_internal_errors( false );

		return $dom;
	}

	/**
	 * Configures the admin interface for the plugin.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$admin->add_admin_page( $this->get_uid(), __( 'YouTube Class Playlist', 'classblogs' ), array( $this, '_admin_page' ) );
	}

	/**
	 * Handles the admin page for the plugin.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _admin_page()
	{

		// If we have been redirected back to this page after allowing access
		// to YouTube, use the OAuth token to get an access token
		if ( isset( $_GET['oauth_token'] ) && isset( $_GET['oauth_verifier'] ) ) {
			$this->_get_access_token( $_GET['oauth_verifier'] );
			if ( $this->get_option( 'access_token' ) ) {
				$this->_maybe_link_account();
				if ( $this->get_option( 'account_linked' ) ) {
					$message = sprintf(
						__( "You have successfully linked your YouTube account (%s) with this blog!  You may now select a playlist to which all posted videos will be added.", 'classblogs' ),
						esc_html( $this->get_option( 'youtube_user_id' ) ) );
				} else {
					$message = __( "Your YouTube account was not able to be linked to this blog!", 'classblogs' );
				}
				ClassBlogs_Admin::show_admin_message( $message );
			}
		}

		if ( $_POST ) {
			check_admin_referer( $this->get_uid() );

			// Update the playlist if one was selected, making sure to clear the cache
			if ( array_key_exists( 'submit', $_POST ) ) {
				$playlist = ClassBlogs_Utils::sanitize_user_input( $_POST['playlist_id'] );
				if ( $playlist != $this->get_option( 'youtube_playlist' ) ) {
					$this->_clear_cached_playlist();
				}
				$this->update_option( 'youtube_playlist', $playlist );
				ClassBlogs_Admin::show_admin_message( __( 'Your YouTube class playlist settings have been updated.', 'classblogs' ) );
			}

			// Unlink the account if the user has chosen to do so
			elseif ( array_key_exists( 'unlink_account', $_POST ) ) {
				$options = $this->get_options();
				unset( $options['youtube_playlist'] );
				unset( $options['youtube_token'] );
				unset( $options['youtube_user_id'] );
				$options['account_linked'] = false;
				$this->update_options( $options );
				$this->_clear_cached_playlist();
				ClassBlogs_Admin::show_admin_message( __( 'Your YouTube account has been unlinked from this blog.', 'classblogs' ) );
			}
		}

		// If the playlist has reached its maximum number of entries, display
		// a message explaining the problem
		if ( $this->get_option( 'playlist_full' ) ) {
			ClassBlogs_Admin::show_admin_error(
				sprintf(
					__( 'Your playlist has reached the maximum number of entries allowed by YouTube (%1$d).  No new videos can be added to this playlist.', 'classblogs' ),
					self::_PLAYLIST_MAX_ENTRIES ) );
		}
	?>
		<div class="wrap">

			<h2><?php _e( 'YouTube Class Playlist Configuration', 'classblogs' ); ?></h2>

			<p>
				<?php _e( "This plugin lets you define a YouTube playlist to which any videos posted on a student's blog will be added.  To use it, you must sign in to your YouTube account.", 'classblogs' ); ?>
			</p>

			<form method="post" action="">

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'YouTube Account', 'classblogs' ); ?></th>
						<td>
							<?php

							// If their account is linked, show them a message
							// informing them of this
							if ( $this->get_option( 'account_linked' ) ) {
								printf( '<p style="color: #0c0; font-weight: bold;">%s</p>',
									sprintf( __( 'Your YouTube account (%s) has been linked with this blog', 'classblogs' ),
										esc_html( $this->get_option( 'youtube_user_id' ) ) ) );
							}

							// If it's not linked, show a message conveying this
							// and provide a link to the YouTube linkage page, unless
							// we couldn't get a valid request token
							else {
								$this->_get_oauth_request_token();
								if ( $this->get_option( 'request_token' ) ) {
									printf( '<p style="color: #c00; font-weight: bold;">%s</p><p><a href="%s">%s</a></p>',
										__( 'You have not associated a YouTube account with this blog.', 'classblogs' ),
										esc_url( $this->_get_oauth_signin_link() ),
										__( 'Click here to sign in to your YouTube account and link it with this blog.', 'classblogs' ) );
								} else {
									printf( '<p style="color: #c00; font-weight: bold;">%s</p>',
										__( 'The YouTube authorization process cannot be started.  Try refreshing your browser.', 'classblogs' ) );
								}
							}
							?>
						</td>
					</tr>

					<?php
						if ( $this->get_option( 'account_linked' ) ) {
							$playlists = $this->_get_user_playlists();
							if ( $playlists ) {
					?>
								<tr valign="top">
									<th scope="row"><?php _e( 'Playlist', 'classblogs' ); ?></th>
									<td>
										<label for="playlist-id"><?php _e( 'The playlist to which videos will be added', 'classblogs' ); ?></label><br />
										<select id="playlist-id" name="playlist_id">
											<option value="">-----------------------</option>
											<?php
												foreach ( $playlists as $playlist ) {
													printf( '<option value="%s" %s>%s</option>',
														esc_attr( $playlist->id ),
														( $playlist->id == $this->get_option( 'youtube_playlist' ) ) ? 'selected="selected"' : "",
														esc_html( $playlist->name ) );
												}
											?>
										</select>
									</td>
								</tr>
					<?php } } ?>

				</table>

				<?php if ( $this->get_option( 'account_linked' ) ): ?>
					<?php wp_nonce_field( $this->get_uid() ); ?>
					<p class="submit">
						<input type="submit" name="submit" class="button-primary" value="<?php _e( 'Update Options', 'classblogs' ); ?>" />
						<input type="submit" name="unlink_account" value="<?php _e( 'Unlink YouTube Account', 'classblogs' ); ?>" style="color: #a00; font-weight: bold; margin-left: 2em;" />
					</p>
				<?php endif; ?>
			</form>
		</div>
	<?php

	}

	/**
	 * Returns the value of the requested XML tag name, which must be unique.
	 *
	 * @param  object $dom an XML DOM document instance
	 * @param  string $tag the name of the tag
	 * @return mixed       the tag's value
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_single_tag_value( $dom, $tag )
	{
		return $dom->getElementsByTagName( $tag )->item(0)->nodeValue;
	}

	/**
	 * Gets the namespace URL for the given namespace.
	 *
	 * @param  object $dom       an XML DOM document instance
	 * @param  string $namespace the name of the namespace to find
	 * @param  string $tag       the tag name that contains the namespace URL
	 * @return string            the full namespace URL
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_namespace( $dom, $namespace, $tag = 'feed' )
	{
		$ns_tag = $dom->getElementsByTagName( $tag )->item(0);
		if ( $ns_tag ) {
			return $ns_tag->getAttribute( 'xmlns:' . $namespace );
		} else {
			return "";
		}
	}

	/**
	 * Returns a GData-style date as a standard PHP date.
	 *
	 * Credit for this function goes to Eric D. Hough from his TubePress plugin
	 *
	 * @param  string $date the GData date string
	 * @return string       a date string that can be understood by PHP's date()
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _parse_gdata_date( $date )
	{
		$tmp = str_replace( 'T', ' ', $date );
		$tmp = preg_replace( '!(\.[0-9]{1,})?!', '', $tmp );
		$datetime = substr( $tmp, 0, 19 );
		$timezone = str_replace( ':', "", substr( $tmp, 19, 6 ) );
		return mysql2date(
			get_option( 'date_format' ),
			strftime( '%Y-%m-%d %T', strtotime( $datetime . " " . $timezone ) ) );
	}

	/**
	 * Gets information on all videos that are part of the class's YouTube class.
	 *
	 * The returned array is in the same order as the actual YouTube playlist.
	 * Each entry in the array will be an object with the following properties:
	 *
	 *     link          - the URL to the video's YouTube page
	 *     playlist_id   - the ID of the video on the playlist
	 *     published     - the date on which the video was added to the playlist
	 *     thumbnail     - the URL of the large thumbnail
	 *     title         - the name of the video
	 *     used_by       - an array of post- and blog-ID pairs indicating which
	 *                     posts have embedded the video
	 *     video_id      - the YouTube ID for the video
	 *
	 * @return array a list of information about the class playlist videos
	 *
	 * @since 0.1
	 */
	public function get_playlist_videos()
	{
		global $wpdb;
		$videos = array();

		// Return early if we have a cached playlist or no valid playlist
		if (! $this->get_option( 'youtube_playlist' ) ) {
			return $videos;
		}
		$cached = $this->get_cache( 'playlist' );
		if ( $cached !== null ) {
			return $cached;
		}

		// Build the template URL for our request, with a placeholder to receive
		// an integer specifying the start index of the playlist results.  This
		// needs to be done due to the fact that YouTube returns at most 50
		// results in a playlist, yet playlists can have up to 200 videos.  The
		// start index placeholder is used to piece together the results of
		// muliple different playlist API calls.
		$url_template = sprintf( "%s%s?v=%d&max-results=%d&start-index=%%d",
			self::_PLAYLIST_API_BASE,
			$this->get_option( 'youtube_playlist' ),
			self::_GDATA_API_VERSION,
			self::_YOUTUBE_API_MAX_RESULTS );
		$start_index = 1;

		// Keep requesting playlist entries until we have every single entry
		$are_results = true;
		while ( $are_results ) {

			// Request the next slice of playlist entries
			$url = sprintf( $url_template, $start_index );
			$response = $this->_make_gdata_request( $url );
			if ( $response ) {

				// Get common namespaces for tags
				$media_namespace   = $this->_get_namespace( $response, 'media' );
				$youtube_namespace = $this->_get_namespace( $response, 'yt' );

				// Get information on each entry
				foreach ( $response->getElementsByTagName( 'entry' ) as $video ) {

					// Get the date the video was added to the playlist and the title
					$info = array(
						'link'      => "",
						'published' => $this->_parse_gdata_date( $this->_get_single_tag_value( $video, 'updated' ) ),
						'thumbnail' => "",
						'title'     => $this->_get_single_tag_value( $video, 'title' )
					);

					// Get the link to the video's page
					foreach ( $video->getElementsByTagName( 'link' ) as $link ) {
						if ( $link->getAttribute( 'rel' ) == 'alternate' && $link->getAttribute( 'type' ) == 'text/html' ) {
							$info['link'] = $link->getAttribute( 'href' );
							break;
						}
					}

					// Get the large default thumbnail for the video, which will
					// be the one thumbnail without a time attribute
					foreach ( $video->getElementsByTagNameNS( $media_namespace, 'thumbnail' ) as $thumb ) {
						$thumb_url = $thumb->getAttribute( 'url' );
						if ( preg_match( '/default\.\w{3,4}$/', $thumb_url ) && ! $thumb->hasAttribute( 'time' ) ) {
							$info['thumbnail'] = $thumb_url;
							break;
						}
					}

					// Get the video and playlist entry ID
					preg_match( '/:([^:]+)$/', $this->_get_single_tag_value( $video, 'id' ), $matches );
					$info['playlist_id'] = $matches[1];
					$info['video_id'] = $video->getElementsByTagNameNS( $youtube_namespace, 'videoid' )->item(0)->nodeValue;

					// Determine which posts reference the video
					$info['used_by'] = $wpdb->get_results( $wpdb->prepare( "
						SELECT vu.blog_id, vu.post_id
						FROM {$this->tables->videos} AS v, {$this->tables->video_usage} AS vu
						WHERE v.youtube_id = %s AND vu.video_id = v.id",
						$info['video_id'] ) );

					$videos[] = (object) $info;
					$start_index++;
				}

				// If we received less than the maximum number of results from
				// the API query, signal that we are finished getting results
				if ( ( $start_index - 1 ) % 50 ) {
					$are_results = false;
				}
			} else {
				$are_results = false;
			}
		}

		// Since the videos come to us from the API with the first video added
		// as the first element of the array and the last added as the last
		// element, we reverse the array to make the most recent be first
		$videos = array_reverse( $videos );

		// If the playlist has reached the maximum number of videos allowed in
		// in a playlist, flag this for later use
		$this->update_option(
			'playlist_full',
			count( $videos ) >= self::_PLAYLIST_MAX_ENTRIES );

		// Cache the full playlist
		$this->set_cache( 'playlist', $videos, self::_PLAYLIST_CACHE_LENGTH );
		return $videos;
	}

	/**
	 * Returns a list of recently added videos for use in the sidebar widget.
	 *
	 * @param  int   $limit the optional maximum number of videos to return
	 * @return array        a list of recently added videos
	 *
	 * @since 0.1
	 */
	public function get_recent_videos_for_sidebar( $limit = 5 )
	{
		$playlist = $this->get_playlist_videos();
		if ( $limit <= count( $playlist ) ) {
			return array_slice( $playlist, 0, $limit );
		} else {
			return $playlist;
		}
	}

	/**
	 * Returns the URL for viewing the class playlist page on YouTube.
	 *
	 * @return string the YouTube URL for the class playlist page
	 *
	 * @since 0.1
	 */
	public function get_youtube_playlist_page_url()
	{
		return sprintf(
			self::_YOUTUBE_PLAYLIST_PAGE_TEMPLATE,
			$this->get_option( 'youtube_playlist' ) );
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
}

ClassBlogs::register_plugin( 'youtube_class_playlist', new ClassBlogs_Plugins_YouTubeClassPlaylist() );

?>
