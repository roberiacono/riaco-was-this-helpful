<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Settings' ) ) {
	class RI_WTH_Settings {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ri_wth_enqueue_color_picker' ) );
		}

		function ri_wth_enqueue_color_picker( $hook_suffix ) {

			if ( 'settings_page_ri-wth-settings' !== $hook_suffix ) {
				return;
			}
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'ri-wth-color-picker', RI_WTH_PLUGIN_URL . 'admin/js/color-picker.js', array( 'wp-color-picker' ), false, true );
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
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_color_background' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_color_positive_button' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_color_positive_text' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_color_negative_button' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_color_negative_text' );
			register_setting( 'ri-wth-tab-feedback-box-settings-group', 'ri_wth_feedback_box_border_button_rounded' );

			add_settings_section(
				'ri-wth-settings-section',
				esc_html( __( 'Display on', 'ri-was-this-helpful' ) ),
				array( $this, 'settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);

			add_settings_field(
				'ri_wth_display_on',
				esc_html( __( 'Display on', 'ri-was-this-helpful' ) ),
				array( $this, 'display_on_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-settings-section'
			);

			add_settings_field(
				'ri_wth_display_by_user_role',
				esc_html( __( 'Display Stats and Functionalities by User Role', 'ri-was-this-helpful' ) ),
				array( $this, 'display_by_user_role_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-settings-section'
			);

			add_settings_section(
				'ri-wth-load-settings-section',
				esc_html( __( 'Assets Loading', 'ri-was-this-helpful' ) ),
				array( $this, 'load_settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);

			add_settings_field(
				'ri_wth_load_styles',
				esc_html( __( 'Load Styles', 'ri-was-this-helpful' ) ),
				array( $this, 'load_styles_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-load-settings-section'
			);

			add_settings_field(
				'ri_wth_load_scripts',
				esc_html( __( 'Load Scripts', 'ri-was-this-helpful' ) ),
				array( $this, 'load_scripts_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-load-settings-section'
			);

			add_settings_section(
				'ri-wth-admin-bar-settings-section',
				esc_html( __( 'Admin Bar', 'ri-was-this-helpful' ) ),
				array( $this, 'admin_bar_settings_section_callback' ),
				'ri-wth-settings-tab-general'
			);
			add_settings_field(
				'ri_wth_show_admin_bar_content',
				esc_html( __( 'Show Admin Bar Content', 'ri-was-this-helpful' ) ),
				array( $this, 'show_admin_bar_content_callback' ),
				'ri-wth-settings-tab-general',
				'ri-wth-admin-bar-settings-section'
			);

			add_settings_section(
				'ri-wth-feedback-box-settings-section',
				esc_html( __( 'Content', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_settings_section_callback' ),
				'ri-wth-settings-tab-feedback-box'
			);
			add_settings_field(
				'ri_wth_feedback_box_text',
				esc_html( __( 'Feedback Box Text', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);
			add_settings_field(
				'ri_wth_feedback_box_positive_button_text',
				esc_html( __( 'Positive Button Text', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_positive_button_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);
			add_settings_field(
				'ri_wth_feedback_box_positive_button_icon',
				esc_html( __( 'Positive Button Icon', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_positive_button_icon_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section',
				array( 'class' => 'radio' )
			);
			add_settings_field(
				'ri_wth_feedback_box_negative_button_text',
				esc_html( __( 'Negative Button Text', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_negative_button_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section'
			);

			add_settings_field(
				'ri_wth_feedback_box_negative_button_icon',
				esc_html( __( 'Negative Button Icon', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_negative_button_icon_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-settings-section',
				array( 'class' => 'radio' )
			);

			add_settings_section(
				'ri-wth-feedback-box-colors-settings-section',
				esc_html( __( 'Colors', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_colors_settings_section_callback' ),
				'ri-wth-settings-tab-feedback-box'
			);

			add_settings_field(
				'ri_wth_feedback_box_color_background',
				esc_html( __( 'Background Color', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_color_background_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-colors-settings-section',
				array( 'class' => 'color' )
			);

			add_settings_field(
				'ri_wth_feedback_box_color_positive_button',
				esc_html( __( 'Positive Button Color', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_color_positive_button_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-colors-settings-section',
				array( 'class' => 'color' )
			);

			add_settings_field(
				'ri_wth_feedback_box_color_positive_text',
				esc_html( __( 'Positive Text/Icon Color', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_color_positive_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-colors-settings-section',
				array( 'class' => 'color' )
			);

			add_settings_field(
				'ri_wth_feedback_box_color_negative_button',
				esc_html( __( 'Negative Button Color', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_color_negative_button_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-colors-settings-section',
				array( 'class' => 'color' )
			);

			add_settings_field(
				'ri_wth_feedback_box_color_negative_text',
				esc_html( __( 'Negative Text/Icon Color', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_color_negative_text_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-colors-settings-section',
				array( 'class' => 'color' )
			);

			add_settings_section(
				'ri-wth-feedback-box-styles-settings-section',
				esc_html( __( 'Styles', 'ri-was-this-helpful' ) ),
				null,
				'ri-wth-settings-tab-feedback-box'
			);

			add_settings_field(
				'ri_wth_feedback_box_border_button_rounded',
				esc_html( __( 'Button Border Radius', 'ri-was-this-helpful' ) ),
				array( $this, 'feedback_box_border_button_rounded_callback' ),
				'ri-wth-settings-tab-feedback-box',
				'ri-wth-feedback-box-styles-settings-section'
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
					'label' => esc_html__( 'Posts', 'ri-was-this-helpful' ),
				),
				array(
					'value' => 'page',
					'label' => esc_html__( 'Pages', 'ri-was-this-helpful' ),
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
			esc_html_e( 'Select whether to show the content in the admin bar.', 'ri-was-this-helpful' );
		}

		public function show_admin_bar_content_callback() {
			$option = get_option( 'ri_wth_show_admin_bar_content' );
			echo '<input type="checkbox" name="ri_wth_show_admin_bar_content" value="1" ' . checked( 1, $option, false ) . '>';
		}


		public function feedback_box_settings_section_callback() {
			esc_html_e( 'Change feedback box content.', 'ri-was-this-helpful' );
		}

		public function feedback_box_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_text' );
			echo '<input type="text" name="ri_wth_feedback_box_text" value="' . esc_attr( $option ) . '">';
		}

		public function feedback_box_positive_button_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_positive_button_text' );
			echo '<input type="text" name="ri_wth_feedback_box_positive_button_text" value="' . esc_attr( $option ) . '">';
			echo '<p class=""description"">' . esc_html__( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ) . '</p>';
		}

		public function feedback_box_positive_button_icon_callback() {
			$option    = get_option( 'ri_wth_feedback_box_positive_button_icon' );
			$svg_icons = RI_WTH_SVG_Icons::get_svg_positive_icons();
			$svg_icons = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_positive_button_icon" value="<?php esc_attr_e( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo RI_WTH_Functions::sanitize_svg( $icon ); ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_icon_callback() {
			$option    = get_option( 'ri_wth_feedback_box_negative_button_icon' );
			$svg_icons = RI_WTH_SVG_Icons::get_svg_negative_icons();
			$svg_icons = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_negative_button_icon" value="<?php esc_attr_e( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo RI_WTH_Functions::sanitize_svg( $icon ); ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_text_callback() {
			$option = get_option( 'ri_wth_feedback_box_negative_button_text' );
			echo '<input type="text" name="ri_wth_feedback_box_negative_button_text" value="' . esc_attr( $option ) . '">';
			echo '<p class=""description"">' . esc_html( __( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ) ) . '</p>';
		}


		public function feedback_box_colors_settings_section_callback() {
			echo esc_html__( 'Style your feedback box.', 'ri-was-this-helpful' );
		}

		public function feedback_box_color_background_callback() {
			$option           = get_option( 'ri_wth_feedback_box_color_background' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="ri_wth_feedback_box_color_background" name="ri_wth_feedback_box_color_background" value="' . esc_attr( $option ) . '" class="ri-wth-color-field" data-default-color="' . esc_attr( $initial_settings['ri_wth_feedback_box_color_background'] ) . '" />';
		}

		public function feedback_box_color_positive_button_callback() {
			$option           = get_option( 'ri_wth_feedback_box_color_positive_button' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="ri_wth_feedback_box_color_positive_button" name="ri_wth_feedback_box_color_positive_button" value="' . esc_attr( $option ) . '" class="ri-wth-color-field" data-default-color="' . esc_attr( $initial_settings['ri_wth_feedback_box_color_positive_button'] ) . '" />';
		}

		public function feedback_box_color_positive_text_callback() {
			$option           = get_option( 'ri_wth_feedback_box_color_positive_text' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="ri_wth_feedback_box_color_positive_text" name="ri_wth_feedback_box_color_positive_text" value="' . esc_attr( $option ) . '" class="ri-wth-color-field" data-default-color="' . esc_attr( $initial_settings['ri_wth_feedback_box_color_positive_text'] ) . '" />';
		}

		public function feedback_box_color_negative_button_callback() {
			$option           = get_option( 'ri_wth_feedback_box_color_negative_button' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="ri_wth_feedback_box_color_negative_button" name="ri_wth_feedback_box_color_negative_button" value="' . esc_attr( $option ) . '" class="ri-wth-color-field" data-default-color="' . esc_attr( $initial_settings['ri_wth_feedback_box_color_negative_button'] ) . '" />';
		}

		public function feedback_box_color_negative_text_callback() {
			$option           = get_option( 'ri_wth_feedback_box_color_negative_text' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="ri_wth_feedback_box_color_negative_text" name="ri_wth_feedback_box_color_negative_text" value="' . esc_attr( $option ) . '" class="ri-wth-color-field" data-default-color="' . esc_attr( $initial_settings['ri_wth_feedback_box_color_negative_text'] ) . '" />';
		}


		public function feedback_box_border_button_rounded_callback() {
			$option           = get_option( 'ri_wth_feedback_box_border_button_rounded' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="number" min="0" max="100" id="ri_wth_feedback_box_border_button_rounded" name="ri_wth_feedback_box_border_button_rounded" value="' . esc_attr( $option ) . '" />%';
		}




		public static function get_intial_settings() {
			$initial_settings = array(
				'ri_wth_display_on'                        => array( 'post' ),
				'ri_wth_display_by_user_role'              => array( 'administrator', 'editor' ),
				'ri_wth_load_styles'                       => 1,
				'ri_wth_load_scripts'                      => 1,
				'ri_wth_show_admin_bar_content'            => 1,
				'ri_wth_feedback_box_template'             => 'default',
				'ri_wth_feedback_box_text'                 => esc_html__( 'Was This Helpful?', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_positive_button_text' => esc_html__( 'Yes', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_positive_button_icon' => 'thumbs-up',
				'ri_wth_feedback_box_negative_button_text' => esc_html( __( 'No', 'ri-was-this-helpful' ) ),
				'ri_wth_feedback_box_negative_button_icon' => 'thumbs-down',
				'ri_wth_feedback_box_color_background'     => '#f4f4f5',
				'ri_wth_feedback_box_color_positive_button' => '#ffffff',
				'ri_wth_feedback_box_color_positive_text'  => '#444444',
				'ri_wth_feedback_box_color_negative_button' => '#ffffff',
				'ri_wth_feedback_box_color_negative_text'  => '#444444',
				'ri_wth_feedback_box_border_button_rounded' => '8',
			);
			return $initial_settings;
		}
	}
}
