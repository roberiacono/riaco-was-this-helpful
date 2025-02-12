<?php
/*
Plugin Name: RIACO Was This Helpful
Plugin URI: https://www.robertoiacono.it/riaco-was-this-helpful/
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 2.0.0
Author: Roberto Iacono
Author URI: https://www.robertoiacono.it/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.2
Requires PHP: 7.0
Text Domain: riaco-was-this-helpful
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;


if ( ! defined( 'RIWTH_PLUGIN_VERSION' ) ) {
	define( 'RIWTH_PLUGIN_VERSION', '2.0.0' );
}

if ( ! defined( 'RIWTH_PLUGIN_FILE' ) ) {
	define( 'RIWTH_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'RIACO_Was_This_Helpful', false ) ) {
	include_once dirname( RIWTH_PLUGIN_FILE ) . '/includes/class-was-this-helpful.php';
}


/**
 * Returns the main instance of RIWTH_PLUGIN.
 *
 * @since  1.5
 * @return RIACO_Was_This_Helpful
 */
if ( ! function_exists( 'riaco_was_this_helpful' ) ) {
	function riaco_was_this_helpful() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		return RIACO_Was_This_Helpful::get_instance();
	}

	riaco_was_this_helpful();
}
