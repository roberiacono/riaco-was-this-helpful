<?php
/**
 * PHPUnit test config for this plugin — points at a dedicated database
 * (wp_test_riwth) rather than the shared /tmp/wordpress-tests-lib database,
 * so this suite's data never mixes with other plugins' test runs.
 *
 * Loaded via WP_TESTS_CONFIG_FILE_PATH (set in tests/bootstrap.php).
 */

define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WP_DEFAULT_THEME', 'default' );
define( 'WP_DEBUG', true );

// ** Database settings ** //
define( 'DB_NAME', 'wp_test_riwth' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts — local test DB only, no real secrets.
 */
define( 'AUTH_KEY', 'riwth-tests' );
define( 'SECURE_AUTH_KEY', 'riwth-tests' );
define( 'LOGGED_IN_KEY', 'riwth-tests' );
define( 'NONCE_KEY', 'riwth-tests' );
define( 'AUTH_SALT', 'riwth-tests' );
define( 'SECURE_AUTH_SALT', 'riwth-tests' );
define( 'LOGGED_IN_SALT', 'riwth-tests' );
define( 'NONCE_SALT', 'riwth-tests' );
/**#@-*/

$table_prefix = 'wptests_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
