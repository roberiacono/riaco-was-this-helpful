<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Settings' ) ) {
	class RIWTH_Settings {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_pages' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'riwth_enqueue_color_picker' ) );
		}

		function riwth_enqueue_color_picker( $hook_suffix ) {
			if ( 'toplevel_page_riwth-settings' !== $hook_suffix ) {
				return;
			}
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'riwth-color-picker', RIWTH_PLUGIN_URL . 'assets/admin/js/color-picker.js', array( 'wp-color-picker' ), RIWTH_PLUGIN_VERSION, true );
		}


		public function add_settings_page() {
			add_menu_page(
				__( 'Was This Helpful Settings', 'riwth-was-this-helpful' ),
				__( 'Was This Helpful', 'riwth-was-this-helpful' ),
				'manage_options',
				'riwth-settings',
				array( $this, 'render_settings_page' ),
				'dashicons-thumbs-up'
			);
		}

		public function add_submenu_pages() {
			add_submenu_page(
				'riwth-settings',
				__( 'Shortcode', 'riwth-was-this-helpful' ),
				__( 'Shortcode', 'riwth-was-this-helpful' ),
				'manage_options',
				'riwth-shortcode',
				array( $this, 'render_shortcode_page' ),
			);
		}



		public function render_settings_page() {
			require_once RIWTH_PLUGIN_DIR . 'templates/page-settings.php';
		}

		public function render_shortcode_page() {
			require_once RIWTH_PLUGIN_DIR . 'templates/page-shortcode.php';
		}

		public function get_settings_section() {
			$settings_section = array(
				'riwth-settings-section'              => array(
					'title'    => __( 'Display on', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-general',
				),
				'riwth-admin-bar-settings-section'    => array(
					'title'    => __( 'Admin Bar', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'admin_bar_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-general',
				),
				'riwth-feedback-box-settings-section' => array(
					'title'    => __( 'Content', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
				),
				'riwth-feedback-box-colors-settings-section' => array(
					'title'    => __( 'Colors', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_colors_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
				),
				'riwth-feedback-box-styles-settings-section' => array(
					'title'    => __( 'Styles', 'riwth-was-this-helpful' ),
					'callback' => null,
					'tab'      => 'riwth-settings-tab-feedback-box',
				),
				'riwth-load-settings-section'         => array(
					'title'    => __( 'Assets Loading', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'load_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-extra',
				),
				'riwth-uninstall-settings-section'    => array(
					'title'    => __( 'Data Deletion', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'uninstall_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-extra',
				),
				'riwth-feedback-box-other-steps-settings-section' => array(
					'title'    => __( 'Submitting and Thanks Content', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_other_steps_settings_section_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
				),
			);
			return $settings_section;
		}

		public function get_settings_field() {

			$settings_field = array(
				'riwth_display_on'                         => array(
					'title'    => __( 'Display on', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'display_on_callback' ),
					'tab'      => 'riwth-settings-tab-general',
					'section'  => 'riwth-settings-section',
				),
				'riwth_display_by_user_role'               => array(
					'title'    => __( 'Display Stats and Functionalities by User Role', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'display_by_user_role_callback' ),
					'tab'      => 'riwth-settings-tab-general',
					'section'  => 'riwth-settings-section',
				),
				'riwth_load_styles'                        => array(
					'title'    => __( 'Load Styles', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'riwth-settings-tab-extra',
					'section'  => 'riwth-load-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'riwth_load_styles',
					),
				),
				'riwth_load_scripts'                       => array(
					'title'    => __( 'Load Scripts', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'riwth-settings-tab-extra',
					'section'  => 'riwth-load-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'riwth_load_scripts',
					),
				),
				'riwth_show_admin_bar_content'             => array(
					'title'    => __( 'Show Admin Bar Content', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'riwth-settings-tab-general',
					'section'  => 'riwth-admin-bar-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'riwth_show_admin_bar_content',
					),
				),
				'riwth_feedback_box_text'                  => array(
					'title'    => __( 'Feedback Box Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'riwth_feedback_box_text',
					),
				),
				'riwth_feedback_box_positive_button_text'  => array(
					'title'    => __( 'Positive Button Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'riwth_feedback_box_positive_button_text',
					),
				),
				'riwth_feedback_box_positive_button_text'  => array(
					'title'    => __( 'Positive Button Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array(
						'type'        => 'text',
						'name'        => 'riwth_feedback_box_positive_button_text',
						'description' => __( 'Leave empty if you don\'t want to display text', 'riwth-was-this-helpful' ),
					),
				),
				'riwth_feedback_box_positive_button_icon'  => array(
					'title'    => __( 'Positive Button Icon', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_positive_button_icon_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array( 'class' => 'radio' ),
				),
				'riwth_feedback_box_negative_button_text'  => array(
					'title'    => __( 'Negative Button Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array(
						'type'        => 'text',
						'name'        => 'riwth_feedback_box_negative_button_text',
						'description' => __( 'Leave empty if you don\'t want to display text', 'riwth-was-this-helpful' ),
					),
				),
				'riwth_feedback_box_negative_button_icon'  => array(
					'title'    => __( 'Negative Button Icon', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_negative_button_icon_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-settings-section',
					'args'     => array( 'class' => 'radio' ),
				),
				'riwth_feedback_box_color_background'      => array(
					'title'    => __( 'Background Color', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_background_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'riwth_feedback_box_color_positive_button' => array(
					'title'    => __( 'Positive Button Color', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_positive_button_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'riwth_feedback_box_color_positive_text'   => array(
					'title'    => __( 'Positive Text/Icon Color', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_positive_text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'riwth_feedback_box_color_negative_button' => array(
					'title'    => __( 'Negative Button Color', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_negative_button_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'riwth_feedback_box_color_negative_text'   => array(
					'title'    => __( 'Negative Text/Icon Color', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_color_negative_text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-colors-settings-section',
					'args'     => array( 'class' => 'color' ),
				),
				'riwth_feedback_box_border_button_rounded' => array(
					'title'    => __( 'Button Border Radius', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'feedback_box_border_button_rounded_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-styles-settings-section',
				),
				'riwth_uninstall_remove_data'              => array(
					'title'    => __( 'Delete data when removing plugin?', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'checkbox_callback' ),
					'tab'      => 'riwth-settings-tab-extra',
					'section'  => 'riwth-uninstall-settings-section',
					'args'     => array(
						'type' => 'checkbox',
						'name' => 'riwth_uninstall_remove_data',
					),
				),
				'riwth_feedback_box_submitting_text'       => array(
					'title'    => __( 'Submitting Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-other-steps-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'riwth_feedback_box_submitting_text',
					),
				),
				'riwth_feedback_box_thanks_text'           => array(
					'title'    => __( 'Thank You Text', 'riwth-was-this-helpful' ),
					'callback' => array( $this, 'text_callback' ),
					'tab'      => 'riwth-settings-tab-feedback-box',
					'section'  => 'riwth-feedback-box-other-steps-settings-section',
					'args'     => array(
						'type' => 'text',
						'name' => 'riwth_feedback_box_thanks_text',
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
			echo '<p>' . esc_html__( 'Where do you want to show your Was this helpful box?', 'riwth-was-this-helpful' ) . '</p>';
		}

		public function display_on_callback() {
			$options = get_option( 'riwth_display_on', array() );
			$options = is_array( $options ) ? $options : array();
			$fields  = array(
				array(
					'value' => 'post',
					'label' => esc_html__( 'Posts', 'riwth-was-this-helpful' ),
				),
				array(
					'value' => 'page',
					'label' => esc_html__( 'Pages', 'riwth-was-this-helpful' ),
				),
			);

			$fields = apply_filters( 'riwth_display_on_fields', $fields );

			foreach ( $fields as $field ) {
				?>
			<label>
				<input type="checkbox" name="riwth_display_on[]" value="<?php echo esc_attr( $field['value'] ); ?>" <?php checked( in_array( $field['value'], $options ) ); ?>>
				<?php echo esc_html( $field['label'] ); ?>
			</label><br>
				<?php
			}
		}

		public function display_by_user_role_callback() {
			global $wp_roles;
			$options = get_option( 'riwth_display_by_user_role' );
			$options = is_array( $options ) ? $options : array();

			$all_roles = $wp_roles->roles;

			foreach ( $all_roles as $key => $value ) {
				?>
			<label>
				<input type="checkbox" name="riwth_display_by_user_role[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $options ) ); ?>>
				<?php echo esc_html( $value['name'] ); ?>
			</label><br>
				<?php
			}
		}


		public function load_settings_section_callback() {
			echo esc_html( __( 'Select whether to load the plugin styles and scripts.', 'riwth-was-this-helpful' ) );
		}

		public function checkbox_callback( $args ) {
			$option = get_option( $args['name'] );
			echo '<input type="checkbox" name="' . esc_attr( $args['name'] ) . '" value="1"' . checked( 1, $option, false ) . '>';
		}


		public function admin_bar_settings_section_callback() {
			esc_html_e( 'Select whether to show the content in the admin bar.', 'riwth-was-this-helpful' );
		}


		public function feedback_box_settings_section_callback() {
			esc_html_e( 'Change feedback box content.', 'riwth-was-this-helpful' );
		}
		public function feedback_box_other_steps_settings_section_callback() {
			esc_html_e( 'Change feedback box content  for submitting and thank you messages.', 'riwth-was-this-helpful' );
		}

		public function text_callback( $args ) {
			$option = get_option( $args['name'] );
			echo '<input type="text" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $option ) . '">';
			if ( isset( $args['description'] ) && ! empty( $args['description'] ) ) {
				echo '<p class=""description"">' . esc_html( $args['description'] ) . '</p>';
			}
		}

		public function feedback_box_positive_button_icon_callback() {
			$option           = get_option( 'riwth_feedback_box_positive_button_icon' );
			$svg_allowed_html = RIWTH_Functions::get_svg_allowed_html();
			$svg_icons        = RIWTH_SVG_Icons::get_svg_positive_icons();
			$svg_icons        = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'riwth-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="riwth_feedback_box_positive_button_icon" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo wp_kses( $icon, $svg_allowed_html ); ?>	
				</label>
				<?php
			}
		}

		public function feedback_box_negative_button_icon_callback() {
			$option           = get_option( 'riwth_feedback_box_negative_button_icon' );
			$svg_allowed_html = RIWTH_Functions::get_svg_allowed_html();
			$svg_icons        = RIWTH_SVG_Icons::get_svg_negative_icons();
			$svg_icons        = array_merge( $svg_icons, array( 'empty' => esc_html__( 'Leave Empty', 'riwth-was-this-helpful' ) ) );

			foreach ( $svg_icons as $key => $icon ) {
				?>
				<label>
					<input type="radio" name="riwth_feedback_box_negative_button_icon" value="<?php echo esc_attr( $key ); ?>" <?php checked( $key, $option ); ?>>
					<?php echo wp_kses( $icon, $svg_allowed_html ); ?>	
				</label>
				<?php
			}
		}


		public function feedback_box_colors_settings_section_callback() {
			echo esc_html__( 'Style your feedback box.', 'riwth-was-this-helpful' );
		}

		public function feedback_box_color_background_callback() {
			$option           = get_option( 'riwth_feedback_box_color_background' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="riwth_feedback_box_color_background" name="riwth_feedback_box_color_background" value="' . esc_attr( $option ) . '" class="riwth-color-field" data-default-color="' . esc_attr( $initial_settings['riwth_feedback_box_color_background'] ) . '" />';
		}

		public function feedback_box_color_positive_button_callback() {
			$option           = get_option( 'riwth_feedback_box_color_positive_button' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="riwth_feedback_box_color_positive_button" name="riwth_feedback_box_color_positive_button" value="' . esc_attr( $option ) . '" class="riwth-color-field" data-default-color="' . esc_attr( $initial_settings['riwth_feedback_box_color_positive_button'] ) . '" />';
		}

		public function feedback_box_color_positive_text_callback() {
			$option           = get_option( 'riwth_feedback_box_color_positive_text' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="riwth_feedback_box_color_positive_text" name="riwth_feedback_box_color_positive_text" value="' . esc_attr( $option ) . '" class="riwth-color-field" data-default-color="' . esc_attr( $initial_settings['riwth_feedback_box_color_positive_text'] ) . '" />';
		}

		public function feedback_box_color_negative_button_callback() {
			$option           = get_option( 'riwth_feedback_box_color_negative_button' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="riwth_feedback_box_color_negative_button" name="riwth_feedback_box_color_negative_button" value="' . esc_attr( $option ) . '" class="riwth-color-field" data-default-color="' . esc_attr( $initial_settings['riwth_feedback_box_color_negative_button'] ) . '" />';
		}

		public function feedback_box_color_negative_text_callback() {
			$option           = get_option( 'riwth_feedback_box_color_negative_text' );
			$initial_settings = self::get_intial_settings();
			echo '<input type="text" id="riwth_feedback_box_color_negative_text" name="riwth_feedback_box_color_negative_text" value="' . esc_attr( $option ) . '" class="riwth-color-field" data-default-color="' . esc_attr( $initial_settings['riwth_feedback_box_color_negative_text'] ) . '" />';
		}


		public function feedback_box_border_button_rounded_callback() {
			$option           = get_option( 'riwth_feedback_box_border_button_rounded' );
			$initial_settings = self::get_intial_settings();

			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
				// clear transient box
				delete_transient( 'riwth_feedback_box' );
			}

			echo '<input type="number" min="0" max="100" id="riwth_feedback_box_border_button_rounded" name="riwth_feedback_box_border_button_rounded" value="' . esc_attr( $option ) . '" />%';
		}


		public function uninstall_settings_section_callback() {
			echo esc_html( __( 'Deletes all data when plugin is removed.', 'riwth-was-this-helpful' ) );
		}


		public static function get_intial_settings() {
			$initial_settings = array(
				'riwth_display_on'                         => array( 'post' ),
				'riwth_display_by_user_role'               => array( 'administrator', 'editor' ),
				'riwth_load_styles'                        => 1,
				'riwth_load_scripts'                       => 1,
				'riwth_show_admin_bar_content'             => 1,
				'riwth_feedback_box_template'              => 'default',
				'riwth_feedback_box_text'                  => __( 'Was This Helpful?', 'riwth-was-this-helpful' ),
				'riwth_feedback_box_positive_button_text'  => __( 'Yes', 'riwth-was-this-helpful' ),
				'riwth_feedback_box_positive_button_icon'  => 'thumbs-up',
				'riwth_feedback_box_negative_button_text'  => __( 'No', 'riwth-was-this-helpful' ),
				'riwth_feedback_box_negative_button_icon'  => 'thumbs-down',
				'riwth_feedback_box_color_background'      => '#f4f4f5',
				'riwth_feedback_box_color_positive_button' => '#ffffff',
				'riwth_feedback_box_color_positive_text'   => '#444444',
				'riwth_feedback_box_color_negative_button' => '#ffffff',
				'riwth_feedback_box_color_negative_text'   => '#444444',
				'riwth_feedback_box_border_button_rounded' => '8',
				'riwth_uninstall_remove_data'              => 1,
				'riwth_feedback_box_submitting_text'       => __( '⏳ Submitting...', 'riwth-was-this-helpful' ),
				'riwth_feedback_box_thanks_text'           => __( '✅ Thank you for your feedback!', 'riwth-was-this-helpful' ),
			);
			return $initial_settings;
		}
	}
}
