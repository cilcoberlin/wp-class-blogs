<?php

/**
 * A low-level interface to an HTTP connection to a server.
 *
 * @package ClassBlogs
 * @subpackage Http
 * @since 0.4
 */
class ClassBlogs_Http
{

	/**
	 * The number of seconds to use as a timeout for requests.
	 *
	 * @access private
	 * @var int
	 * @since 0.4
	 */
	const _TIMEOUT_SECONDS = 7;

	/**
	 * The status line indicating an HTTP 200 response.
	 *
	 * @access private
	 * @var string
	 * @since 0.4
	 */
	const _HTTP_200_STATUS = "HTTP/1.1 200 OK";

	/**
	 * Create a new HTTP connection to a server.
	 *
	 * The URL of the server can either be the base URL of the server, or it
	 * can be a full URL on the server which will be parsed into the basic parts.
	 *
	 * @param string $server_url the URL of the server or a page on it
	 *
	 * @since 0.4
	 */
	function __construct( $server_url )
	{
		$parts = parse_url( $server_url );
		$this->_conn = $this->_connect_to_server(
			sprintf( "%s://%s", $parts['scheme'], $parts['host'] ) );
		$this->_request_lines = array();
	}

	/**
	 * Makes a connection to a remote server.
	 *
	 * @param  string $server  the URL of the remote server
	 * @return object          the connection object
	 *
	 * @access private
	 * @since 0.4
	 */
	private function _connect_to_server( $server )
	{
		$port   = preg_match( '/^https:/', $server ) ? 443 : 80;
		$server = preg_replace( '/^http:\/\//', "", $server );
		$server = preg_replace( '/^https/', 'ssl', $server );
		return fsockopen( $server, $port, $errno, $errst, self::_TIMEOUT_SECONDS );
	}

	/**
	 * Adds the given text to the request to server.
	 *
	 * @param string $line a new line of text to add to the list of requests
	 *
	 * @since 0.4
	 */
	public function add_request_line( $line )
	{
		$this->_request_lines[] = $line;
	}

	/**
	 * Reads the responses to the requests made to the connection.
	 *
	 * The returned responses will be available in an array sorted by the order
	 * in which the request was made.  Each response will be an object with the
	 * following properties:
	 *
	 *     body    - a string of the body content
	 *     headers - an array of key-value pairs of the headers
	 *     status  - an int of the returned HTTP status code
	 *
	 * @return array information on each response
	 *
	 * @since 0.4
	 */
	public function get_responses()
	{
		// Abort if no connection exists
		if ( ! $this->_conn ){
			return array();
		}

		// Actually send the requests to the server
		$this->add_request_line( "Connection: close\r\n\r\n" );
		fputs( $this->_conn, implode( '', $this->_request_lines ) );

		// Initialize frequently used variables
		$responses = array();
		$i = -1;
		$parse_chunk = "";
		$eol_size = strlen( "\r\n" );
		$status_ok = self::_HTTP_200_STATUS;
		$status_ok_length = strlen( self::_HTTP_200_STATUS );

		// Begin reading the responses to the requests
		while ( !feof( $this->_conn ) ) {
			$line = ( $parse_chunk) ? $parse_chunk : fgets( $this->_conn, 4096 );
			$add = "";
			$parse_chunk = "";

			// Reset variables for handling a new response
			$trim_line = trim( $line );
			if ( $trim_line == $status_ok ) {
				$responses[] = "";
				$i++;
				$chunk_size  = 0;
				$is_chunked  = false;
				$new_chunk   = "";
				$past_header = false;
				$response    = "";
			}

			//  If using a chunked transfer encoding, add each chunk to the response
			//  as it's read.  Otherwise, just add the body string.
			if ( $past_header ) {
				if ( $is_chunked ) {
					if ( ! $chunk_size || $chunk_size == strlen( $new_chunk ) - $eol_size ) {
						$chunk_size = hexdec( trim( $line ) );
						if ( $new_chunk ) {
							$add = preg_replace( '/\\r\\n$/', "", $new_chunk );
							$new_chunk = "";
						}
					} else {
						$new_chunk .= $line;
					}
				} else {

					// If the encoding is not chunked, we need to account for
					// the fact that we might have the header for the next
					// response thrown at the end of the body of the current
					// response by setting the next parse chunk manually
					if ( substr( $trim_line, 0 - $status_ok_length ) == $status_ok ) {
						$line = substr( $trim_line, 0, 0 - $status_ok_length );
						$parse_chunk = $status_ok;
					}
					$add = $line;
				}
			}
			else {
				$add = $line;
			}

			//  Detect whether or not we're using chunked encoding
			if ( ! $past_header && $line == "\r\n" ) {
				$past_header = true;
				$is_chunked = preg_match( '/Transfer-Encoding:\s+chunked/', $responses[$i] );
			}

			// Add the content to the correct response
			$responses[$i] .= $add;
		}

		// Parse the parts of the response, getting the body first
		$all_parsed = array();
		foreach ( $responses as $response ) {
			$parsed = array();
			$parts = explode( "\r\n\r\n", $response, 2 );
			$headers = explode( "\r\n", $parts[0] );
			$parsed['body'] = $parts[1];

			// Get the status as a dedicated field
			$status = array_shift( $headers );
			preg_match( '/\s+(\d+)\s+/', $status, $status_matches );
			$parsed['status'] = $status_matches[1];

			// Provide information on the other headers
			$parsed['headers'] = array();
			foreach ( $headers as $header ) {
				$header_parts = explode( ':', $header, 2 );
				$parsed['headers'][$header_parts[0]] = $header_parts[1];
			}

			$all_parsed[] = (object) $parsed;
		}

		return $all_parsed;
	}

	/**
	 * Closes the connection.
	 *
	 * @since 0.4
	 */
	public function close_connection()
	{
		fclose( $this->_conn );
	}
}

?>
