<?php
/**
 * Registers the Gutenberg block for the contact form.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
 */

/**
 * Class Simple_Contact_Block
 *
 * Registers the block type and renders on the server.
 *
 * @since 1.0.0
 */
class Simple_Contact_Block {
	/**
	 * Block name identifier.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const BLOCK_NAME = 'simple-contact/form';

	/**
	 * Registers block assets and block type.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'simple-contact-block',
			SIMPLE_CONTACT_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components' ),
			SIMPLE_CONTACT_VERSION,
			true
		);

		wp_set_script_translations( 'simple-contact-block', 'simple-contact', SIMPLE_CONTACT_PATH . 'languages' );

		register_block_type(
			self::BLOCK_NAME,
			array(
				'editor_script'   => 'simple-contact-block',
				'render_callback' => array( __CLASS__, 'render_block' ),
				'attributes'      => array(
					'successMessage' => array(
						'type'    => 'string',
						'default' => '',
					),
					'cssClass'       => array(
						'type'    => 'string',
						'default' => '',
					),
				),
			)
		);
	}

	/**
	 * Renders the block on the front end.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public static function render_block( $attributes ) {
		$atts = array(
			'success_message' => isset( $attributes['successMessage'] ) && is_string( $attributes['successMessage'] ) ? sanitize_text_field( $attributes['successMessage'] ) : '',
			'css_class'       => isset( $attributes['cssClass'] ) && is_string( $attributes['cssClass'] ) ? sanitize_text_field( $attributes['cssClass'] ) : '',
		);

		return Simple_Contact_Form::render( $atts, '' );
	}
}
