<?php

defined( 'ABSPATH' ) || exit;



if ( ! class_exists( 'RIWTH_Was_This_Helpful' ) ) {
	final class RIWTH_Was_This_Helpful {

		public $version          = RIWTH_PLUGIN_VERSION;
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
			$this->define( 'RIWTH_DB_NAME', 'riwth_helpful_feedback' );
			$this->define( 'RIWTH_PLUGIN_DIR', plugin_dir_path( RIWTH_PLUGIN_FILE ) );
			$this->define( 'RIWTH_PLUGIN_URL', plugin_dir_url( RIWTH_PLUGIN_FILE ) );
			$this->define( 'RIWTH_PLUGIN_DIRNAME', dirname( RIWTH_PLUGIN_FILE ) . '/' );
		}

		private function includes() {
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-functions.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-settings.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-admin-columns.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-ajax.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-admin-bar.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-metabox.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-metabox-stats.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-box.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-user-role.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-shortcode.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-svg-icons.php';
			require_once RIWTH_PLUGIN_DIRNAME . 'includes/class-block.php';
		}

		private function init_hooks() {
			register_activation_hook( RIWTH_PLUGIN_FILE, array( $this, 'activate_plugin' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'init', array( $this, 'load_textdomain' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( RIWTH_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
		}

		public function init() {
			$user_role = new RIWTH_User_Role();
			new RIWTH_Settings();
			new RIWTH_Box();
			new RIWTH_Functions();
			new RIWTH_Shortcode();
			new RIWTH_Ajax();
			new RIWTH_Block();

			if ( $user_role->can_user_see_stats() ) {
				new RIWTH_Admin_Columns();
				new RIWTH_Admin_Bar();
				new RIWTH_Metabox();
				new RIWTH_Metabox_Stats();
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
			if ( ! class_exists( 'RIWTH_Was_This_Helpful_Pro' ) ) {
				load_plugin_textdomain( 'riwth-was-this-helpful', false, dirname( plugin_basename( RIWTH_PLUGIN_FILE ) ) . '/languages' );
			}
		}

		public function activate_plugin() {
			$this->create_database();
			$this->set_initial_settings();
		}

		public function create_database() {
			global $wpdb;

			require_once plugin_dir_path( RIWTH_PLUGIN_FILE ) . 'includes/class-settings.php';

			$table_name      = $wpdb->prefix . RIWTH_DB_NAME;
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
			$initial_settings = RIWTH_Settings::get_intial_settings();

			// set default initial settings
			foreach ( $initial_settings as $key => $value ) {
				if ( false === get_option( $key ) ) {
					add_option( $key, $value );
				}
			}
		}

		public function admin_enqueue_scripts() {
			if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'riwth-settings' ) {
				wp_enqueue_style( 'riwth-admin-style', RIWTH_PLUGIN_URL . 'assets/admin/css/style.css', RIWTH_PLUGIN_VERSION );
			}
		}

		public function maybe_enqueue_scripts() {
			if ( get_option( 'riwth_load_styles' ) ) {
				wp_register_style( 'riwth-style', RIWTH_PLUGIN_URL . 'assets/public/css/style.css', array(), RIWTH_PLUGIN_VERSION );
			}
			if ( get_option( 'riwth_load_scripts' ) ) {
				wp_register_script( 'riwth-script', RIWTH_PLUGIN_URL . 'assets/public/js/script.js', array( 'jquery' ), RIWTH_PLUGIN_VERSION, true );
				wp_localize_script(
					'riwth-script',
					'riwth_scripts',
					array(
						'ajax_url'   => admin_url( 'admin-ajax.php' ),
						'submitting' => esc_html( get_option( 'riwth_feedback_box_submitting_text' ) ),
						'postId'     => get_the_ID(),
					)
				);
			}
			if ( RIWTH_Functions::should_display_box() ) {
				if ( get_option( 'riwth_load_styles' ) ) {
					wp_enqueue_style( 'riwth-style' );
				}
				if ( get_option( 'riwth_load_scripts' ) ) {
					wp_enqueue_script( 'riwth-script' );
				}
			}
		}

		public function add_settings_link( $links ) {
			$url           = get_admin_url() . 'admin.php?page=riwth-settings';
			$settings_link = array( '<a href="' . esc_url( $url ) . '">' . esc_html( __( 'Settings', 'riwth-was-this-helpful' ) ) . '</a>' );
			return array_merge( $settings_link, $links );
		}
	}
}
