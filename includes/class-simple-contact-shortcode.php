<?php
/**
 * Registers the contact form shortcode.
 *
 * @package SimpleContact
 * @since 1.0.0
 */

/**
 * Class Simple_Contact_Shortcode
 *
 * Registers and renders the [simple_contact] shortcode.
 *
 * @since 1.0.0
 */
class Simple_Contact_Shortcode {
	/**
	 * Registers the shortcode with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register() {
		add_shortcode( 'simple_contact', array( __CLASS__, 'render' ) );
	}

	/**
	 * Renders the shortcode output.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Enclosed content (unused).
	 *
	 * @return string
	 */
	public static function render( $atts, $content = '' ) {
		$atts = shortcode_atts(
			array(
				'success_message' => '',
				'css_class'       => '',
			),
			$atts,
			'simple_contact'
		);

		$atts['success_message'] = is_string( $atts['success_message'] ) ? sanitize_text_field( $atts['success_message'] ) : '';
		$atts['css_class']       = is_string( $atts['css_class'] ) ? sanitize_text_field( $atts['css_class'] ) : '';

		return Simple_Contact_Form::render( $atts, $content );
	}
}
