<?php
/**
 * Handles notification email dispatch for contact submissions.
 *
 * @package SimpleContact
 * @since 1.0.0
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
		$recipient = apply_filters( 'sc_email_to', get_option( 'admin_email' ), $data );
		$subject   = apply_filters( 'sc_email_subject', __( 'New contact form submission', 'simple-contact' ), $data );
		$headers   = apply_filters( 'sc_email_headers', array( 'Content-Type: text/plain; charset=UTF-8' ), $data );

		$message = $this->build_message( $data, $insert_id );

		return (bool) wp_mail( $recipient, $subject, $message, $headers );
	}

	/**
	 * Builds the email body content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data      Submission data to include in the message.
	 * @param int   $insert_id Inserted row ID.
	 *
	 * @return string
	 */
	private function build_message( array $data, $insert_id ) {
		$name  = isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
		$email = isset( $data['email'] ) ? sanitize_email( (string) $data['email'] ) : '';

		return sprintf(
			"%s\n\n%s: %s\n%s: %s\n%s: %d",
			__( 'You have received a new contact form submission.', 'simple-contact' ),
			__( 'Name', 'simple-contact' ),
			$name,
			__( 'Email', 'simple-contact' ),
			$email,
			__( 'Entry ID', 'simple-contact' ),
			(int) $insert_id
		);
	}
}
