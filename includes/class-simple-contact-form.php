<?php
/**
 * Provides shared rendering utilities for the contact form.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
 */

/**
 * Class Simple_Contact_Form
 *
 * Renders the form used by both the shortcode and block.
 *
 * @since 1.0.0
 */
class Simple_Contact_Form {
	/**
	 * Renders the contact form markup.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attributes Shortcode or block attributes.
	 * @param string $content    Optional content (unused).
	 *
	 * @return string
	 */
	public static function render( $attributes = array(), $content = '' ) {
		unset( $content );

		$defaults   = array(
			'success_message' => __( 'Thank you for contacting us. We will get back to you soon.', 'simple-contact' ),
			'css_class'       => '',
		);
		$attributes = wp_parse_args( $attributes, $defaults );

		$success_message = is_string( $attributes['success_message'] ) ? sanitize_text_field( $attributes['success_message'] ) : sanitize_text_field( $defaults['success_message'] );
		$css_class       = is_string( $attributes['css_class'] ) ? sanitize_text_field( $attributes['css_class'] ) : '';

		$status          = self::get_query_param( 'sc_status' );
		$error_code      = self::get_query_param( 'sc_error' );
		$success_payload = array();

		if ( 'success' === $status ) {
			$token           = self::get_query_param( 'sc_token' );
			$success_payload = self::maybe_retrieve_success_data( $token );
		}

		$notice_html = self::prepare_notice( $status, $error_code, $success_message, $success_payload );

		$classes = array( 'simple-contact-form' );
		if ( '' !== $css_class ) {
			$classes = array_merge( $classes, self::sanitize_class_list( $css_class ) );
		}

		$action      = admin_url( 'admin-post.php' );
		$redirect_to = self::get_current_url();

		ob_start();
		?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php if ( ! empty( $notice_html ) ) : ?>
<div class="simple-contact-notice"><?php echo wp_kses_post( $notice_html ); ?></div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url( $action ); ?>" class="simple-contact-form__fields">
<p class="simple-contact-field">
<label for="simple-contact-name"><?php echo esc_html__( 'Name', 'simple-contact' ); ?></label>
<input type="text" id="simple-contact-name" name="simple_contact_name" required />
</p>
<p class="simple-contact-field">
<label for="simple-contact-email"><?php echo esc_html__( 'Email', 'simple-contact' ); ?></label>
<input type="email" id="simple-contact-email" name="simple_contact_email" required />
</p>
<input type="hidden" name="action" value="simple_contact_submit" />
<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />
		<?php wp_nonce_field( 'simple_contact_submit', 'simple_contact_nonce' ); ?>
<p class="simple-contact-actions">
<button type="submit" class="simple-contact-submit"><?php echo esc_html__( 'Send', 'simple-contact' ); ?></button>
</p>
</form>
</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Builds the feedback notice HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status          Submission status parameter.
	 * @param string $error_code      Error code parameter.
	 * @param string $success_message Success message attribute.
	 * @param array  $success_payload  Sanitized submission data used for filters.
	 *
	 * @return string
	 */
	private static function prepare_notice( $status, $error_code, $success_message, array $success_payload ) {
		if ( 'success' === $status ) {
			$message = apply_filters( 'sc_success_message', $success_message, $success_payload );
			return esc_html( $message );
		}

		if ( '' === $error_code ) {
			return '';
		}

		switch ( $error_code ) {
			case 'invalid_email':
				$message = __( 'Please provide a valid email address.', 'simple-contact' );
				break;
			case 'missing_fields':
				$message = __( 'Please fill in both your name and email.', 'simple-contact' );
				break;
			case 'nonce':
				$message = __( 'Security check failed. Please try again.', 'simple-contact' );
				break;
			case 'database':
				$message = __( 'We could not process your request. Please try again.', 'simple-contact' );
				break;
			default:
				$message = __( 'We could not process your request. Please try again.', 'simple-contact' );
		}

		return esc_html( $message );
	}

	/**
	 * Sanitizes a list of CSS classes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_list Raw class list string.
	 *
	 * @return array
	 */
	private static function sanitize_class_list( $class_list ) {
		$sanitized = array();

		foreach ( preg_split( '/\s+/', $class_list ) as $class_name ) {
			$class_name = trim( $class_name );

			if ( '' === $class_name ) {
				continue;
			}

			$sanitized_class = sanitize_html_class( $class_name );

			if ( '' !== $sanitized_class ) {
				$sanitized[] = $sanitized_class;
			}
		}

		return $sanitized;
	}

	/**
	 * Determines the current URL for redirecting after submission.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function get_current_url() {
		$host = simple_contact_filter_input( INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$uri  = simple_contact_filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $host ) || empty( $uri ) ) {
			return home_url();
		}

		$scheme = is_ssl() ? 'https' : 'http';

		return esc_url_raw( sprintf( '%s://%s%s', $scheme, $host, $uri ) );
	}

	/**
	 * Retrieves a sanitized query parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Query key to fetch.
	 *
	 * @return string
	 */
	private static function get_query_param( $key ) {
		$value = simple_contact_filter_input( INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		return is_string( $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Retrieves stored success payload data after redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param string $token Payload token.
	 *
	 * @return array
	 */
	private static function maybe_retrieve_success_data( $token ) {
		if ( '' === $token ) {
			return array();
		}

		$key = 'simple_contact_success_' . sanitize_key( $token );

		$data = get_transient( $key );

		if ( false !== $data ) {
			delete_transient( $key );
		}

		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();

		if ( isset( $data['name'] ) ) {
			$sanitized['name'] = sanitize_text_field( (string) $data['name'] );
		}

		if ( isset( $data['email'] ) ) {
			$sanitized['email'] = sanitize_email( (string) $data['email'] );
		}

		if ( isset( $data['created_at'] ) ) {
			$sanitized['created_at'] = sanitize_text_field( (string) $data['created_at'] );
		}

		if ( isset( $data['consent_ip'] ) && '' !== $data['consent_ip'] ) {
			$validated_ip = filter_var( $data['consent_ip'], FILTER_VALIDATE_IP );

			if ( false !== $validated_ip ) {
				$sanitized['consent_ip'] = sanitize_text_field( $validated_ip );
			}
		}

		if ( isset( $data['user_agent'] ) ) {
			$sanitized['user_agent'] = sanitize_text_field( (string) $data['user_agent'] );
		}

		if ( isset( $data['insert_id'] ) ) {
			$sanitized['insert_id'] = absint( $data['insert_id'] );
		}

		return $sanitized;
	}
}
