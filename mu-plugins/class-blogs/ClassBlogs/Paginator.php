<?php

ClassBlogs::require_cb_file( 'Utils.php' );

/**
 * A paginator utility class.
 *
 * This class provides an abstract interface for paginated data, and is used
 * primarily by admin side of the class blogs plugins.  An example of using the
 * paginator to paginate a short list of data, showing at most two items per
 * page, is as follows:
 *
 *     $data = new array(1, 2, 3, 4, 5);
 *     $paginator = new ClassBlogs_Paginator( $data, 2 );
 *     assert( $paginator->get_total_pages() === 3 );
 *     assert( $paginator->get_items_for_page( 2 ) == array( 3, 4 ) );
 *     assert( $paginator->get_items_for_page( 5 ) == array() );
 *
 * Furthermore, if a paginator instance is used on admin page for a plugin,
 * the following can be executed before a table listing the paginated data
 * to display WordPress's admin paginating elements:
 *
 *     $paginator->show_admin_page_links();
 *
 * @package ClassBlogs
 * @subpackage Paginator
 * @since 0.1
 */
class ClassBlogs_Paginator
{

	/**
	 * Create a new paginator.
	 *
	 * This class takes an array of data to be paginated, and the maximum number
	 * of items to show per page.
	 *
	 * @param array $data           the data to be paginated
	 * @param int   $items_per_page the number of items to show per page
	 *
	 * @since 0.1
	 */
	public function __construct( $data, $items_per_page )
	{
		$this->_data = $data;
		$this->_per_page = absint( $items_per_page );
	}

	/**
	 * Gets any items of the data set that should be shown on the given page.
	 *
	 * @param  int   $page a page number
	 * @return array       a subset of the larger data set
	 *
	 * @since 0.1
	 */
	public function get_items_for_page( $page )
	{
		$page = absint( $page );
		if ( ! empty( $this->_data ) && $this->_per_page && $page <= $this->get_total_pages() ) {
			return array_slice( $this->_data, ( $page - 1 ) * $this->_per_page, $this->_per_page );
		} else {
			return array();
		}
	}

	/**
	 * Gets the number of pages needed to show the paginated data set.
	 *
	 * @return int the number of pages in the data set
	 *
	 * @since 0.1
	 */
	public function get_total_pages()
	{
		$items = count( $this->_data );
		if ( $items && $this->_per_page ) {
			return ceil($items / $this->_per_page);
		} else {
			return 0;
		}
	}

	/**
	 * Outputs markup for a list of links to use for paginating data.
	 *
	 * This markup is intended to be displayed on the WordPress admin side, and
	 * uses classes and styles that the admin styling is familiar with.
	 *
	 * @param int    $current_page the current page number
	 * @param string $css_class    an optional CSS class to apply to the nav wrapper
	 *
	 * @since 0.1
	 */
	public function show_admin_page_links( $current_page=1, $css_class="top" )
	{
		$current_page = absint( $current_page );
		$total = $this->get_total_pages();
		$items = count( $this->_data );
		if ( ! $items || ! $this->_per_page || $current_page > $total ) {
			return "";
		}

		// Determine the next and previous page numbers
		$next = $current_page + 1;
		$previous = $current_page - 1;
?>
		<div class="tablenav class-blogs <?php echo $css_class;  ?>">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php printf( _n( '%d item', '%d items', $items ), $items ); ?>
				</span>
				<span class="pagination-links">
					<a class="first-page <?php if ( ! $previous ) { echo 'disabled'; } ?>" href="<?php echo $this->_make_page_url( 1 ); ?>">&laquo;</a>
					<a class="previous-page <?php if ( ! $previous ) { echo 'disabled'; } ?>" href="<?php echo $this->_make_page_url( max( 1, $previous ) ); ?>">&lsaquo;</a>
					<span class="paging-input">
						<input class="current-page" type="text" size="1" value="<?php echo $current_page;  ?>" name="paged" title="<?php _e( 'Current page', 'classblogs' );  ?>" />
						of
						<span class="total-pages"><?php echo $total;  ?></span>
					</span>
					<a class="next-page <?php if ( $next > $total ) { echo 'disabled'; } ?>" href="<?php echo $this->_make_page_url( min( $total, $next) ); ?>">&rsaquo;</a>
					<a class="last-page <?php if ( $next > $total ) { echo 'disabled'; } ?>" href="<?php echo $this->_make_page_url( $total ); ?>">&raquo;</a>
				</span>
			</div>
		</div>
<?php
	}

	/**
	 * Returns the escaped URL for the given page of a paginated data set.
	 *
	 * @param  int    $page the page number
	 * @return string       the escaped URL of the page
	 *
	 * @access private
	 * @since 0.1
	 */
	private function _make_page_url( $page )
	{
		$url = ClassBlogs_Utils::get_current_url();
		$query = parse_url( $url, PHP_URL_QUERY );
		if ( $query ) {
			$url = preg_replace( '/\?.*/', '', $url );
		}
		$query = $_GET;
		$query['paged'] = $page;
		return esc_url( $url . '?' . http_build_query( $query ) );
	}
}

?>
