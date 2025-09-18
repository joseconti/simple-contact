<?php
// phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
/**
 * Tests for the Simple_Contact_Form_Handler class.
 *
 * @package SimpleContact\Tests
 * @since 1.0.0
 */

namespace SimpleContact\Tests;

use Simple_Contact_Form_Handler;
use function Brain\Monkey\Actions\expectDone;
use function Brain\Monkey\Functions\expect;
use function Brain\Monkey\Functions\when;

/**
 * Class FormHandlerTest
 *
 * @since 1.0.0
 */
class FormHandlerTest extends TestCase {
        /**
         * Ensures submissions without a nonce redirect with the nonce error code.
         *
         * @since 1.0.0
         *
         * @return void
         */
        public function test_handle_redirects_when_nonce_missing(): void {
                $_POST = array();

                when( 'home_url' )->justReturn( 'https://example.com/' );
                when( 'add_query_arg' )->alias( array( self::class, 'build_query_url' ) );

                $redirect = null;
                expect( 'wp_safe_redirect' )
                        ->once()
                        ->with( 'https://example.com/?sc_status=error&sc_error=nonce' )
                        ->andReturnUsing(
                                static function ( $location ) use ( &$redirect ) {
                                        $redirect = $location;
                                        throw new RedirectException( $location );
                                }
                        );

                try {
                        Simple_Contact_Form_Handler::handle();
                        $this->fail( 'Nonce failures should trigger a redirect.' );
                } catch ( RedirectException $exception ) {
                        $this->assertSame( $redirect, $exception->getMessage() );
                }

                unset( $_POST );
        }

        /**
         * Ensures invalid email addresses trigger the appropriate error redirect.
         *
         * @since 1.0.0
         *
         * @return void
         */
        public function test_handle_redirects_when_email_invalid(): void {
                $_POST = array(
                        'simple_contact_nonce' => 'nonce',
                        'simple_contact_name'  => ' Jane ',
                        'simple_contact_email' => 'invalid-email',
                        'redirect_to'          => 'https://example.com/form',
                );

                when( 'check_admin_referer' )->justReturn( true );
                when( 'wp_unslash' )->alias( array( self::class, 'identity' ) );
                when( 'sanitize_text_field' )->alias( array( self::class, 'trim_string' ) );
                when( 'sanitize_email' )->alias( array( self::class, 'trim_string' ) );
                when( 'is_email' )->justReturn( false );
                when( 'home_url' )->justReturn( 'https://example.com/' );
                when( 'esc_url_raw' )->alias( array( self::class, 'identity' ) );
                when( 'wp_validate_redirect' )->alias( array( self::class, 'identity' ) );
                when( 'add_query_arg' )->alias( array( self::class, 'build_query_url' ) );

                $redirect = null;
                expect( 'wp_safe_redirect' )
                        ->once()
                        ->with( 'https://example.com/form?sc_status=error&sc_error=invalid_email' )
                        ->andReturnUsing(
                                static function ( $location ) use ( &$redirect ) {
                                        $redirect = $location;
                                        throw new RedirectException( $location );
                                }
                        );

                try {
                        Simple_Contact_Form_Handler::handle();
                        $this->fail( 'Invalid email should trigger redirect.' );
                } catch ( RedirectException $exception ) {
                        $this->assertSame( $redirect, $exception->getMessage() );
                }

                unset( $_POST );
        }

        /**
         * Ensures successful submissions are persisted, notifications fire, and redirects include the token.
         *
         * @since 1.0.0
         *
         * @return void
         */
        public function test_handle_persists_submission_and_redirects_with_success_token(): void {
                global $wpdb;

                $previous_wpdb = $wpdb;
                $test_wpdb     = new class() {
                        public $prefix = 'wp_';
                        public $insert_args;
                        public $insert_id = 23;

                        public function insert( $table, $data, $formats ) {
                                $this->insert_args = array(
                                        'table'  => $table,
                                        'data'   => $data,
                                        'format' => $formats,
                                );

                                return 1;
                        }
                };

                $wpdb = $test_wpdb;

                $_POST = array(
                        'simple_contact_nonce' => 'nonce',
                        'simple_contact_name'  => 'Jane Doe',
                        'simple_contact_email' => 'jane@example.com',
                        'redirect_to'          => 'https://example.com/form',
                );

                when( 'check_admin_referer' )->justReturn( true );
                when( 'wp_unslash' )->alias( array( self::class, 'identity' ) );
                when( 'sanitize_text_field' )->alias( array( self::class, 'trim_string' ) );
                when( 'sanitize_email' )->alias( array( self::class, 'trim_string' ) );
                when( 'is_email' )->justReturn( true );
                when( 'esc_url_raw' )->alias( array( self::class, 'identity' ) );
                when( 'wp_validate_redirect' )->alias( array( self::class, 'identity' ) );
                when( 'home_url' )->justReturn( 'https://example.com/' );
                when( '__' )->alias( array( self::class, 'identity' ) );
                when( 'get_option' )->justReturn( 'admin@example.com' );
                when( 'apply_filters' )->alias( array( self::class, 'return_second_argument' ) );
                expect( 'wp_mail' )
                        ->once()
                        ->andReturn( true );
                when( 'wp_generate_uuid4' )->justReturn( 'abc-123' );
                when( 'sanitize_key' )->alias( array( self::class, 'sanitize_key_value' ) );

                $stored_transient = array();
                when( 'set_transient' )->alias(
                        static function ( $key, $value, $expiration ) use ( &$stored_transient ) {
                                $stored_transient = array(
                                        'key'        => $key,
                                        'value'      => $value,
                                        'expiration' => $expiration,
                                );

                                return true;
                        }
                );

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
                                        return 23 === $insert_id
                                                && is_array( $data )
                                                && 'Jane Doe' === $data['name']
                                                && 'jane@example.com' === $data['email'];
                                }
                        );

                $redirect = null;
                expect( 'wp_safe_redirect' )
                        ->once()
                        ->with( 'https://example.com/form?sc_status=success&sc_token=abc-123' )
                        ->andReturnUsing(
                                static function ( $location ) use ( &$redirect ) {
                                        $redirect = $location;
                                        throw new RedirectException( $location );
                                }
                        );

                try {
                        Simple_Contact_Form_Handler::handle();
                        $this->fail( 'Successful submissions should redirect with a token.' );
                } catch ( RedirectException $exception ) {
                        $this->assertSame( $redirect, $exception->getMessage() );
                } finally {
                        unset( $_POST );
                        $wpdb = $previous_wpdb;
                }

                $this->assertSame( 'wp_sc_contacts', $test_wpdb->insert_args['table'] );
                $this->assertSame( 'Jane Doe', $test_wpdb->insert_args['data']['name'] );
                $this->assertSame( 'jane@example.com', $test_wpdb->insert_args['data']['email'] );

                $this->assertSame( 'simple_contact_success_abc-123', $stored_transient['key'] );
                $this->assertSame( 60, $stored_transient['expiration'] );
                $this->assertSame( 23, $stored_transient['value']['insert_id'] );
                $this->assertSame( 'jane@example.com', $stored_transient['value']['email'] );
                $this->assertSame( '', $stored_transient['value']['consent_ip'] );
                $this->assertArrayNotHasKey( 'user_agent', $stored_transient['value'] );
                $this->assertNotEmpty( $stored_transient['value']['created_at'] );
        }

        /**
         * Simple identity helper.
         *
         * @since 1.0.0
         *
         * @param mixed $value Value to return.
         *
         * @return mixed
         */
        public static function identity( $value ) {
                return $value;
        }

        /**
         * Trims strings and casts non-strings to empty strings.
         *
         * @since 1.0.0
         *
         * @param mixed $value Value to trim.
         *
         * @return string
         */
        public static function trim_string( $value ) {
                return is_string( $value ) ? trim( $value ) : '';
        }

        /**
         * Provides a deterministic sanitize_key replacement for tests.
         *
         * @since 1.0.0
         *
         * @param string $value Raw value.
         *
         * @return string
         */
        public static function sanitize_key_value( $value ) {
                $value = strtolower( (string) $value );

                return preg_replace( '/[^a-z0-9_\-]/', '', $value );
        }

        /**
         * Rebuilds a URL with the provided query arguments similar to add_query_arg().
         *
         * @since 1.0.0
         *
         * @param array  $args Arguments to merge.
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

        /**
         * Returns the second argument passed to apply_filters().
         *
         * @since 1.0.0
         *
         * @param string $hook   Filter name.
         * @param mixed  $value  Value to filter.
         *
         * @return mixed
         */
        public static function return_second_argument( $hook, $value, ...$args ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
                return $value;
        }
}

/**
 * Exception thrown to short-circuit wp_safe_redirect() calls during testing.
 *
 * @since 1.0.0
 */
class RedirectException extends \RuntimeException {}
// phpcs:enable WordPress.Files.FileName.NotHyphenatedLowercase,WordPress.Files.FileName.InvalidClassFileName
