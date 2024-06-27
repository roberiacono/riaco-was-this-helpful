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
			require_once RI_WTH_PLUGIN_DIR . 'templates/page-settings.php';
		}


		public function register_settings() {
			register_setting( 'ri-wth-tab-general-settings-group', 'ri_wth_display_on' );
			register_setting( 'ri-wth-tab-general-settings-group', 'ri_wth_display_by_user_role' );
			register_setting( 'ri-wth-tab-general-settings-group', 'ri_wth_load_styles' );
			register_setting( 'ri-wth-tab-general-settings-group', 'ri_wth_load_scripts' );
			register_setting( 'ri-wth-tab-general-settings-group', 'ri_wth_show_admin_bar_content' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_text' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_positive_button_text' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_positive_button_icon' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_negative_button_text' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_negative_button_icon' );

			add_settings_section(
				'ri-wth-settings-section',
				__( 'Display on', 'ri-was-this-helpful' ),
				array( $this, 'settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);

			add_settings_field(
				'ri_wth_display_on',
				__( 'Display on', 'ri-was-this-helpful' ),
				array( $this, 'display_on_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-settings-section'
			);

			add_settings_field(
				'ri_wth_display_by_user_role',
				__( 'Display Stats and Functionalities by User Role', 'ri-was-this-helpful' ),
				array( $this, 'display_by_user_role_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-settings-section'
			);

			add_settings_section(
				'ri-wth-load-settings-section',
				__( 'Assets Loading', 'ri-was-this-helpful' ),
				array( $this, 'load_settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);

			add_settings_field(
				'ri_wth_load_styles',
				__( 'Load Styles', 'ri-was-this-helpful' ),
				array( $this, 'load_styles_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-load-settings-section'
			);

			add_settings_field(
				'ri_wth_load_scripts',
				__( 'Load Scripts', 'ri-was-this-helpful' ),
				array( $this, 'load_scripts_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-load-settings-section'
			);

			add_settings_section(
				'ri-wth-admin-bar-settings-section',
				__( 'Admin Bar', 'ri-was-this-helpful' ),
				array( $this, 'admin_bar_settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);
			add_settings_field(
				'ri_wth_show_admin_bar_content',
				__( 'Show Admin Bar Content', 'ri-was-this-helpful' ),
				array( $this, 'show_admin_bar_content_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-admin-bar-settings-section'
			);

			add_settings_section(
				'ri-wth-feedback-box-settings-section',
				__( 'Feedback Box', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_settings_section_callback' ),
				'ri-wth-settings-tab-feedback-box'
			);
			add_settings_field(
				'ri_wth_feedback_box_text',
				__( 'Feedback Box Text', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);
			add_settings_field(
				'ri_wth_feedback_box_positive_button_text',
				__( 'Positive Button Text', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_positive_button_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);
			add_settings_field(
				'ri_wth_feedback_box_positive_button_icon',
				__( 'Positive Button Icon', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_positive_button_icon_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section',
				array( 'class' => 'radio' )
			);
			add_settings_field(
				'ri_wth_feedback_box_negative_button_text',
				__( 'Negative Button Text', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_negative_button_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);

			add_settings_field(
				'ri_wth_feedback_box_negative_button_icon',
				__( 'Negative Button Icon', 'ri-was-this-helpful' ),
				array( $this, 'feedback_box_negative_button_icon_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section',
				array( 'class' => 'radio' )
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

		public function display_by_user_role_callback() {
			global $wp_roles;
			$options = get_option( 'ri_wth_display_by_user_role' );
			$options = is_array( $options ) ? $options : array();

			$all_roles = $wp_roles->roles;

			foreach ( $all_roles as $key => $value ) {
				?>
			<label>
				<input type="checkbox" name="ri_wth_display_by_user_role[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $options ) ); ?>>
				<?php echo esc_html( $value['name'] ); ?>
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
			echo __( 'Select whether to show the content in the admin bar.', 'ri-was-this-helpful' );
		}

		public function show_admin_bar_content_callback() {
			$option = get_option( 'ri_wth_show_admin_bar_content' );
			echo '<input type="checkbox" name="ri_wth_show_admin_bar_content" value="1" ' . checked( 1, $option, false ) . '>';
		}

		public function feedback_box_settings_section_callback() {
			echo __( 'Style and change content on your feedback box.', 'ri-was-this-helpful' );
		}

		public function feedback_box_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_text' );
			echo '<input type="text" name="ri_wth_feedback_box_text" value="' . esc_attr( $option ) . '">';
		}

		public function feedback_box_positive_button_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_positive_button_text' );
			echo '<input type="text" name="ri_wth_feedback_box_positive_button_text" value="' . esc_attr( $option ) . '">';
			echo '<p class=""description"">' . __( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ) . '</p>';
		}

		public function feedback_box_positive_button_icon_callback() {
			$option    = get_option( 'ri_wth_feedback_box_positive_button_icon' );
			$svg_icons = RI_WTH_SVG_Icons::get_svg_positive_icons();
			$svg_icons = array_merge( $svg_icons, array( 'empty' => __( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_positive_button_icon" value="<?php esc_attr_e( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo $icon; ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_icon_callback() {
			$option    = get_option( 'ri_wth_feedback_box_negative_button_icon' );
			$svg_icons = RI_WTH_SVG_Icons::get_svg_negative_icons();
			$svg_icons = array_merge( $svg_icons, array( 'empty' => __( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_negative_button_icon" value="<?php esc_attr_e( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo $icon; ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_negative_button_text' );
			echo '<input type="text" name="ri_wth_feedback_box_negative_button_text" value="' . esc_attr( $option ) . '">';
			echo '<p class=""description"">' . esc_html( __( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ) ) . '</p>';
		}
	}
}
