<?php

defined( 'ABSPATH' ) || exit;



if ( ! class_exists( 'RI_Was_This_Helpful' ) ) {
	final class RI_Was_This_Helpful {

		public $version          = RI_WTH_PLUGIN_VERSION;
		private static $instance = null;

		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();
		}

		public static function get_instance() {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Cloning is forbidden.
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html( 'Cloning is forbidden.' ), '1.0.0' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html( 'Unserializing instances of this class is forbidden.' ), '1.0.0' );
		}

		public function define_constants() {
			$this->define( 'RI_WTH_DB_NAME', 'ri_wth_helpful_feedback' );
			$this->define( 'RI_WTH_PLUGIN_DIR', plugin_dir_path( RI_WTH_PLUGIN_FILE ) );
			$this->define( 'RI_WTH_PLUGIN_URL', plugin_dir_url( RI_WTH_PLUGIN_FILE ) );
			$this->define( 'RI_WTH_PLUGIN_DIRNAME', dirname( RI_WTH_PLUGIN_FILE ) . '/' );
		}

		private function includes() {
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-functions.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-settings.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-admin-columns.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-ajax.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-admin-bar.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-metabox.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-metabox-stats.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-box.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-user-role.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-shortcode.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-svg-icons.php';
			require_once RI_WTH_PLUGIN_DIRNAME . 'includes/class-ri-wth-block.php';
		}

		private function init_hooks() {
			register_activation_hook( RI_WTH_PLUGIN_FILE, array( $this, 'activate_plugin' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'load_textdomain' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( RI_WTH_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		}

		public function init() {
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

		/**
		 * Define constant if not already set.
		 *
		 * @param string      $name  Constant name.
		 * @param string|bool $value Constant value.
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		public function load_textdomain() {
			if ( ! class_exists( 'RI_Was_This_Helpful_Pro' ) ) {
				load_plugin_textdomain( 'ri-was-this-helpful', false, dirname( plugin_basename( RI_WTH_PLUGIN_FILE ) ) . '/languages' );
			}
		}

		public function activate_plugin() {
			$this->create_database();
			$this->set_initial_settings();
		}

		public function create_database() {
			global $wpdb;

			require_once plugin_dir_path( RI_WTH_PLUGIN_FILE ) . 'includes/class-ri-wth-settings.php';

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
		}

		public function set_initial_settings() {
			$initial_settings = RI_WTH_Settings::get_intial_settings();

			// set default initial settings
			foreach ( $initial_settings as $key => $value ) {
				if ( false === get_option( $key ) ) {
					add_option( $key, $value );
				}
			}
		}

		public function admin_enqueue_scripts() {
			if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'ri-wth-settings' ) {
				wp_enqueue_style( 'ri-wth-admin-style', RI_WTH_PLUGIN_URL . 'admin/css/style.css', RI_WTH_PLUGIN_VERSION );
			}
		}

		public function maybe_enqueue_scripts() {
			if ( get_option( 'ri_wth_load_styles' ) ) {
				wp_register_style( 'ri-wth-style', RI_WTH_PLUGIN_URL . 'public/css/style.css', array(), RI_WTH_PLUGIN_VERSION );
			}
			if ( get_option( 'ri_wth_load_scripts' ) ) {
				wp_register_script( 'ri-wth-script', RI_WTH_PLUGIN_URL . 'public/js/script.js', array( 'jquery' ), RI_WTH_PLUGIN_VERSION, true );
				wp_localize_script(
					'ri-wth-script',
					'ri_wth_scripts',
					array(
						'ajax_url'   => admin_url( 'admin-ajax.php' ),
						'submitting' => esc_html( get_option( 'ri_wth_feedback_box_submitting_text' ) ),
						'postId'     => get_the_ID(),
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
			$url           = get_admin_url() . 'admin.php?page=ri-wth-settings';
			$settings_link = array( '<a href="' . esc_url( $url ) . '">' . esc_html( __( 'Settings', 'ri-was-this-helpful' ) ) . '</a>' );
			return array_merge( $settings_link, $links );
		}
	}
}
