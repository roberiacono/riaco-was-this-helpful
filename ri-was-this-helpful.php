<?php
/*
Plugin Name: RI Was This Helpful
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 1.4.6
Author: Roberto Iacono
Text Domain: ri-was-this-helpful
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'RI_WTH_DB_NAME' ) ) {
	define( 'RI_WTH_DB_NAME', 'ri_wth_helpful_feedback' );
}

if ( ! defined( 'RI_WTH_PLUGIN_VERSION' ) ) {
	define( 'RI_WTH_PLUGIN_VERSION', '1.4.6' );
}

if ( ! defined( 'RI_WTH_PLUGIN_DIR' ) ) {
	define( 'RI_WTH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'RI_WTH_PLUGIN_URL' ) ) {
	define( 'RI_WTH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! class_exists( 'RI_Was_This_Helpful' ) ) {
	class RI_Was_This_Helpful {

		private static $instance = null;

		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new RI_Was_This_Helpful();
			} else {
				error_log( 'RI_Was_This_Helpful instance already exists.' );
			}
			return self::$instance;
		}

		public function init() {
			$this->includes();
			$this->init_hooks();
		}


		private function includes() {
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-functions.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-settings.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-admin-columns.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-ajax.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-admin-bar.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-metabox.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-metabox-stats.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-box.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-user-role.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-shortcode.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-svg-icons.php';
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-block.php';
		}

		private function init_hooks() {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

			$user_role = new RI_WTH_User_Role();
			new RI_WTH_Settings();
			new RI_WTH_Box();
			new RI_WTH_Functions();
			new RI_WTH_Shortcode();
			new RI_WTH_Ajax();
			new RI_WTH_Block();

			if ( $user_role->can_user_see_stats() ) {
				new RI_WTH_Admin_Columns();
				new RI_WTH_Admin_Bar();
				new RI_WTH_Metabox();
				new RI_WTH_Metabox_Stats();
			}
		}




		public function load_textdomain() {
			load_plugin_textdomain( 'ri-was-this-helpful', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		public function activate_plugin() {
			global $wpdb;

			require_once plugin_dir_path( __FILE__ ) . 'includes/class-ri-wth-settings.php';

			$table_name      = $wpdb->prefix . RI_WTH_DB_NAME;
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            helpful tinyint(1) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			$initial_settings = RI_WTH_Settings::get_intial_settings();

			// set default initial settings
			foreach ( $initial_settings as $key => $value ) {
				if ( false === get_option( $key ) ) {
					add_option( $key, $value );
				}
			}
			add_action( 'plugins_loaded', array( $this, 'set_initial_settings' ) );
		}

		public function admin_enqueue_scripts() {
			if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'ri-wth-settings' ) {
				wp_enqueue_style( 'ri-wth-admin-style', plugin_dir_url( __FILE__ ) . 'admin/css/style.css' );
			}
		}

		public function maybe_enqueue_scripts() {
			if ( get_option( 'ri_wth_load_styles' ) ) {
				wp_register_style( 'ri-wth-style', plugin_dir_url( __FILE__ ) . 'public/css/style.css', array(), RI_WTH_PLUGIN_VERSION );
			}
			if ( get_option( 'ri_wth_load_scripts' ) ) {
				wp_register_script( 'ri-wth-script', plugin_dir_url( __FILE__ ) . 'public/js/script.js', array( 'jquery' ), RI_WTH_PLUGIN_VERSION, true );
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
			if ( RI_WTH_Functions::should_display_box() ) {
				if ( get_option( 'ri_wth_load_styles' ) ) {
					wp_enqueue_style( 'ri-wth-style' );
				}
				if ( get_option( 'ri_wth_load_scripts' ) ) {
					wp_enqueue_script( 'ri-wth-script' );
				}
			}
		}

		public function add_settings_link( $links ) {
			$url           = get_admin_url() . 'options-general.php?page=ri-wth-settings';
			$settings_link = '<a href="' . $url . '">' . esc_html( __( 'Settings', 'textdomain' ) ) . '</a>';
				$links[]   = $settings_link;
			return $links;
		}
	}
	RI_Was_This_Helpful::get_instance();
}
