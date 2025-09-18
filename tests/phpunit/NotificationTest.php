<?php
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * Tests for the Simple_Contact_Notification class.
 *
 * @package SimpleContact\Tests
 * @since 1.0.0
 */

namespace SimpleContact\Tests;

use Simple_Contact_Notification;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Class NotificationTest
 *
 * @since 1.0.0
 */
class NotificationTest extends TestCase {
	/**
	 * Ensures notification emails respect filters and call wp_mail with expected data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function test_notification_uses_filters_and_sends_email(): void {
		$data = array(
			'name'       => 'Jane Doe',
			'email'      => 'jane@example.com',
			'created_at' => '2025-01-01 12:00:00',
		);

		when( '__' )->alias(
			static function ( $text ) {
				return $text;
			}
		);

		when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return is_string( $value ) ? trim( $value ) : '';
			}
		);

		when( 'sanitize_email' )->alias(
			static function ( $value ) {
				return is_string( $value ) ? trim( $value ) : '';
			}
		);

		when( 'get_option' )->alias(
			static function ( $option ) {
				return 'admin_email' === $option ? 'admin@example.com' : null;
			}
		);

		expectApplied( 'sc_email_to' )
			->once()
			->with( 'admin@example.com', $data )
			->andReturn( 'filtered@example.com' );

		expectApplied( 'sc_email_subject' )
			->once()
			->with( 'New contact form submission', $data )
			->andReturn( 'Custom Subject' );

		expectApplied( 'sc_email_headers' )
			->once()
			->with( array( 'Content-Type: text/plain; charset=UTF-8' ), $data )
			->andReturn( array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: jane@example.com' ) );

		expect( 'wp_mail' )
			->once()
			->withArgs(
				static function ( $to, $subject, $message, $headers ) {
					return 'filtered@example.com' === $to
						&& 'Custom Subject' === $subject
						&& str_contains( $message, 'Name: Jane Doe' )
						&& str_contains( $message, 'Email: jane@example.com' )
						&& str_contains( $message, 'Entry ID: 42' )
						&& in_array( 'Reply-To: jane@example.com', $headers, true );
				}
			)
			->andReturn( true );

		$notification = new Simple_Contact_Notification();

		$result = $notification->send( $data, 42 );

		$this->assertTrue( $result, 'Notification should report successful email dispatch.' );
	}
}
// phpcs:enable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
