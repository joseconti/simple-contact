<?php
/**
 * Handles notification email dispatch for contact submissions.
 *
 * @package SimpleContact
 * @since 1.0.0
 * @author Codex
 */

/**
 * Class Simple_Contact_Notification
 *
 * Composes and sends notification emails for contact submissions.
 *
 * @since 1.0.0
 */
class Simple_Contact_Notification {
	/**
	 * Sends the notification email to the configured recipient.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data      Sanitized submission data.
	 * @param int   $insert_id Inserted row ID.
	 *
	 * @return bool True when the email dispatch reports success, false otherwise.
	 */
	public function send( array $data, $insert_id ) {
		$context = $this->prepare_context( $data, $insert_id );

		$recipient = apply_filters( 'sc_email_to', get_option( 'admin_email' ), $context );
		$subject   = apply_filters( 'sc_email_subject', __( 'New contact form submission', 'simple-contact' ), $context );
		$headers   = apply_filters( 'sc_email_headers', array( 'Content-Type: text/plain; charset=UTF-8' ), $context );

		$message = $this->build_message( $context );
		$message = apply_filters( 'sc_email_message', $message, $context );

		return (bool) wp_mail( $recipient, $subject, $message, $headers );
	}

	/**
	 * Builds the email body content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $context Submission context prepared for messaging.
	 *
	 * @return string
	 */
	private function build_message( array $context ) {
		$lines = array(
			__( 'You have received a new contact form submission.', 'simple-contact' ),
			'',
			sprintf( '%s: %s', __( 'Name', 'simple-contact' ), $context['name'] ),
			sprintf( '%s: %s', __( 'Email', 'simple-contact' ), $context['email'] ),
			sprintf( '%s: %d', __( 'Entry ID', 'simple-contact' ), $context['insert_id'] ),
		);

		if ( '' !== $context['created_at'] ) {
			$lines[] = sprintf( '%s: %s', __( 'Submitted at', 'simple-contact' ), $context['created_at'] );
		}

		if ( '' !== $context['consent_ip'] ) {
			$lines[] = sprintf( '%s: %s', __( 'IP Address', 'simple-contact' ), $context['consent_ip'] );
		}

		if ( '' !== $context['user_agent'] ) {
			$lines[] = sprintf( '%s: %s', __( 'User Agent', 'simple-contact' ), $context['user_agent'] );
		}

		return implode( PHP_EOL, $lines );
	}

	/**
	 * Prepares sanitized context passed to email filters and templates.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data      Submission data to include in the message.
	 * @param int   $insert_id Inserted row ID.
	 *
	 * @return array
	 */
	private function prepare_context( array $data, $insert_id ) {
		$name       = isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
		$email      = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';
		$created_at = isset( $data['created_at'] ) ? sanitize_text_field( (string) $data['created_at'] ) : '';
		$user_agent = '';

		if ( isset( $data['user_agent'] ) && '' !== $data['user_agent'] ) {
			$user_agent = sanitize_text_field( (string) $data['user_agent'] );
		}

		return array(
			'name'       => $name,
			'email'      => $email,
			'created_at' => $created_at,
			'consent_ip' => $this->normalize_ip( isset( $data['consent_ip'] ) ? $data['consent_ip'] : '' ),
			'user_agent' => $user_agent,
			'insert_id'  => (int) $insert_id,
		);
	}

	/**
	 * Normalizes the stored IP address to a printable string.
	 *
	 * @since 1.1.0
	 *
	 * @param mixed $raw_ip Raw IP address data from storage.
	 *
	 * @return string
	 */
	private function normalize_ip( $raw_ip ) {
		if ( empty( $raw_ip ) ) {
			return '';
		}

		$ip = $raw_ip;

		if ( is_string( $raw_ip ) && function_exists( 'inet_ntop' ) ) {
			$length = strlen( $raw_ip );

			if ( in_array( $length, array( 4, 16 ), true ) ) {
				$converted = inet_ntop( $raw_ip );

				if ( false !== $converted ) {
					$ip = $converted;
				}
			}
		}

		$ip_string = is_string( $ip ) ? $ip : (string) $ip;

		$validated = filter_var( $ip_string, FILTER_VALIDATE_IP );

		if ( false === $validated ) {
			return '';
		}

		return sanitize_text_field( (string) $validated );
	}
}
