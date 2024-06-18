<?php
/*
Plugin Name: RI Was This Helpful
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 1.4.1
Author: Roberto Iacono
Text Domain: ri-was-this-helpful
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

define( 'RI_WTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RI_WTH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class RI_Was_This_Helpful {

	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-admin-columns.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-box.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-ajax.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-admin-bar.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-functions.php';
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ri-was-this-helpful', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function activate_plugin() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ri_helpful_feedback';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            helpful tinyint(1) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// set default initial settings
		if ( false === get_option( 'ri_wth_display_on' ) ) {
			add_option( 'ri_wth_display_on', array( 'post' ) );
		}
		if ( false === get_option( 'ri_wth_load_styles' ) ) {
			add_option( 'ri_wth_load_styles', 1 );
		}
		if ( false === get_option( 'ri_wth_load_scripts' ) ) {
			add_option( 'ri_wth_load_scripts', 1 );
		}
	}

	public function maybe_enqueue_scripts() {
		if ( is_single() && is_main_query() ) {
			if ( get_option( 'ri_wth_load_styles' ) ) {
				wp_enqueue_style( 'ri-wth-style', plugin_dir_url( __FILE__ ) . 'css/style.css' );
			}
			if ( get_option( 'ri_wth_load_scripts' ) ) {
				wp_enqueue_script( 'ri-wth-script', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'jquery' ), false, true );
				wp_localize_script(
					'ri-wth-script',
					'ri_wth_scripts',
					array(
						'ajax_url'   => admin_url( 'admin-ajax.php' ),
						'thank_you'  => __( 'Thank you for your feedback!', 'ri-was-this-helpful' ),
						'submitting' => __( 'Submitting...', 'ri-was-this-helpful' ),
					)
				);
			}
		}
	}
}

new RI_Was_This_Helpful();
