<?php

/**
 * A base class for any widgets used in the class-blogs suite.
 *
 * This provides a few basic methods to a descending widget, mainly to help
 * with handling options and validating user input on the widget admin panel.
 *
 * Any child widgets should provide definitions for the functions expected
 * by WordPress, being `widget()`, `update()`, and `form()`.  In addition to
 * these, they should also provide definitions for the functions expected by
 * this class that provide the widget with a name and description, being `get_name()`
 * and `get_description()`, respectively.  Additionaly, a child widget can also
 * provide a `$css_class` attribute that defines the CSS class to be applied
 * to the widget and the default options for the widget via `$default_options`.
 *
 * An example of a possible child widget is as follows:
 *
 *     class MyWidget extends ClassBlogs_Widget {
 *
 *         protected $default_options = array(
 *             'option_one' => 1,
 *             'option_two' => 2
 *         );
 *
 *         protected $css_class = 'my-widget';
 *
 *         protected function get_name() {
 *             return 'My Widget';
 *         }
 *
 *         protected function get_description() {
 *             return 'The description of my widget.';
 *         }
 *
 *         ...
 *     }
 *
 * @package ClassBlogs_Plugins
 * @subpackage SidebarWidget
 * @since 0.2
 */
abstract class ClassBlogs_Widget extends WP_Widget
{

	/**
	 * The default options for the widget.
	 *
	 * @access protected
	 * @var array
	 * @since 0.1
	 */
	protected $default_options = array();

	/**
	 * An optional CSS class or classes that is assigned to the widget.
	 *
	 * @access protected
	 * @var string
	 * @since 0.2
	 */
	protected $css_class = "";

	/**
	 * Initializes the widget.
	 *
	 * As part of initialization, this method uses the values of two required
	 * methods and one optional attribute on the widget.
	 *
	 * The first required method, `get_name()`, should return the name to use
	 * for the widget.  The second, `get_description()`, should return the
	 * widget's description.  The optional attribute, `$css_class`, may be
	 * given a value to provide the widget with a custom CSS class.
	 *
	 * In addition to these methods and attributes, this also calls the `init()`
	 * method on the descending plugin, which can contain any custom initializaiton
	 * code that the plugin wants to run.
	 *
	 * @access protected
	 * @since 0.1
	 */
	public function __construct()
	{
		parent::__construct(
			false,
			$this->get_name(),
			array(
				'description' => $this->get_description(),
				'classname'   => 'cb-widget ' . $this->css_class ) );
		$this->init();
	}

	/**
	 * Allows the descending widget to safely execute initialization code.
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function init() {}

	/**
	 * Returns the name to use for the widget.
	 *
	 * This is implemented as a method to allow the widget descended from this
	 * class to provide internationalized text.
	 *
	 * @return string the name of the widget
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function get_name() {}

	/**
	 * Returns the description to use for the widget.
	 *
	 * This is implemented as a method to allow the widget descended from this
	 * class to provide internationalized text.
	 *
	 * @return string the name of the widget
	 *
	 * @access protected
	 * @since 0.2
	 */
	protected function get_description() {}

	/**
	 * Outputs markup to open a widget.
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
	 * Outputs markup to close a widget.
	 *
	 * @access protected
	 * @since 0.1
	 */
	protected function end_widget( $params )
	{
		echo $params['after_widget'];
	}

	/**
	 * Returns the escaped value of the requested attribute for the given instance.
	 *
	 * If the provided instance does not have the requested attribute, an
	 * empty string is returned.
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
	 * Gives the instance default values if there are any and if the instance is null.
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

	/**
	 * Registers a widget that should only be available on the root blog.
	 *
	 * This makes the widget only appear as a selection on the admin side if the
	 * user is an admin on the root blog and the root blog is being edited, but
	 * will show the widget to any user viewing the root blog.
	 *
	 * @param object $widget an instance of the widget class
	 *
	 * @since 0.2
	 */
	public static function register_root_only_widget( $widget )
	{
		global $blog_id;

		// If we're currently on the root blog, see if we're either viewing the
		// public-facing part of the blog or are a user with admin rights viewing
		// the admin side.  If so, register the widget.
		if ( ClassBlogs_Utils::is_root_blog() && ( ! is_admin() || ClassBlogs_Utils::current_user_is_admin_on_root_blog() ) ) {
			register_widget( $widget );
		}
	}

}
