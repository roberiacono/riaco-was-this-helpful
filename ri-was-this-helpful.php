<?php
/*
Plugin Name: RI Was This Helpful
Plugin URI: https://letsplugin.com/wordpress-plugin/ri-was-this-helpful/
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 1.5.2
Author: Roberto Iacono
Author URI: https://letsplugin.com/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.2
Requires PHP: 7.0
Text Domain: riwth-was-this-helpful
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;


if ( ! defined( 'RIWTH_PLUGIN_VERSION' ) ) {
	define( 'RIWTH_PLUGIN_VERSION', '1.5.2' );
}

if ( ! defined( 'RIWTH_PLUGIN_FILE' ) ) {
	define( 'RIWTH_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'RIWTH_Was_This_Helpful', false ) ) {
	include_once dirname( RIWTH_PLUGIN_FILE ) . '/includes/class-was-this-helpful.php';
}


/**
 * Returns the main instance of RIWTH_PLUGIN.
 *
 * @since  1.5
 * @return RIWTH_Was_This_Helpful
 */
if ( ! function_exists( 'riwth_was_this_helpful' ) ) {
	function riwth_was_this_helpful() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		return RIWTH_Was_This_Helpful::get_instance();
	}

	riwth_was_this_helpful();
}
