<?php
/**
 * Shared base test case for this plugin's WP_UnitTestCase-based tests.
 *
 * @package RIWTH
 */

/**
 * Base test case with belt-and-braces cleanup of plugin-specific state.
 */
abstract class RIWTH_Test_Case extends WP_UnitTestCase {

	/**
	 * Clean up plugin-specific state between tests.
	 *
	 * WP_UnitTestCase already wraps each test in a DB transaction that's
	 * rolled back afterwards, but the custom feedback table and the raw
	 * rate-limit rows written directly into wp_options bypass some of that
	 * safety net when a test crosses process boundaries, so clean them up
	 * explicitly too.
	 */
	public function tear_down() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . RIWTH_DB_NAME );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_riwth\_rl\_%'" );

		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		unset( $_SERVER['REMOTE_ADDR'] );

		parent::tear_down();
	}
}
