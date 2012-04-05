<?php

/**
 * A schema manager for plugin database tables.
 *
 * This is used to provide a very light level of abstraction for the database
 * tables used by certain plugins.  Using a lightweight DSL, any plugin can
 * define schemata for the tables that they use.  The plugin can then use the
 * `apply_to_table` method of the schema to actually create a table.  If a table
 * already exists with the name given, the database table will be modified to
 * match the schema as closely as WordPress is capable of doing.
 *
 * For example, to create a table named 'test' with two columns, the first a
 * primary-key field named 'pk' and the other a character field that cannot be
 * null named 'text', which is also used in an index, this code could be used:
 *
 *     $schema = new ClassBlogs_Schema(
 *         array(
 *             array( 'pk',   'bigint(20) not null auto increment' ),
 *             array( 'text', 'varchar(255) not null' )
 *         ),
 *         'pk',
 *         array(
 *             array( 'text', 'text' )
 *         )
 *     );
 *     $schema->apply_to_table( 'test' );
 *
 * The details of the syntax used when declaring a schema can be found by
 * consulting the documentation for the constructor for the schema class.
 *
 * @package ClassBlogs
 * @subpackage Schema
 * @since 0.2
 */
class ClassBlogs_Schema
{

	/**
	 * Create a new schema manager.
	 *
	 * The arguments to this class must be formatted in a particular way.  The
	 * first $columns argument needs to be an array containing other arrays
	 * with two elements each.  The order of the outermost array elements will
	 * be the order in which the columns appear in the database.  Each inner
	 * array's first element must be a column name, and the second must be the
	 * description of the column.
	 *
	 * The $primary_key argument can be either a simple string of the name of
	 * the column to use for a primary key, or it can be an array containing
	 * multiple column names as strings to use for the primary key.
	 *
	 * The $keys argument is like $columns, as it must be an array containing other
	 * arrays.  The first element of each inner array must be the name to use
	 * for the index, and the second element can either be a single string for
	 * a single-column index, or an array with the names of the columns to use
	 * to create a multi-column index.
	 *
	 * An example of this is as follows:
	 *
	 *     new ClassBlogs_Schema(
	 *         array(
	 *             array( 'my_pk',  'bigint(20) not null auto increment' ),
	 *             array( 'first',  'bigint(20)' ),
	 *             array( 'second', 'bigint(20)' )
	 *         ),
	 *         'my_pk',
	 *         array(
	 *             array( 'single', 'first' ),
	 *             array( 'combined', ( 'first', 'second' ) )
	 *         )
	 *     )
	 *
	 * @param array  $columns     a list of columns that define the database
	 * @param mixed  $primary_key the column name or names to use as a primary key
	 * @param array  $keys        a list of key names and their constituent columns
	 *
	 * @since 0.2
	 */
	public function __construct( $columns, $primary_key = "", $keys = array() )
	{
		$this->_columns = $columns;
		$this->_primary_key = $primary_key;
		$this->_keys = $keys;
	}

	/**
	 * Create a table from the current schema, or modify an existing one of the
	 * same name to match the current schema.
	 *
	 * If this operation does not result in any modifications to the given table
	 * name, then `false` is returned.  If a table was created or modified to
	 * match the current schema, `true` is returned instead.
	 *
	 * @param  string $table the name of the table to which to apply the schema
	 * @return bool          whether the table was created or modified
	 *
	 * @since 0.2
	 */
	public function apply_to_table( $table )
	{
		global $wpdb;

		// Build the column-creation lines from our columns
		$statements = array();
		foreach ( $this->_columns as $column ) {
			$statements[] = sprintf( '%s %s', $column[0], $column[1] );
		}

		// Add a primary key if one is provided
		if ( ! empty( $this->_primary_key ) ) {
			$pk = is_array( $this->_primary_key ) ? $this->_primary_key : array( $this->_primary_key );
			$statements[] = sprintf( "PRIMARY KEY  (%s)",
				implode( ', ', $pk ) );
		}

		// Add any single- or multi-column indices
		if ( ! empty( $this->_keys ) ) {
			foreach ( $this->_keys as $key ) {
				$columns = is_array( $key[1] ) ? $key[1] : array( $key[1] );

				// Avoid re-adding composite indices, as WordPress's dbDelta
				// function doesn't know what to do with them
				$index = null;
				if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) == $table ) {
					$index = $wpdb->get_row( $wpdb->prepare( "
						SHOW INDEX FROM $table WHERE key_name=%s",
						$key[0] ) );
				}
				if ( count( $columns ) > 1 && ! empty( $index ) ) {
					continue;
				} else {
					$statements[] = sprintf( "KEY %s (%s)",
						$key[0], implode( ', ', $columns ) );
				}
			}
		}

		// Create or upgrade the table, returning whether or not a modification
		// of the table took place.  Modification in this sense excludes when
		// keys are changed, as this doesn't really change the table's columns.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$results = dbDelta( sprintf(
			"CREATE TABLE %s (\n  %s\n)%s;",
			$table, implode( ",\n  ", $statements ),
			( DB_CHARSET ) ? ' DEFAULT CHARSET=' . DB_CHARSET : "" ) );
		$modified = false;
		foreach ( $results as $id => $result ) {
			$id_type = array_pop( explode( ".", $id ) );
			if ( $id_type != 'PRIMARY' && $id_type != 'KEY' ) {
				$modified = true;
				break;
			}
		}
		return $modified;
	}

	/**
	 * Return the names of the columns in current schema in order.
	 *
	 * @return array a list of the column names
	 *
	 * @since 0.2
	 */
	public function get_column_names()
	{
		$columns = array();
		foreach ( $this->_columns as $column ) {
			$columns[] = $column[0];
		}
		return $columns;
	}

	/**
	 * Return the names of the columns in the given table in order.
	 *
	 * @param  string $table the name of a table
	 * @return array         a list of the column names in order
	 *
	 * @since 0.2
	 */
	public static function get_table_column_names( $table )
	{
		global $wpdb;
		$columns = array();
		foreach ( $wpdb->get_results( "SHOW COLUMNS FROM $table" ) as $column ) {
			$columns[] = $column->Field;
		}
		return $columns;
	}
}

?>
