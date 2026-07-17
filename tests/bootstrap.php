<?php
/**
 * PHPUnit bootstrap file.
 *
 * Reuses the already-provisioned WP core checkout (/tmp/wordpress) and the
 * WP core PHPUnit test-suite library (/tmp/wordpress-tests-lib) shared
 * across other plugins on this machine, while giving this plugin its own
 * dedicated test database via WP_TESTS_CONFIG_FILE_PATH (see
 * tests/wp-tests-config.php) so test data never collides between plugins.
 *
 * @package RIWTH
 */

// This plugin's own wp-tests-config.php (dedicated DB). Must be the full
// file path, not just its directory — when this constant is defined,
// includes/bootstrap.php uses it verbatim instead of appending
// '/wp-tests-config.php' itself.
define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

// This plugin's own Composer-installed copy of the polyfills.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, is the WP core test suite checked out there?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested, on muplugins_loaded (before
 * plugins_loaded/init). require()-ing the main file triggers
 * RIWTH_Was_This_Helpful's constructor (define_constants/includes/
 * init_hooks) immediately, registering all add_action() hooks, including
 * plugins_loaded priority 9 (maybe_upgrade_db) and priority 10 (init),
 * which then fire naturally later in this same bootstrap request exactly
 * as in a real WP load.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/riaco-was-this-helpful.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * require()-ing the main file above never fires register_activation_hook's
 * callback, so set_initial_settings() (default options) would otherwise
 * never run and every should_display_box()/could_display_box() check would
 * see an empty riwth_display_on option. maybe_upgrade_db() (real
 * plugins_loaded, priority 9) already creates the table, but
 * activate_plugin() bundles create_database() + set_initial_settings() +
 * update_option('riwth_db_version') in one idempotent method — safe to call
 * explicitly here, once, on WP's real 'init' action at priority -10 (after
 * plugins_loaded's init() has already wired the unconditional classes'
 * hooks).
 */
function _manually_install_schema() {
	RIWTH_Was_This_Helpful::get_instance()->activate_plugin();
}
tests_add_filter( 'init', '_manually_install_schema', -10 );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// Shared test helpers, available to every test file without per-file requires.
require_once __DIR__ . '/helpers/class-riwth-test-factory.php';
require_once __DIR__ . '/helpers/class-riwth-test-case.php';
