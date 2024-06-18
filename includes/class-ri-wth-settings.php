<?php

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
		register_setting( 'ri-wth-settings-group', 'ri_wth_load_styles' );
		register_setting( 'ri-wth-settings-group', 'ri_wth_load_scripts' );

		add_settings_section(
			'ri-wth-settings-section',
			__( 'Load Settings', 'ri-was-this-helpful' ),
			array( $this, 'settings_section_callback' ),
			'ri-wth-settings'
		);

		add_settings_field(
			'ri_wth_load_styles',
			__( 'Load Styles', 'ri-was-this-helpful' ),
			array( $this, 'load_styles_callback' ),
			'ri-wth-settings',
			'ri-wth-settings-section'
		);

		add_settings_field(
			'ri_wth_load_scripts',
			__( 'Load Scripts', 'ri-was-this-helpful' ),
			array( $this, 'load_scripts_callback' ),
			'ri-wth-settings',
			'ri-wth-settings-section'
		);
	}

	public function settings_section_callback() {
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
}

new RI_WTH_Settings();
