<?php
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * End-to-end style tests for the full submission workflow.
 *
 * @package SimpleContact\Tests
 * @since 1.1.0
 */

namespace SimpleContact\Tests;

use Simple_Contact_Form;
use Simple_Contact_Form_Handler;
use function Brain\Monkey\Actions\expectDone;
use function Brain\Monkey\Filters\expectApplied;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Class EndToEndSubmissionTest
 *
 * Validates that rendering, submission handling, and notice output cooperate.
 *
 * @since 1.1.0
 */
class EndToEndSubmissionTest extends TestCase {
        /**
         * Ensures a complete happy-path submission renders the filtered success notice.
         *
         * @since 1.1.0
         *
         * @return void
         */
        public function test_successful_submission_renders_filtered_notice(): void {
                global $wpdb;

                $previous_wpdb = $wpdb;
                $wpdb          = new class() {
                        public $prefix = 'wp_';
                        public $insert_id = 58;
                        public $insert_args = array();

                        public function insert( $table, $data, $formats ) {
                                $this->insert_args = array(
                                        'table'  => $table,
                                        'data'   => $data,
                                        'format' => $formats,
                                );

                                return 1;
                        }
                };

                $filter_calls = array();
                $post_data    = array(
                        'simple_contact_nonce' => 'nonce-value',
                        'simple_contact_name'  => ' Jane Doe ',
                        'simple_contact_email' => ' jane@example.com ',
                        'redirect_to'          => 'https://example.com/contact/',
                );
                $server_data = array(
                        'REMOTE_ADDR'     => '203.0.113.25',
                        'HTTP_USER_AGENT' => 'IntegrationBot/1.0',
                        'HTTP_HOST'       => 'example.com',
                        'REQUEST_URI'     => '/contact/',
                );
                $get_data    = array();
                $transients  = array();

                when( 'simple_contact_filter_input' )->alias(
                        static function ( $type, $variable, $filter = FILTER_DEFAULT ) use ( &$post_data, &$server_data, &$get_data, &$filter_calls ) {
                                $filter_calls[] = array(
                                        'type'     => $type,
                                        'variable' => $variable,
                                        'filter'   => $filter,
                                );
                                switch ( $type ) {
                                        case INPUT_POST:
                                                $source = $post_data;
                                                break;
                                        case INPUT_SERVER:
                                                $source = $server_data;
                                                break;
                                        case INPUT_GET:
                                                $source = $get_data;
                                                break;
                                        default:
                                                $source = array();
                                }

                                if ( ! isset( $source[ $variable ] ) ) {
                                        return null;
                                }

                                $value = $source[ $variable ];

                                if ( FILTER_VALIDATE_IP === $filter ) {
                                        return filter_var( $value, FILTER_VALIDATE_IP );
                                }

                                if ( FILTER_SANITIZE_FULL_SPECIAL_CHARS === $filter ) {
                                        return filter_var( $value, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                                }

                                return $value;
                        }
                );

                when( 'check_admin_referer' )->justReturn( true );
                when( 'wp_unslash' )->alias( array( self::class, 'identity' ) );
                when( 'sanitize_text_field' )->alias( array( self::class, 'trim_string' ) );
                when( 'sanitize_email' )->alias( array( self::class, 'trim_string' ) );
                when( 'is_email' )->alias(
                        static function ( $value ) {
                                return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
                        }
                );
                when( 'esc_url_raw' )->alias( array( self::class, 'identity' ) );
                when( 'wp_validate_redirect' )->alias( array( self::class, 'identity' ) );
                when( 'home_url' )->justReturn( 'https://example.com/' );
                when( 'admin_url' )->alias(
                        static function ( $path ) {
                                return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
                        }
                );
                when( 'wp_parse_args' )->alias(
                        static function ( $args, $defaults = array() ) {
                                return array_merge( (array) $defaults, (array) $args );
                        }
                );
                when( 'is_ssl' )->justReturn( true );
                when( '__' )->alias( array( self::class, 'identity' ) );
                when( 'esc_html__' )->alias( array( self::class, 'identity' ) );
                when( 'esc_attr__' )->alias( array( self::class, 'identity' ) );
                when( 'esc_html' )->alias( array( self::class, 'identity' ) );
                when( 'esc_attr' )->alias( array( self::class, 'identity' ) );
                when( 'esc_url' )->alias( array( self::class, 'identity' ) );
                when( 'wp_kses_post' )->alias( array( self::class, 'identity' ) );
                when( 'sanitize_html_class' )->alias( array( self::class, 'sanitize_html_class_value' ) );
                when( 'wp_nonce_field' )->alias(
                        static function () {
                                echo '<input type="hidden" name="simple_contact_nonce" value="generated" />';
                        }
                );
                when( 'wp_generate_uuid4' )->justReturn( 'abc-123' );
                when( 'sanitize_key' )->alias( array( self::class, 'sanitize_key_value' ) );
                when( 'set_transient' )->alias(
                        static function ( $key, $value, $expiration ) use ( &$transients ) {
                                $transients[ $key ] = array(
                                        'value'      => $value,
                                        'expiration' => $expiration,
                                );

                                return true;
                        }
                );
                when( 'get_transient' )->alias(
                        static function ( $key ) use ( &$transients ) {
                                return isset( $transients[ $key ] ) ? $transients[ $key ]['value'] : false;
                        }
                );
                when( 'delete_transient' )->alias(
                        static function ( $key ) use ( &$transients ) {
                                unset( $transients[ $key ] );

                                return true;
                        }
                );
                when( 'get_option' )->alias(
                        static function ( $option ) {
                                return 'admin_email' === $option ? 'admin@example.com' : null;
                        }
                );
                expect( 'wp_mail' )->once()->andReturn( true );

                when( 'add_query_arg' )->alias( array( self::class, 'build_query_url' ) );

                expectDone( 'sc_before_insert_contact' )
                        ->once()
                        ->withArgs(
                                static function ( $data ) {
                                        return is_array( $data )
                                                && 'Jane Doe' === $data['name']
                                                && 'jane@example.com' === $data['email'];
                                }
                        );

                expectDone( 'sc_after_insert_contact' )
                        ->once()
                        ->withArgs(
                                static function ( $insert_id, $data ) {
                                        return 58 === $insert_id && 'Jane Doe' === $data['name'];
                                }
                        );

                $captured_redirect = '';
                expect( 'wp_safe_redirect' )
                        ->once()
                        ->andReturnUsing(
                                static function ( $location ) use ( &$captured_redirect, &$get_data ) {
                                        $captured_redirect = $location;

                                        $parsed = parse_url( $location );
                                        if ( isset( $parsed['query'] ) ) {
                                                parse_str( $parsed['query'], $get_data );
                                        }

                                        throw new RedirectException( $location );
                                }
                        );

                $_POST = $post_data;

                try {
                        Simple_Contact_Form_Handler::handle();
                        $this->fail( 'Successful submissions should redirect the visitor.' );
                } catch ( RedirectException $exception ) {
                        $this->assertSame( $captured_redirect, $exception->getMessage() );
                }

                unset( $_POST );

                $this->assertSame( 'https://example.com/contact/?sc_status=success&sc_token=abc-123', $captured_redirect );
                $this->assertArrayHasKey( 'simple_contact_success_abc-123', $transients );

                $that = $this;
                expectApplied( 'sc_success_message' )
                        ->once()
                        ->withArgs(
                                static function ( $message, $payload ) use ( $that ) {
                                        $that->assertSame( 'Thank you for contacting us. We will get back to you soon.', $message );
                                        $that->assertSame( 'Jane Doe', $payload['name'] );
                                        $that->assertSame( 'jane@example.com', $payload['email'] );
                                        $that->assertSame( '203.0.113.25', $payload['consent_ip'] );
                                        $that->assertSame( 58, $payload['insert_id'] );
                                        $that->assertSame( 'IntegrationBot/1.0', $payload['user_agent'] );

                                        return true;
                                }
                        )
                        ->andReturn( 'Submission received, Jane!' );

                $html = Simple_Contact_Form::render( array( 'css_class' => ' hero   banner ' ) );

                $this->assertStringContainsString( 'Submission received, Jane!', $html );
                $this->assertStringContainsString( 'simple-contact-notice', $html );
                $this->assertStringContainsString( 'simple-contact-form hero banner', $html );
                $this->assertStringContainsString( 'action="https://example.com/wp-admin/admin-post.php"', $html );
                $this->assertStringContainsString( 'name="simple_contact_name"', $html );
                $this->assertStringContainsString( 'name="simple_contact_email"', $html );
                $this->assertContains(
                        array(
                                'type'     => INPUT_GET,
                                'variable' => 'sc_status',
                                'filter'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
                        ),
                        $filter_calls,
                        'Expected sc_status to be read from the query string.'
                );

                $this->assertArrayNotHasKey( 'simple_contact_success_abc-123', $transients );

                $wpdb = $previous_wpdb;
        }

        /**
         * Identity helper for stubbed WordPress functions.
         *
         * @since 1.1.0
         *
         * @param mixed $value Value to return.
         *
         * @return mixed
         */
        public static function identity( $value ) {
                return $value;
        }

        /**
         * Trims input strings for sanitization stubs.
         *
         * @since 1.1.0
         *
         * @param mixed $value Value to trim.
         *
         * @return string
         */
        public static function trim_string( $value ) {
                return is_string( $value ) ? trim( $value ) : '';
        }

        /**
         * Provides a minimal sanitize_key replacement for test expectations.
         *
         * @since 1.1.0
         *
         * @param string $value Raw input value.
         *
         * @return string
         */
        public static function sanitize_key_value( $value ) {
                $value = strtolower( (string) $value );

                return preg_replace( '/[^a-z0-9_\-]/', '', $value );
        }

        /**
         * Provides a minimal sanitize_html_class replacement.
         *
         * @since 1.1.0
         *
         * @param string $value Class name value.
         *
         * @return string
         */
        public static function sanitize_html_class_value( $value ) {
                return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value );
        }

        /**
         * Rebuilds a URL with query arguments similar to add_query_arg().
         *
         * @since 1.1.0
         *
         * @param array  $args Arguments to append.
         * @param string $url  Base URL.
         *
         * @return string
         */
        public static function build_query_url( $args, $url ) {
                $parsed = parse_url( $url );
                $query  = array();

                if ( isset( $parsed['query'] ) ) {
                        parse_str( $parsed['query'], $query );
                }

                foreach ( (array) $args as $key => $value ) {
                        if ( false === $value ) {
                                unset( $query[ $key ] );
                                continue;
                        }

                        $query[ $key ] = $value;
                }

                $scheme   = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : '';
                $host     = $parsed['host'] ?? '';
                $port     = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
                $path     = $parsed['path'] ?? '';
                $fragment = isset( $parsed['fragment'] ) ? '#' . $parsed['fragment'] : '';

                $built = $scheme . $host . $port . $path;

                if ( ! empty( $query ) ) {
                        $built .= '?' . http_build_query( $query );
                }

                return $built . $fragment;
        }
}

// phpcs:enable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
