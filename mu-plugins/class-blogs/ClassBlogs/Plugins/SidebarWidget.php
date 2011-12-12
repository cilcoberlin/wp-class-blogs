<?php

/**
 * A base class for any sidebar widgets used in the class blogs suite
 *
 * @package ClassBlogs_Plugins
 * @subpackage SidebarWidget
 * @since 0.1
 */
abstract class ClassBlogs_Plugins_SidebarWidget extends WP_Widget
{

	/**
	 * The default options for the sidebar widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected $default_options = array();

	/**
	 * Initializes the widget
	 *
	 * This is a convenience function wrapping the standard widget constructor.
	 *
	 * @param string $name        the widget's name
	 * @param string $description the widget's description
	 * @param string $class       the optional CSS class name of the widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	public function __construct( $name, $description, $class )
	{
		parent::__construct(
			false,
			$name,
			array(
				'description' => $description,
				'classname'   => $class ) );
	}

	/**
	 * Outputs markup to contain a sidebar widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function start_widget( $params, $instance )
	{
		echo $params['before_widget'];
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( $title ) {
			echo $params['before_title'] . $title . $params['after_title'];
		}
	}

	/**
	 * Outputs markup to close a sidebar widget
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function end_widget( $params )
	{
		echo $params['after_widget'];
	}

	/**
	 * Returns the escaped value of the requested attribute for the given instance
	 *
	 * If the provided instance does not have the requested attribute, an
	 * empty string is returned
	 *
	 * @param  object $instance a widget instance
	 * @param  string $attr     the name of the instance attribute
	 * @return object           the escaped instance attribute's value
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function safe_instance_attr( $instance, $attr )
	{
		$value = array_key_exists( $attr, $instance ) ? $instance[$attr] : "";
		return esc_attr( $value );
	}


	/**
	 * Gives the instance default values if there are any and if the instance is null
	 *
	 * @param  object $instance a widget instance
	 * @return object           the possibly modified instance
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function maybe_apply_instance_defaults( $instance )
	{
		return wp_parse_args( $instance, $this->default_options );
	}

}
