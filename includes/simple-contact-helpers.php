<?php
/**
 * Helper functions for the Simple Contact plugin.
 *
 * @package SimpleContact
 * @since 1.1.0
 */

if ( ! function_exists( 'simple_contact_filter_input' ) ) {
	/**
	 * Wrapper around filter_input() to ease testing.
	 *
	 * @since 1.1.0
	 *
	 * @param int    $type     Input type constant (INPUT_GET, INPUT_POST, INPUT_SERVER).
	 * @param string $variable Variable name to retrieve.
	 * @param int    $filter   Optional PHP filter constant.
	 *
	 * @return mixed The filtered value or null when unavailable.
	 */
	function simple_contact_filter_input( $type, $variable, $filter = FILTER_DEFAULT ) {
		return filter_input( $type, $variable, $filter );
	}
}
