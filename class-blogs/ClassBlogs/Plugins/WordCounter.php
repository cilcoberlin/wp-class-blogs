<?php

ClassBlogs::require_cb_file( 'Admin.php' );
ClassBlogs::require_cb_file( 'BasePlugin.php' );
ClassBlogs::require_cb_file( 'Plugins/Aggregation/SitewidePosts.php' );
ClassBlogs::require_cb_file( 'Plugins/Aggregation/SitewideComments.php' );
ClassBlogs::require_cb_file( 'Utils.php' );

/**
 * A plugin that tracks the number of words produced each week by students.
 *
 * This provides an admin page available to any admins on the root blog that
 * displays the number of words written by each student over a period of time,
 * drawn from the content of their posts and comments.  The professor is able
 * to set a weekly minimum word count, which influences the display of the
 * word counts shown on the admin page, with word counts falling below the
 * minimum shown differently than those that exceed it.
 *
 * It also provides each student with a dashboard widget that displays their
 * word counts for the current week and the previous one.
 *
 * @package ClassBlogs_Plugins
 * @subpackage WordCounter
 * @since 0.1
 */
class ClassBlogs_Plugins_WordCounter extends ClassBlogs_BasePlugin
{
	/**
	 * The default options for the plugin.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array(
		'required_weekly_words' => 0
	);

	/**
	 * Admin media files.
	 *
	 * @access protected
	 * @var array
	 * @since 0.2
	 */
	protected $admin_media = array(
		'css' => array( 'word-counter.css' )
	);

	/**
	 * The numerical representation of Monday when using PHP's `date` function.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _MONDAY = 1;

	/**
	 * The numerical representation of Sunday when using PHP's `date` function.
	 *
	 * @access private
	 * @var int
	 * @since 0.2
	 */
	const _SUNDAY = 0;

	/**
	 * Registers WordPress hooks to enable the word counter.
	 */
	function __construct() {
		parent::__construct();
		add_action( 'wp_dashboard_setup', array( $this, '_add_student_dashboard_widget' ) );
	}

	/**
	 * Adds an admin dashboard widget to any student-blog admin pages that shows
	 * the student's word count for the current and previous weeks.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _add_student_dashboard_widget()
	{
		if ( is_admin() && ClassBlogs_Utils::on_student_blog_admin() ) {
			wp_add_dashboard_widget(
				'dashboard_' . $this->get_uid(),
				__( 'Word Count', 'classblogs' ),
				array( $this, '_handle_student_dashboard_widget' ) );
		}
	}

	/**
	 * Handles the logic to display the student-facing admin dashboard widget
	 * that shows their word count for the current and previous weeks.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _handle_student_dashboard_widget()
	{
		$date = new DateTime();
		$student_id = wp_get_current_user()->ID;
		$required_words = $this->get_option( 'required_weekly_words' );

		// Get the word count for the current and previous weeks
		$current_count = $this->_get_student_word_count_for_week( $student_id, $date );
		$date->modify( '-1 week' );
		$previous_count = $this->_get_student_word_count_for_week( $student_id, $date );

		// Display the word counts in the dashboard widget
		?>
			<div class="count current <?php if ( $required_words && $current_count < $required_words ) { echo 'under'; } ?>">
				<h5><?php _e( 'This Week', 'classblogs' ); ?></h5>
				<p><?php echo number_format( $current_count ); ?></p>
			</div>

			<div class="count previous <?php if ( $required_words && $previous_count < $required_words ) { echo 'under'; } ?>">
				<h5><?php _e( 'Previous Week', 'classblogs' ); ?></h5>
				<p><?php echo number_format( $previous_count ); ?></p>
			</div>

			<div class="clearfix"></div>

			<?php if ( $required_words ): ?>
				<p class="required">
					<?php _e( 'Words required per week', 'classblogs' ); ?>
					<span class="quantity"><?php echo number_format( $required_words ); ?></span>
				</p>
			<?php endif; ?>

		<?php
	}

	/**
	 * Gets the number of words used in a student's posts and comments for the
	 * week, starting on Monday, that contains the given date.
	 *
	 * @param  int    $student_id the ID of the student user
	 * @param  object $date       a DateTime instance of a date during a desired week
	 * @return int                the number of words used in content during the week
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_student_word_count_for_week( $student_id, $date )
	{
		// Figure out the date bounds for the week containing the given date,
		// with the week starting on Monday and ending on Sunday
		$start_date = $this->_find_weekday_near_date( self::_MONDAY, $date, '-1 day' );
		$end_date = $this->_find_weekday_near_date( self::_SUNDAY, $date, '+1 day' );

		return $this->_get_word_count_for_student( $student_id, $start_date, $end_date );
	}

	/**
	 * Adds the word-counter admin page if the user has sufficient privileges
	 * to view the page and we have access to the sitewide functionality of
	 * the class blogs suite.
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewidePosts to see if we can get sitewide data
	 * @uses ClassBlogs_Plugins_Aggregation_SitewideComments to see if we can get sitewide data
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function enable_admin_page( $admin )
	{
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$sitewide_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
		if ( ! empty( $sitewide_posts ) && ! empty( $sitewide_comments ) ) {
			$admin->add_admin_page( $this->get_uid(), __( 'Word Counts', 'classblogs' ), array( $this, '_admin_page' ) );
		}
	}

	/**
	 * Handles the logic to display the admin page for the plugin.
	 *
	 * @access private
	 * @since 0.2
	 */
	public function _admin_page()
	{

		// Update the plugin options
		if ( $_POST ) {
			check_admin_referer( $this->get_uid() );
			$this->update_option( 'required_weekly_words', absint( ClassBlogs_Utils::sanitize_user_input( $_POST['required_weekly_words'] ) ) );
			ClassBlogs_Admin::show_admin_message( __( 'Your word-counter options been updated.', 'classblogs' ) );
		}
?>

	<div class="wrap">
		<?php ClassBlogs_Admin::show_admin_icon();  ?>
		<h2><?php _e( 'Student Word Counts', 'classblogs' ); ?></h2>
<?php

		// Show the word-count table if we have word counts
		$word_counts = $this->_get_weekly_word_counts();
		$student_ids = ClassBlogs_Students::get_student_user_ids();
		if ( ! empty( $word_counts ) ):

			// Compute the total word counts for each student
			$total_counts = array();
			foreach ( $student_ids as $student_id ) {
				$total_counts[$student_id] = 0;
				foreach ( $word_counts as $week_counts ) {
					$total_counts[$student_id] += $week_counts['user_counts'][$student_id];
				}
			}

			// Precompute each student's name
			$student_names = array();
			foreach ( $student_ids as $student_id ) {
				$user_data = get_userdata( $student_id );
				$student_names[$student_id] = $user_data->display_name;
			}
?>
		<h3><?php _e( 'Word Counts by Week', 'classblogs' ); ?></h3>

		<p id="student-word-counts-instructions">
			<?php _e( 'The table below shows the word counts for each student, drawn from any posts and comments that they have written, broken down by the week for which those counts are calculated.  The date displayed in the "Week of" column is for the Monday that started that week.', 'classblogs' ); ?>
		</p>

		<div id="student-word-counts-wrap">
			<table id="student-word-counts">

				<thead>
					<tr>
						<th class="week"><?php _e( 'Week of', 'classblogs' ); ?></th>
						<?php
							// Show each student's name in the header
							foreach ( $student_ids as $student_id ) {
								printf( '<th>%s</th>',esc_html( $student_names[$student_id] ) );
							}
						?>
					</tr>
				</thead>

				<tfoot>
					<th><?php _e( 'Totals', 'classblogs' ); ?></th>
					<?php
						// Display each student's total words in the footer
						foreach ( $student_ids as $student_id ) {
							printf( '<td title="%s">%s</td>',
								esc_attr( $student_names[$student_id] ),
								number_format( $total_counts[$student_id] ) );
						}
					?>
				</tfoot>

				<tbody>
					<?php
						// Show each week and every student's total words for that week
						$required_words = $this->get_option( 'required_weekly_words' );
						foreach ( $word_counts as $week_counts ) {
							echo "<tr>";
							$verbose_date = date_i18n( 'M j, Y', (int) $week_counts['week_start']->format( 'U' ) );
							printf( '<th class="week">%s</th>', $verbose_date );
							$counter = 0;
							foreach ( $student_ids as $student_id ) {
								$classes = array();
								$count = $week_counts['user_counts'][$student_id];
								if ( ! $count ) {
									$classes[] = 'null';
								} else if ( $count < $required_words ) {
									$classes[] = 'under';
								} else if ( $count >= $required_words ) {
									$classes[] = 'over';
								}
								$classes[] = ($counter % 2) ? 'even' : 'odd';
								$counter++;
								printf( '<td title="%s" class="%s">%s</td>',
									esc_attr( sprintf( __('%1$s on %2$s'), $student_names[$student_id], $verbose_date ) ),
									implode( ' ', $classes ),
									number_format( $count ) );
							}
							echo "</tr>";
						}
					?>
				</tbody>

			</table>
		</div>

		<?php endif; ?>

		<h3><?php _e( 'Options', 'classblogs' ); ?></h3>

		<form method="post" action="">

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Required Weekly Words', 'classblogs' ); ?></th>
					<td>
						<input type="text" name="required_weekly_words" id="required-weekly-words" value="<?php echo esc_attr( $this->get_option( 'required_weekly_words' ) ); ?>" /><br />
						<label for="required-weekly-words"><?php _e( 'The number of words a student must write per week.', 'classblogs' ); ?></label>
					</td>
				</tr>
			</table>

			<?php wp_nonce_field( $this->get_uid() ); ?>
			<p class="submit"><input type="submit" class="button-primary" name="Submit" value="<?php _e( 'Update Required Word Count', 'classblogs' ); ?>" /></p>
		</form>

	</div>

<?php
	}

	/**
	 * Gets weekly word counts for all students that are part of a class.
	 *
	 * This returns an array ordered by the week in which the posts and comments
	 * that provide the word counts were made.  Each entry in the array has a
	 * `week_start` key whose value is a DateTime instance of the Monday that
	 * began that week. There is also a `user_counts` key, which is in turn
	 * another array, this one keyed by a student's user ID, with a value of
	 * the total number of words used in all posts and comments for that week.
	 *
	 * @return array the student word counts by week
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewidePosts to get the oldest and newest sitewide post
	 * @uses ClassBlogs_Plugins_Aggregation_SitewideComments to get the oldest and newest sitewide comment
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_weekly_word_counts()
	{
		global $wpdb;
		$by_week = array();

		// Get the dates of the oldest and newest posts and comments, which will
		// be used to influence our date bounds.  If no posts or comments are
		// found, abort and return an empty object.
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$newest_post = $sitewide_posts->get_newest_post();
		$oldest_post = $sitewide_posts->get_oldest_post();
		$sitewide_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
		$newest_comment = $sitewide_comments->get_newest_comment();
		$oldest_comment = $sitewide_comments->get_oldest_comment();
		if ( ( empty( $newest_post ) || empty( $oldest_post ) ) && ( empty( $newest_comment ) || empty( $oldest_comment ) ) ) {
			return $by_week;
		}

		// Move the start date back until we hit a Monday, and move the end date
		// forward until we hit another Monday
		$old_post = ( $oldest_post ) ? $oldest_post->post_date : "";
		$old_comment = ( $oldest_comment ) ? $oldest_comment->comment_date : "";
		$start_date = new DateTime( min( $old_post, $old_comment ) );
		$new_post = ( $newest_post ) ? $newest_post->post_date : "";
		$new_comment = ( $newest_comment ) ? $newest_comment->comment_date : "";
		$end_date = new DateTime( max( $new_post, $new_comment ) );
		$start_date = $this->_find_weekday_near_date( self::_MONDAY, $start_date, '-1 day' );
		$end_date = $this->_find_weekday_near_date( self::_MONDAY, $end_date, '+1 day' );
		if ( $start_date > $end_date ) {
			return $by_week;
		}

		// If the current date falls before the calculated end date, use today's
		// date as the end date
		$today = new DateTime();
		if ( $today < $end_date ) {
			$end_date = $today;
		}

		// Calculate the word counts for each user for each week between the
		// start and end date, with each entry in the array containing information
		// on the user counts and the date of the Monday that began the week
		$student_ids = ClassBlogs_Students::get_student_user_ids();
		$current_date = $start_date;
		while ( $current_date <= $end_date ) {

			// Get the word counts for each user
			$user_counts = array();
			$until_date = clone $current_date;
			$until_date->modify( '+6 days' );
			foreach ( $student_ids as $student_id ) {
				$user_counts[$student_id] = $this->_get_word_count_for_student(
					$student_id, $current_date, $until_date );
			}

			$by_week[] = array(
				'week_start' => clone $current_date,
				'user_counts' => $user_counts);
			$current_date->modify( '+1 week' );
		}
		return $by_week;
	}

	/**
	 * Applies the given interval to the given date until the date happens
	 * to fall on the given weekday.
	 *
	 * The interval passed can be any string that would be a valid argument
	 * to the `modify` method of a DateTime instance.  The weekday passed should
	 * use 0 to represent Sunday and 6 for Saturday.
	 *
	 * @param  int    $weekday the weekday to search for
	 * @param  object $date    a DateTime instance
	 * @param  string $step    an interval by which to modify the date object
	 * @return object          a DateTime instance that falls on a Monday
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _find_weekday_near_date( $weekday, $date, $step )
	{
		$weekday = (string) $weekday;
		$new_date = clone $date;
		while ( date( 'w', (int) $new_date->format( 'U' ) ) !== $weekday ) {
			$new_date->modify( $step );
		}
		return $new_date;
	}

	/**
	 * Calculates the number of words produced by a student in the given date window.
	 *
	 * @param  int    $user_id    the ID of the user
	 * @param  object $start_dt   a DateTime of the start date
	 * @param  object $end_dt     a DateTime of the end date
	 * @return int                the number of words produced by the student
	 *
	 * @uses ClassBlogs_Plugins_Aggregation_SitewidePosts to get all sitewide posts
	 * @uses ClassBlogs_Plugins_Aggregation_SitewideComments to get all sitewide comments
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_word_count_for_student( $user_id, $start_dt, $end_dt )
	{
		$words = 0;

		// Be explicit about the times of the given dates, making the start date
		// begin at midnight an the end date end at one second before midnight
		$start_dt->setTime( 0, 0, 0 );
		$end_dt->setTime( 23, 59, 59 );

		// Start with the word counts from the posts
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$posts = $sitewide_posts->filter_posts( $user_id, $start_dt, $end_dt );
		foreach ( $posts as $post ) {
			$words += $this->_get_word_count_for_text( $post->post_content );
		}

		// Add the word count from all comments
		$sitewide_comments = ClassBlogs::get_plugin( 'sitewide_comments' );
		$comments = $sitewide_comments->filter_comments( $user_id, $start_dt, $end_dt );
		foreach ( $comments as $comment ) {
			$words += $this->_get_word_count_for_text( $comment->comment_content );
		}

		return $words;
	}

	/**
	 * Determines the number of words in the given text.
	 *
	 * This first strips all formatting and then calculates the number of words
	 * using PHP's native `str_word_count` function.
	 *
	 * @param  string $text the text whose words should be counted
	 * @return int          the number of words in the text
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_word_count_for_text( $text )
	{
		// Code derived from http://www.php.net/manual/en/function.str-word-count.php#85579,
		// which allows this word counter to make a good-faith effort at counting
		// words in Unicode strings
		$plaintext = strip_shortcodes( strip_tags( $text ) );
		preg_match_all( "/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]*/u", $plaintext, $matches );
		if ( ! empty( $matches ) ) {
			return count( $matches[0] );
		} else {
			return 0;
		}
	}
}

ClassBlogs::register_plugin(
	'word_counter',
	'ClassBlogs_Plugins_WordCounter',
	__( 'Word Counter', 'classblogs' ),
	__( 'Adds an admin page for you to view student word counts by week, taken from their posts and comments.', 'classblogs' )
);

?>
