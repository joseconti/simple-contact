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
'consent_ip' => inet_pton( '203.0.113.10' ),
'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
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

$expected_context = array(
'name'       => 'Jane Doe',
'email'      => 'jane@example.com',
'created_at' => '2025-01-01 12:00:00',
'consent_ip' => '203.0.113.10',
'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
'insert_id'  => 42,
);

expectApplied( 'sc_email_to' )
->once()
->withArgs(
static function ( $recipient, $context ) use ( $expected_context ) {
return 'admin@example.com' === $recipient && $expected_context === $context;
}
)
->andReturn( 'filtered@example.com' );

expectApplied( 'sc_email_subject' )
->once()
->withArgs(
static function ( $subject, $context ) use ( $expected_context ) {
return 'New contact form submission' === $subject && $expected_context === $context;
}
)
->andReturn( 'Custom Subject' );

expectApplied( 'sc_email_headers' )
->once()
->withArgs(
static function ( $headers, $context ) use ( $expected_context ) {
return $expected_context === $context
&& $headers === array( 'Content-Type: text/plain; charset=UTF-8' );
}
)
->andReturn( array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: jane@example.com' ) );

$captured_message = '';

expectApplied( 'sc_email_message' )
->once()
->withArgs(
static function ( $message, $context ) use ( $expected_context, &$captured_message ) {
$captured_message = $message;

return $expected_context === $context;
}
)
->andReturnUsing(
static function ( $message ) {
return $message;
}
);

expect( 'wp_mail' )
->once()
->withArgs(
static function ( $to, $subject, $message, $headers ) use ( &$captured_message ) {
return 'filtered@example.com' === $to
&& 'Custom Subject' === $subject
&& $captured_message === $message
&& str_contains( $message, 'Name: Jane Doe' )
&& str_contains( $message, 'Email: jane@example.com' )
&& str_contains( $message, 'Entry ID: 42' )
&& str_contains( $message, 'Submitted at: 2025-01-01 12:00:00' )
&& str_contains( $message, 'IP Address: 203.0.113.10' )
&& str_contains( $message, 'User Agent: Mozilla/5.0 (compatible; TestBot/1.0)' )
&& in_array( 'Reply-To: jane@example.com', $headers, true );
}
)
->andReturn( true );

		$notification = new Simple_Contact_Notification();

		$result = $notification->send( $data, 42 );

$this->assertTrue( $result, 'Notification should report successful email dispatch.' );
$this->assertStringContainsString( 'Submitted at: 2025-01-01 12:00:00', $captured_message );
$this->assertStringContainsString( 'IP Address: 203.0.113.10', $captured_message );
$this->assertStringContainsString( 'User Agent: Mozilla/5.0 (compatible; TestBot/1.0)', $captured_message );
}
}
// phpcs:enable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
