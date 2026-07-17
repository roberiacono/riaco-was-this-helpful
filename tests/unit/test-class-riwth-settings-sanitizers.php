<?php
/**
 * Tests for RIWTH_Settings sanitizers and defaults.
 *
 * @package RIWTH
 */

class Test_RIWTH_Settings_Sanitizers extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Settings
	 */
	private $settings;

	public function set_up() {
		parent::set_up();
		$this->settings = new RIWTH_Settings();
	}

	// -----------------------------------------------------------------
	// sanitize_display_on()
	// -----------------------------------------------------------------

	public function test_sanitize_display_on_non_array_returns_empty_array() {
		$this->assertSame( array(), $this->settings->sanitize_display_on( 'post' ) );
	}

	public function test_sanitize_display_on_keeps_whitelisted_values() {
		$result = $this->settings->sanitize_display_on( array( 'post', 'page' ) );
		$this->assertSame( array( 'post', 'page' ), array_values( $result ) );
	}

	public function test_sanitize_display_on_strips_unknown_values() {
		$result = $this->settings->sanitize_display_on( array( 'post', 'evil_post_type' ) );
		$this->assertSame( array( 'post' ), array_values( $result ) );
	}

	public function test_sanitize_display_on_extended_by_filter() {
		add_filter(
			'riwth_display_on_fields',
			function ( $fields ) {
				$fields[] = array( 'value' => 'product' );
				return $fields;
			}
		);

		$result = $this->settings->sanitize_display_on( array( 'product' ) );
		$this->assertSame( array( 'product' ), array_values( $result ) );

		remove_all_filters( 'riwth_display_on_fields' );
	}

	// -----------------------------------------------------------------
	// sanitize_user_roles()
	// -----------------------------------------------------------------

	public function test_sanitize_user_roles_non_array_returns_empty_array() {
		$this->assertSame( array(), $this->settings->sanitize_user_roles( 'administrator' ) );
	}

	public function test_sanitize_user_roles_keeps_real_roles() {
		$result = $this->settings->sanitize_user_roles( array( 'administrator', 'editor' ) );
		$this->assertSame( array( 'administrator', 'editor' ), array_values( $result ) );
	}

	public function test_sanitize_user_roles_strips_garbage_role() {
		$result = $this->settings->sanitize_user_roles( array( 'administrator', 'not_a_real_role' ) );
		$this->assertSame( array( 'administrator' ), array_values( $result ) );
	}

	// -----------------------------------------------------------------
	// sanitize_checkbox()
	// -----------------------------------------------------------------

	public function test_sanitize_checkbox_string_one_is_true() {
		$this->assertSame( 1, $this->settings->sanitize_checkbox( '1' ) );
	}

	public function test_sanitize_checkbox_anything_else_is_false() {
		$this->assertSame( 0, $this->settings->sanitize_checkbox( '0' ) );
		$this->assertSame( 0, $this->settings->sanitize_checkbox( '' ) );
		$this->assertSame( 0, $this->settings->sanitize_checkbox( null ) );
		$this->assertSame( 0, $this->settings->sanitize_checkbox( 1 ) ); // int 1, not string '1'.
	}

	// -----------------------------------------------------------------
	// sanitize_positive_button_icon() / sanitize_negative_button_icon()
	// -----------------------------------------------------------------

	public function test_sanitize_positive_button_icon_valid_key() {
		$this->assertSame( 'thumbs-up', $this->settings->sanitize_positive_button_icon( 'thumbs-up' ) );
	}

	public function test_sanitize_positive_button_icon_invalid_falls_back_to_empty() {
		$this->assertSame( 'empty', $this->settings->sanitize_positive_button_icon( 'not-a-real-icon' ) );
	}

	public function test_sanitize_positive_button_icon_empty_is_valid() {
		$this->assertSame( 'empty', $this->settings->sanitize_positive_button_icon( 'empty' ) );
	}

	public function test_sanitize_negative_button_icon_valid_key() {
		$this->assertSame( 'thumbs-down', $this->settings->sanitize_negative_button_icon( 'thumbs-down' ) );
	}

	public function test_sanitize_negative_button_icon_invalid_falls_back_to_empty() {
		$this->assertSame( 'empty', $this->settings->sanitize_negative_button_icon( 'not-a-real-icon' ) );
	}

	// -----------------------------------------------------------------
	// sanitize_feedback_box_layout()
	// -----------------------------------------------------------------

	public function test_sanitize_feedback_box_layout_valid_value() {
		$this->assertSame( 'one_line', $this->settings->sanitize_feedback_box_layout( 'one_line' ) );
		$this->assertSame( 'default', $this->settings->sanitize_feedback_box_layout( 'default' ) );
	}

	public function test_sanitize_feedback_box_layout_invalid_falls_back_to_default() {
		$this->assertSame( 'default', $this->settings->sanitize_feedback_box_layout( 'not-a-real-layout' ) );
	}

	// -----------------------------------------------------------------
	// sanitize_border_radius()
	// -----------------------------------------------------------------

	public function test_sanitize_border_radius_negative_clamped_to_zero() {
		// absint(-10) === 10, which is in [0,100], so it passes through as 10.
		$this->assertSame( 10, $this->settings->sanitize_border_radius( -10 ) );
	}

	public function test_sanitize_border_radius_zero() {
		$this->assertSame( 0, $this->settings->sanitize_border_radius( 0 ) );
	}

	public function test_sanitize_border_radius_hundred() {
		$this->assertSame( 100, $this->settings->sanitize_border_radius( 100 ) );
	}

	public function test_sanitize_border_radius_over_hundred_resets_to_zero() {
		$this->assertSame( 0, $this->settings->sanitize_border_radius( 101 ) );
	}

	public function test_sanitize_border_radius_non_numeric_resets_to_zero() {
		$this->assertSame( 0, $this->settings->sanitize_border_radius( 'abc' ) );
	}

	// -----------------------------------------------------------------
	// get_intial_settings()
	// -----------------------------------------------------------------

	public function test_get_intial_settings_default_map() {
		$defaults = RIWTH_Settings::get_intial_settings();

		$this->assertSame( array( 'post' ), $defaults['riwth_display_on'] );
		$this->assertSame( array( 'administrator', 'editor' ), $defaults['riwth_display_by_user_role'] );
		$this->assertSame( 1, $defaults['riwth_load_styles'] );
		$this->assertSame( 1, $defaults['riwth_load_scripts'] );
		$this->assertSame( '#f4f4f5', $defaults['riwth_feedback_box_color_background'] );
		$this->assertSame( 'default', $defaults['riwth_feedback_box_layout'] );
		$this->assertSame( '8', $defaults['riwth_feedback_box_border_button_rounded'] );
		$this->assertSame( '', $defaults['riwth_feedback_box_thanks_url'] );
	}

	public function test_get_intial_settings_filter_overrides_defaults() {
		add_filter(
			'riwth_initial_settings',
			function ( $settings ) {
				$settings['riwth_display_on'] = array( 'post', 'page' );
				return $settings;
			}
		);

		$defaults = RIWTH_Settings::get_intial_settings();
		$this->assertSame( array( 'post', 'page' ), $defaults['riwth_display_on'] );

		remove_all_filters( 'riwth_initial_settings' );
	}

	// -----------------------------------------------------------------
	// maybe_clear_box_transient()
	// -----------------------------------------------------------------

	public function test_maybe_clear_box_transient_busts_on_feedback_box_option() {
		set_transient( 'riwth_feedback_box', array( 'x' => 'y' ), DAY_IN_SECONDS );

		update_option( 'riwth_feedback_box_text', 'Changed!' );

		$this->assertFalse( get_transient( 'riwth_feedback_box' ) );
	}

	public function test_maybe_clear_box_transient_leaves_unrelated_option_alone() {
		set_transient( 'riwth_feedback_box', array( 'x' => 'y' ), DAY_IN_SECONDS );

		update_option( 'riwth_display_on', array( 'post', 'page' ) );

		$this->assertSame( array( 'x' => 'y' ), get_transient( 'riwth_feedback_box' ) );
	}
}
