<?php

/**
 * The word-counter plugin
 *
 * This provides an admin page available to any admins on the root blog that
 * displays the number of words written by each student over a period of time.
 *
 * @package Class Blogs
 * @since 0.1
 */
class ClassBlogs_Plugins_WordCounter extends ClassBlogs_Plugins_BasePlugin
{
	/**
	 * The default options for the plugin
	 *
	 * @access protected
	 * @var array
	 */
	protected $default_options = array(
		'required_weekly_words' => 0
	);

	/**
	 * The numerical representation of Monday when using PHP's `date` function.
	 */
	const MONDAY = 1;

	/**
	 * The numerical representation of Sunday when using PHP's `date` function.
	 */
	const SUNDAY = 0;

	/**
	 * Registers WordPress hooks to enable the word counter
	 */
	function __construct() {
		parent::__construct();

		add_action( 'admin_head',         array( $this, '_add_admin_styles' ) );
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
		if ( is_admin() && ! ClassBlogs_Utils::is_root_blog() ) {
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
	 * Gets the number of words used in a student's posts for the week, starting
	 * on Monday, that contains the given date.
	 *
	 * @param  int    $student_id the ID of the student user
	 * @param  object $date       a DateTime instance of a date during a desired week
	 * @return int                the number of words used in posts during the week
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _get_student_word_count_for_week( $student_id, $date )
	{
		// Figure out the date bounds for the week containing the given date,
		// with the week starting on Monday and ending on Sunday
		$start_date = $this->_find_weekday_near_date( self::MONDAY, $date, '-1 day' );
		$end_date = $this->_find_weekday_near_date( self::SUNDAY, $date, '+1 day' );

		// Get the word count for the posts made during the week
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$posts = $sitewide_posts->filter_posts( $student_id, $start_date, $end_date );
		return $this->_get_word_count_for_posts( $posts );
	}

	/**
	 * Adds admin styles to the word counter page.
	 *
	 * @access private
	 * @since 0.1
	 */
	public function _add_admin_styles()
	{
		printf( '<link rel="stylesheet" href="%sword-counter.css" />',
			ClassBlogs_Utils::get_plugin_css_url() );
	}

	/**
	 * Adds the word-counter admin page if the user has sufficient privileges
	 * to view the page and we have access to the sitewide functionality of
	 * the class blogs suite.
	 *
	 * @since 0.1
	 */
	public function enable_admin_page( $admin )
	{
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		if ( ! empty( $sitewide_posts ) ) {
			$admin->add_admin_page( $this->get_uid(), __( 'Word Counts', 'classblogs' ), array( $this, 'admin_page' ) );
		}
	}

	/**
	 * Handles the logic to display the admin page for the plugin
	 *
	 * @access private
	 * @since 0.1
	 */
	public function admin_page()
	{

		// Update the plugin options
		if ( $_POST ) {
			check_admin_referer( $this->get_uid() );
			$this->update_option( 'required_weekly_words', ClassBlogs_Utils::sanitize_user_input( $_POST['required_weekly_words'] ) );
			echo '<div id="message" class="updated fade"><p>' . __( 'Your word-counter options been updated.', 'classblogs' ) . '</p></div>';
		}

?>

	<div class="wrap">
		<h2><?php _e( 'Student Word Counts', 'classblogs' ); ?></h2>
<?php

		// Show the word-count table if we have word counts
		$word_counts = $this->_get_weekly_word_counts();
		$student_ids = ClassBlogs_Utils::get_student_user_ids();
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
			<?php _e( 'The table below shows the word counts for each student, broken down by the week for which those counts are calculated.  The date displayed in the "Week of" column is for the Monday that started that week.', 'classblogs' ); ?>
		</p>

		<div id="student-word-counts-wrap">
			<table id="student-word-counts">

				<thead>
					<tr>
						<th class="week"><?php _e( 'Week of', 'classblogs' ); ?></th>
						<?php
							// Show each student's name in the header
							foreach ( $student_ids as $student_id ) {
								printf( '<th>%s</th>', $student_names[$student_id] );
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
								$student_names[$student_id],
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
									sprintf( __('%1$s on %2$s'), $student_names[$student_id], $verbose_date ),
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
			<p class="submit"><input type="submit" name="Submit" value="<?php _e( 'Update Word-Counter Options', 'classblogs' ); ?>" /></p>
		</form>

	</div>

<?php
	}

	/**
	 * Gets weekly word counts for all students that are part of a class
	 *
	 * This returns an array ordered by the week in which the posts that provide
	 * the word count were made.  Each entry in the array has a `week_start` key
	 * whose value is a DateTime instance of the Monday that began that week.
	 * There is also a `user_counts` key, which is in turn another array, this
	 * one keyed by a student's user ID, with a value of the total number of
	 * words used in all posts for that week.
	 *
	 * @return array the student word counts by week
	 *
	 * @since 0.1
	 */
	private function _get_weekly_word_counts()
	{
		global $wpdb;
		$by_week = array();

		// Get the dates of the oldest and newest posts, which will be used to
		// influence our date bounds
		$sitewide_posts = ClassBlogs::get_plugin( 'sitewide_posts' );
		$newest_post = $sitewide_posts->get_newest_post();
		$oldest_post = $sitewide_posts->get_oldest_post();
		if ( empty( $newest_post ) || empty( $oldest_post ) ) {
			return $by_week;
		}

		// Move the start date back until we hit a Monday, and move the end date
		// forward until we hit another Monday
		$start_date = new DateTime($oldest_post->post_date);
		$end_date = new DateTime($newest_post->post_date);
		$start_date = $this->_find_weekday_near_date( self::MONDAY, $start_date, '-1 day' );
		$end_date = $this->_find_weekday_near_date( self::MONDAY, $end_date, '+1 day' );
		if ( $start_date > $end_date ) {
			return $by_week;
		}

		// Calculate the word counts for each user for each week between the
		// start and end date, with each entry in the array containing information
		// on the user counts and the date of the Monday that began the week
		$student_ids = ClassBlogs_Utils::get_student_user_ids();
		$current_date = $start_date;
		while ( $current_date <= $end_date ) {

			// Get the word counts for each user
			$user_counts = array();
			$until_date = clone $current_date;
			$until_date->modify( '+6 days' );
			foreach ( $student_ids as $student_id ) {
				$posts = $sitewide_posts->filter_posts( $student_id, $current_date, $until_date );
				$user_counts[$student_id] = $this->_get_word_count_for_posts( $posts );
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
	 * Gets the total number of words used in the given posts
	 *
	 * The posts that are passed to this function are identical to a row returned
	 * from a blog's posts table.
	 *
	 * @param  array $posts an array of post objects
	 * @return int          the number of words used in all the posts given
	 */
	private function _get_word_count_for_posts( $posts )
	{
		$total = 0;
		foreach ( $posts as $post ) {
			$total += $this->_get_word_count( $post->post_content );
		}
		return $total;
	}

	/**
	 * Determines the number of words in the given text.
	 *
	 * This first strips all formatting and then calculates the number of words
	 * using PHP's native `str_word_count` function.
	 *
	 * @param  string $text the text whose words should be counted
	 * @return int          the number of words in the text
	 */
	private function _get_word_count( $text )
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

ClassBlogs::register_plugin( 'word_counter', new ClassBlogs_Plugins_WordCounter() );

?>
