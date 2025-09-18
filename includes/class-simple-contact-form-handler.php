<?php
/**
 * Handles processing of contact form submissions.
 *
 * @package SimpleContact
 * @since 1.0.0
 */

/**
 * Class Simple_Contact_Form_Handler
 *
 * Manages submission validation, persistence, and notifications.
 *
 * @since 1.0.0
 */
class Simple_Contact_Form_Handler {
	/**
	 * Registers WordPress actions for processing submissions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_post_nopriv_simple_contact_submit', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_simple_contact_submit', array( __CLASS__, 'handle' ) );
	}

	/**
	 * Handles form submissions routed through admin-post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function handle() {
		$redirect_to = self::get_redirect_url();

		if ( empty( $_POST['simple_contact_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::redirect_with_status( 'error', 'nonce', $redirect_to );
		}

		if ( ! check_admin_referer( 'simple_contact_submit', 'simple_contact_nonce' ) ) {
			self::redirect_with_status( 'error', 'nonce', $redirect_to );
		}

		$name  = isset( $_POST['simple_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['simple_contact_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$email = isset( $_POST['simple_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['simple_contact_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( '' === $name || '' === $email ) {
			self::redirect_with_status( 'error', 'missing_fields', $redirect_to );
		}

		if ( ! is_email( $email ) ) {
			self::redirect_with_status( 'error', 'invalid_email', $redirect_to );
		}

		$data = array(
			'name'       => $name,
			'email'      => $email,
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
			'consent_ip' => self::get_packed_ip(),
			'user_agent' => self::get_user_agent(),
		);

		/**
		 * Fires before a contact entry is inserted.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data Sanitized submission data.
		 */
		do_action( 'sc_before_insert_contact', $data );

		$insert_id = self::insert_submission( $data );

		if ( false === $insert_id ) {
			self::redirect_with_status( 'error', 'database', $redirect_to );
		}

		self::send_notification( $data, $insert_id );

		/**
		 * Fires after a contact entry is inserted.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $insert_id Row ID of the new contact.
		 * @param array $data      Submission data saved to the database.
		 */
		do_action( 'sc_after_insert_contact', $insert_id, $data );

		self::redirect_with_status( 'success', '', $redirect_to );
	}

	/**
	 * Determines redirect URL from submission payload.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function get_redirect_url() {
		if ( empty( $_POST['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return home_url();
		}

		$redirect = esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return wp_validate_redirect( $redirect, home_url() );
	}

	/**
	 * Inserts submission data into the database.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Sanitized data ready for insertion.
	 *
	 * @return int|false Inserted row ID on success, false otherwise.
	 */
	private static function insert_submission( array $data ) {
		global $wpdb;

		$table    = $wpdb->prefix . 'sc_contacts';
		$formats  = array( '%s', '%s', '%s', '%s', '%s' );
		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array(
				'name'       => $data['name'],
				'email'      => $data['email'],
				'created_at' => $data['created_at'],
				'consent_ip' => $data['consent_ip'],
				'user_agent' => $data['user_agent'],
			),
			$formats
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Sends the admin notification email.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data      Submission data.
	 * @param int   $insert_id Inserted row ID.
	 *
	 * @return void
	 */
	private static function send_notification( array $data, $insert_id ) {
		$to      = apply_filters( 'sc_email_to', get_option( 'admin_email' ), $data );
		$subject = apply_filters( 'sc_email_subject', __( 'New contact form submission', 'simple-contact' ), $data );
		$headers = apply_filters( 'sc_email_headers', array( 'Content-Type: text/plain; charset=UTF-8' ), $data );

		$message_body = sprintf(
			"%s\n\n%s: %s\n%s: %s\n%s: %d",
			__( 'You have received a new contact form submission.', 'simple-contact' ),
			__( 'Name', 'simple-contact' ),
			$data['name'],
			__( 'Email', 'simple-contact' ),
			$data['email'],
			__( 'Entry ID', 'simple-contact' ),
			$insert_id
		);

		wp_mail( $to, $subject, $message_body, $headers );
	}

	/**
	 * Retrieves the visitor IP address as packed binary.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Packed binary address or null if unavailable.
	 */
	private static function get_packed_ip() {
		$ip = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );

		if ( empty( $ip ) ) {
			return null;
		}

		$packed = inet_pton( $ip );

		return false === $packed ? null : $packed;
	}

	/**
	 * Retrieves the visitor user agent string.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Sanitized user agent or null when absent.
	 */
	private static function get_user_agent() {
		$agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $agent ) ) {
			return null;
		}

		$sanitized = sanitize_text_field( $agent );

		return '' === $sanitized ? null : $sanitized;
	}

	/**
	 * Redirects the user with query arguments for status messages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status       Either 'success' or 'error'.
	 * @param string $code         Optional error code.
	 * @param string $redirect_url Redirect destination.
	 *
	 * @return void
	 */
	private static function redirect_with_status( $status, $code = '', $redirect_url = '' ) {
		$redirect_url = '' === $redirect_url ? home_url() : $redirect_url;

		$args = array( 'sc_status' => $status );

		if ( '' !== $code ) {
			$args['sc_error'] = $code;
		} else {
			$args['sc_error'] = false;
		}

		$location = add_query_arg( $args, $redirect_url );

		wp_safe_redirect( $location );
		exit;
	}
}
