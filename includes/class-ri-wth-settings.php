<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Settings' ) ) {
	class RI_WTH_Settings {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_pages' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'ri_wth_enqueue_color_picker' ) );
		}

		function ri_wth_enqueue_color_picker( $hook_suffix ) {
			if ( 'toplevel_page_ri-wth-settings' !== $hook_suffix ) {
				return;
			}
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'ri-wth-color-picker', RI_WTH_PLUGIN_URL . 'assets/src/admin/js/color-picker.js', array( 'wp-color-picker' ), RI_WTH_PLUGIN_VERSION, true );
		}


		public function add_settings_page() {
			add_menu_page(
				__( 'Was This Helpful Settings', 'ri-was-this-helpful' ),
				__( 'Was This Helpful', 'ri-was-this-helpful' ),
				'manage_options',
				'ri-wth-settings',
				array( $this, 'render_settings_page' ),
				'dashicons-thumbs-up'
			);
		}

		public function add_submenu_pages() {
			add_submenu_page(
				'ri-wth-settings',
				__( 'Shortcode', 'ri-was-this-helpful' ),
				__( 'Shortcode', 'ri-was-this-helpful' ),
				'manage_options',
				'ri-wth-shortcode',
				array( $this, 'render_shortcode_page' ),
			);
		}



		public function render_settings_page() {
			require_once RI_WTH_PLUGIN_DIR . 'templates/page-settings.php';
		}

		public function render_shortcode_page() {
			require_once RI_WTH_PLUGIN_DIR . 'templates/page-shortcode.php';
		}

		public function get_settings_section() {
			$settings_section = array(
				'ri-wth-settings-section'              => array(
					'title'    => __( 'Display on', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-general',
				),
				'ri-wth-admin-bar-settings-section'    => array(
					'title'    => __( 'Admin Bar', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'admin_bar_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-general',
				),
				'ri-wth-feedback-box-settings-section' => array(
					'title'    => __( 'Content', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
				),
				'ri-wth-feedback-box-colors-settings-section' => array(
					'title'    => __( 'Colors', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_colors_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
				),
				'ri-wth-feedback-box-styles-settings-section' => array(
					'title'    => __( 'Styles', 'ri-was-this-helpful' ),
					'callback' => null,
					'tab'      => 'ri-wth-settings-tab-feedback-box',
				),
				'ri-wth-load-settings-section'         => array(
					'title'    => __( 'Assets Loading', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'load_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-extra',
				),
				'ri-wth-uninstall-settings-section'    => array(
					'title'    => __( 'Data Deletion', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'uninstall_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-extra',
				),
				'ri-wth-feedback-box-other-steps-settings-section' => array(
					'title'    => __( 'Submitting and Thanks Content', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_other_steps_settings_section_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
				),
			);
			return $settings_section;
		}

		public function get_settings_field() {

			$settings_field = array(
				'ri_wth_display_on'                        => array(
					'title'    => __( 'Display on', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'display_on_callback' ),
					'tab'      => 'ri-wth-settings-tab-general',
					'section'  => 'ri-wth-settings-section',
				),
				'ri_wth_display_by_user_role'              => array(
					'title'    => __( 'Display Stats and Functionalities by User Role', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'display_by_user_role_callback' ),
					'tab'      => 'ri-wth-settings-tab-general',
					'section'  => 'ri-wth-settings-section',
				),
				'ri_wth_load_styles'                       => array(
					'title'    => __( 'Load Styles', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'ri-wth-settings-tab-extra',
					'section'  => 'ri-wth-load-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'ri_wth_load_styles',
					),
				),
				'ri_wth_load_scripts'                      => array(
					'title'    => __( 'Load Scripts', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'ri-wth-settings-tab-extra',
					'section'  => 'ri-wth-load-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'ri_wth_load_scripts',
					),
				),
				'ri_wth_show_admin_bar_content'            => array(
					'title'    => __( 'Show Admin Bar Content', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'ri-wth-settings-tab-general',
					'section'  => 'ri-wth-admin-bar-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'ri_wth_show_admin_bar_content',
					),
				),
				'ri_wth_feedback_box_text'                 => array(
					'title'    => __( 'Feedback Box Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'ri_wth_feedback_box_text',
					),
				),
				'ri_wth_feedback_box_positive_button_text' => array(
					'title'    => __( 'Positive Button Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'ri_wth_feedback_box_positive_button_text',
					),
				),
				'ri_wth_feedback_box_positive_button_text' => array(
					'title'    => __( 'Positive Button Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array(
						'type'        => 'text',
						'name'        => 'ri_wth_feedback_box_positive_button_text',
						'description' => __( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ),
					),
				),
				'ri_wth_feedback_box_positive_button_icon' => array(
					'title'    => __( 'Positive Button Icon', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_positive_button_icon_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array( 'class' => 'radio' ),
				),
				'ri_wth_feedback_box_negative_button_text' => array(
					'title'    => __( 'Negative Button Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array(
						'type'        => 'text',
						'name'        => 'ri_wth_feedback_box_negative_button_text',
						'description' => __( 'Leave empty if you don\'t want to display text', 'ri-was-this-helpful' ),
					),
				),
				'ri_wth_feedback_box_negative_button_icon' => array(
					'title'    => __( 'Negative Button Icon', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_negative_button_icon_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-settings-section',
					'args'     => array( 'class' => 'radio' ),
				),
				'ri_wth_feedback_box_color_background'     => array(
					'title'    => __( 'Background Color', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_background_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'ri_wth_feedback_box_color_positive_button' => array(
					'title'    => __( 'Positive Button Color', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_positive_button_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'ri_wth_feedback_box_color_positive_text'  => array(
					'title'    => __( 'Positive Text/Icon Color', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_positive_text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'ri_wth_feedback_box_color_negative_button' => array(
					'title'    => __( 'Negative Button Color', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_negative_button_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'ri_wth_feedback_box_color_negative_text'  => array(
					'title'    => __( 'Negative Text/Icon Color', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_negative_text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'ri_wth_feedback_box_border_button_rounded' => array(
					'title'    => __( 'Button Border Radius', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_border_button_rounded_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-styles-settings-section',
				),
				'ri_wth_uninstall_remove_data'             => array(
					'title'    => __( 'Delete data when removing plugin?', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'ri-wth-settings-tab-extra',
					'section'  => 'ri-wth-uninstall-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'ri_wth_uninstall_remove_data',
					),
				),
				'ri_wth_feedback_box_submitting_text'      => array(
					'title'    => __( 'Submitting Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-other-steps-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'ri_wth_feedback_box_submitting_text',
					),
				),
				'ri_wth_feedback_box_thanks_text'          => array(
					'title'    => __( 'Thank You Text', 'ri-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'ri-wth-settings-tab-feedback-box',
					'section'  => 'ri-wth-feedback-box-other-steps-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'ri_wth_feedback_box_thanks_text',
					),
				),
			);

			return $settings_field;
		}


		public function register_settings() {

			$settings_section = $this->get_settings_section();
			$settings_field   = $this->get_settings_field();

			foreach ( $settings_section as $key => $value ) {

				add_settings_section(
					$key,
					esc_html( $value['title'] ),
					$value['callback'],
					$value['tab']
				);
			}

			foreach ( $settings_field as $key => $value ) {
				register_setting( $value['tab'], $key );

				add_settings_field(
					$key,
					esc_html( $value['title'] ),
					$value['callback'],
					$value['tab'],
					$value['section'],
					isset( $value['args'] ) ? $value['args'] : ''
				);
			}
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

		public function checkbox_callback( $args ) {
			$option = get_option( $args['name'] );
			echo '<input type="checkbox" name="' . esc_attr( $args['name'] ) . '" value="1"' . checked( 1, $option, false ) . '>';
		}


		public function admin_bar_settings_section_callback() {
			esc_html_e( 'Select whether to show the content in the admin bar.', 'ri-was-this-helpful' );
		}


		public function feedback_box_settings_section_callback() {
			esc_html_e( 'Change feedback box content.', 'ri-was-this-helpful' );
		}
		public function feedback_box_other_steps_settings_section_callback() {
			esc_html_e( 'Change feedback box content  for submitting and thank you messages.', 'ri-was-this-helpful' );
		}

		public function text_callback( $args ) {
			$option = get_option( $args['name'] );
			echo '<input type="text" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $option ) . '">';
			if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
				echo '<p class=""description"">' . esc_html( $args['description'] ) . '</p>';
			}
		}

		public function feedback_box_positive_button_icon_callback() {
			$option           = get_option( 'ri_wth_feedback_box_positive_button_icon' );
			$svg_allowed_html = RI_WTH_Functions::get_svg_allowed_html();
			$svg_icons        = RI_WTH_SVG_Icons::get_svg_positive_icons();
			$svg_icons        = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_positive_button_icon" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo wp_kses( $icon, $svg_allowed_html ); ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_icon_callback() {
			$option           = get_option( 'ri_wth_feedback_box_negative_button_icon' );
			$svg_allowed_html = RI_WTH_Functions::get_svg_allowed_html();
			$svg_icons        = RI_WTH_SVG_Icons::get_svg_negative_icons();
			$svg_icons        = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'ri-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="ri_wth_feedback_box_negative_button_icon" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo wp_kses( $icon, $svg_allowed_html ); ?>	
				</label>
				<?php
			}
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

			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
				// clear transient box
				delete_transient( 'ri_wth_feedback_box' );
			}

			echo '<input type="number" min="0" max="100" id="ri_wth_feedback_box_border_button_rounded" name="ri_wth_feedback_box_border_button_rounded" value="' . esc_attr( $option ) . '" />%';
		}


		public function uninstall_settings_section_callback() {
			echo esc_html( __( 'Deletes all data when plugin is removed.', 'ri-was-this-helpful' ) );
		}


		public static function get_intial_settings() {
			$initial_settings = array(
				'ri_wth_display_on'                        => array( 'post' ),
				'ri_wth_display_by_user_role'              => array( 'administrator', 'editor' ),
				'ri_wth_load_styles'                       => 1,
				'ri_wth_load_scripts'                      => 1,
				'ri_wth_show_admin_bar_content'            => 1,
				'ri_wth_feedback_box_template'             => 'default',
				'ri_wth_feedback_box_text'                 => __( 'Was This Helpful?', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_positive_button_text' => __( 'Yes', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_positive_button_icon' => 'thumbs-up',
				'ri_wth_feedback_box_negative_button_text' => __( 'No', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_negative_button_icon' => 'thumbs-down',
				'ri_wth_feedback_box_color_background'     => '#f4f4f5',
				'ri_wth_feedback_box_color_positive_button' => '#ffffff',
				'ri_wth_feedback_box_color_positive_text'  => '#444444',
				'ri_wth_feedback_box_color_negative_button' => '#ffffff',
				'ri_wth_feedback_box_color_negative_text'  => '#444444',
				'ri_wth_feedback_box_border_button_rounded' => '8',
				'ri_wth_uninstall_remove_data'             => 1,
				'ri_wth_feedback_box_submitting_text'      => __( '⏳ Submitting...', 'ri-was-this-helpful' ),
				'ri_wth_feedback_box_thanks_text'          => __( '✅ Thank you for your feedback!', 'ri-was-this-helpful' ),
			);
			return $initial_settings;
		}
	}
}
