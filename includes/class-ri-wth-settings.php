<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Settings' ) ) {
	class RI_WTH_Settings {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}

		public function add_settings_page() {
			add_options_page(
				__( 'Was This Helpful Settings', 'ri-was-this-helpful' ),
				__( 'Was This Helpful', 'ri-was-this-helpful' ),
				'manage_options',
				'ri-wth-settings',
				array( $this, 'render_settings_page' )
			);
		}

		public function render_settings_page() {
			?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Was This Helpful Settings', 'ri-was-this-helpful' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ri-wth-settings-group' );
				do_settings_sections( 'ri-wth-settings' );
				submit_button();
				?>
			</form>
		</div>
			<?php
		}

		public function register_settings() {
			register_setting( 'ri-wth-settings-group', 'ri_wth_display_on' );
			register_setting( 'ri-wth-settings-group', 'ri_wth_load_styles' );
			register_setting( 'ri-wth-settings-group', 'ri_wth_load_scripts' );
			register_setting( 'ri-wth-settings-group', 'ri_wth_show_admin_bar_content' );

			add_settings_section(
				'ri-wth-settings-section',
				__( 'RI Was This Helpful Settings', 'ri-was-this-helpful' ),
				array( $this, 'settings_section_callback' ),
				'ri-wth-settings'
			);

			add_settings_field(
				'ri_wth_display_on',
				__( 'Display on', 'ri-was-this-helpful' ),
				array( $this, 'display_on_callback' ),
				'ri-wth-settings',
				'ri-wth-settings-section'
			);

			add_settings_section(
				'ri-wth-load-settings-section',
				__( 'Load Settings', 'ri-was-this-helpful' ),
				array( $this, 'load_settings_section_callback' ),
				'ri-wth-settings'
			);

			add_settings_field(
				'ri_wth_load_styles',
				__( 'Load Styles', 'ri-was-this-helpful' ),
				array( $this, 'load_styles_callback' ),
				'ri-wth-settings',
				'ri-wth-load-settings-section'
			);

			add_settings_field(
				'ri_wth_load_scripts',
				__( 'Load Scripts', 'ri-was-this-helpful' ),
				array( $this, 'load_scripts_callback' ),
				'ri-wth-settings',
				'ri-wth-load-settings-section'
			);

			add_settings_section(
				'ri-wth-admin-bar-settings-section',
				__( 'Plugin Settings', 'ri-was-this-helpful' ),
				array( $this, 'admin_bar_settings_section_callback' ),
				'ri-wth-settings'
			);
			add_settings_field(
				'ri_wth_show_admin_bar_content',
				__( 'Show Admin Bar Content', 'ri-was-this-helpful' ),
				array( $this, 'show_admin_bar_content_callback' ),
				'ri-wth-settings',
				'ri-wth-admin-bar-settings-section'
			);
		}

		public function settings_section_callback() {
			echo '<p>' . esc_html__( 'Where do you want to show your Was this helpful box?', 'ri-was-this-helpful' ) . '</p>';
		}

		public function display_on_callback() {
			$options = get_option( 'ri_wth_display_on', array() );
			$options = is_array( $options ) ? $options : array();
			$fields  = array(
				array(
					'value' => 'post',
					'label' => __( 'Posts', 'ri-was-this-helpful' ),
				),
				array(
					'value' => 'page',
					'label' => __( 'Pages', 'ri-was-this-helpful' ),
				),
			);

			$fields = apply_filters( 'ri_wth_display_on_fields', $fields );

			foreach ( $fields as $field ) {
				?>
			<label>
				<input type="checkbox" name="ri_wth_display_on[]" value="<?php echo esc_attr( $field['value'] ); ?>" <?php checked( in_array( $field['value'], $options ) ); ?>>
				<?php echo esc_html( $field['label'] ); ?>
			</label><br>
				<?php
			}
		}





		public function load_settings_section_callback() {
			echo esc_html( __( 'Select whether to load the plugin styles and scripts.', 'ri-was-this-helpful' ) );
		}

		public function load_styles_callback() {
			$option = get_option( 'ri_wth_load_styles' );
			echo '<input type="checkbox" name="ri_wth_load_styles" value="1"' . checked( 1, $option, false ) . '>';
		}

		public function load_scripts_callback() {
			$option = get_option( 'ri_wth_load_scripts' );
			echo '<input type="checkbox" name="ri_wth_load_scripts" value="1"' . checked( 1, $option, false ) . '>';
		}

		public function admin_bar_settings_section_callback() {
			echo esc_html( __( 'Select whether to show the content in the admin bar.', 'ri-was-this-helpful' ) );
		}

		public function show_admin_bar_content_callback() {
			$option = get_option( 'ri_wth_show_admin_bar_content' );
			echo '<input type="checkbox" name="ri_wth_show_admin_bar_content" value="1" ' . checked( 1, $option, false ) . '>';
		}
	}
}
