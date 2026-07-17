<?php
/**
 * Tests for RIWTH_Box.
 *
 * @package RIWTH
 */

class Test_RIWTH_Box extends RIWTH_Test_Case {

	public function set_up() {
		parent::set_up();
		delete_transient( 'riwth_feedback_box' );
	}

	public function test_feedback_box_code_contains_button_markup() {
		$html = RIWTH_Box::feedback_box_code();

		$this->assertStringContainsString( 'riwth-helpful-yes', $html );
		$this->assertStringContainsString( 'riwth-helpful-no', $html );
		$this->assertStringContainsString( 'riwth-helpful-feedback', $html );
	}

	public function test_feedback_box_code_contains_configured_text() {
		update_option( 'riwth_feedback_box_text', 'Custom question?' );
		delete_transient( 'riwth_feedback_box' );

		$html = RIWTH_Box::feedback_box_code();

		$this->assertStringContainsString( 'Custom question?', $html );
	}

	public function test_feedback_box_code_caches_attrs_in_transient() {
		update_option( 'riwth_feedback_box_color_background', '#111111' );
		delete_transient( 'riwth_feedback_box' );

		RIWTH_Box::feedback_box_code();

		// Changing the option after the transient was warmed should have no
		// effect until the transient is cleared (busted via 'updated_option'
		// in RIWTH_Settings::maybe_clear_box_transient(), tested separately).
		$attr = get_transient( 'riwth_feedback_box' );
		$this->assertStringContainsString( '#111111', $attr['feedback_box_style'] );
	}

	public function test_feedback_box_atts_filter_can_override_style() {
		delete_transient( 'riwth_feedback_box' );

		add_filter(
			'riwth_feedback_box_atts',
			function ( $attr ) {
				$attr['feedback_box_style'] = 'background-color:#abcdef;';
				return $attr;
			}
		);

		$html = RIWTH_Box::feedback_box_code();
		$this->assertStringContainsString( '#abcdef', $html );

		remove_all_filters( 'riwth_feedback_box_atts' );
	}

	public function test_feedback_box_html_filter_can_wrap_output() {
		add_filter(
			'riwth_feedback_box_html',
			function ( $html ) {
				return '<div class="wrapped">' . $html . '</div>';
			}
		);

		$html = RIWTH_Box::feedback_box_code();
		$this->assertStringStartsWith( '<div class="wrapped">', $html );

		remove_all_filters( 'riwth_feedback_box_html' );
	}

	public function test_get_feedback_box_nonce_is_valid() {
		$nonce = RIWTH_Box::get_feedback_box_nonce();
		$this->assertNotFalse( wp_verify_nonce( $nonce, 'riwth_was_this_helpful_nonce' ) );
	}

	public function test_get_feedback_box_style_empty_when_no_color_set() {
		delete_option( 'riwth_feedback_box_color_background' );
		$this->assertSame( '', RIWTH_Box::get_feedback_box_style() );
	}

	public function test_get_feedback_box_style_with_color_set() {
		update_option( 'riwth_feedback_box_color_background', '#f4f4f5' );
		$this->assertSame( 'background-color:#f4f4f5;', RIWTH_Box::get_feedback_box_style() );
	}

	public function test_get_feedback_box_text_custom_value() {
		update_option( 'riwth_feedback_box_text', 'Was this useful?' );
		$this->assertSame( 'Was this useful?', RIWTH_Box::get_feedback_box_text() );
	}

	public function test_get_feedback_box_text_falls_back_when_unset() {
		delete_option( 'riwth_feedback_box_text' );
		$this->assertSame( 'Was This Helpful?', RIWTH_Box::get_feedback_box_text() );
	}

	public function test_get_feedback_box_button_icon_invalid_type_returns_null() {
		$this->assertNull( RIWTH_Box::get_feedback_box_button_icon( 'sideways' ) );
	}

	public function test_get_feedback_box_button_icon_empty_returns_empty_string() {
		update_option( 'riwth_feedback_box_positive_button_icon', 'empty' );
		$this->assertSame( '', RIWTH_Box::get_feedback_box_button_icon( 'positive' ) );
	}

	public function test_get_feedback_box_button_icon_known_key_returns_svg() {
		update_option( 'riwth_feedback_box_positive_button_icon', 'thumbs-up' );
		$this->assertStringContainsString( '<svg', RIWTH_Box::get_feedback_box_button_icon( 'positive' ) );
	}

	public function test_get_feedback_box_button_text_invalid_type_returns_null() {
		$this->assertNull( RIWTH_Box::get_feedback_box_button_text( 'sideways' ) );
	}

	public function test_get_feedback_box_button_text_wraps_in_span() {
		update_option( 'riwth_feedback_box_positive_button_text', 'Yep' );
		$this->assertSame( '<span> Yep</span>', RIWTH_Box::get_feedback_box_button_text( 'positive' ) );
	}

	public function test_get_feedback_box_button_text_empty_omits_span() {
		update_option( 'riwth_feedback_box_positive_button_text', '' );
		$this->assertSame( '', RIWTH_Box::get_feedback_box_button_text( 'positive' ) );
	}

	public function test_get_feedback_box_button_style_invalid_type_returns_null() {
		$this->assertNull( RIWTH_Box::get_feedback_box_button_style( 'sideways' ) );
	}

	public function test_get_feedback_box_button_style_composes_color_and_radius() {
		update_option( 'riwth_feedback_box_color_positive_button', '#ffffff' );
		update_option( 'riwth_feedback_box_color_positive_text', '#444444' );
		update_option( 'riwth_feedback_box_border_button_rounded', '8' );

		$style = RIWTH_Box::get_feedback_box_button_style( 'positive' );

		$this->assertStringContainsString( 'background-color: #ffffff', $style );
		$this->assertStringContainsString( 'color: #444444', $style );
		$this->assertStringContainsString( 'border-radius: 8%', $style );
	}

	public function test_get_feedback_box_button_style_uses_background_color_regardless_of_layout() {
		update_option( 'riwth_feedback_box_layout', 'one_line' );
		update_option( 'riwth_feedback_box_color_positive_button', '#101010' );
		update_option( 'riwth_feedback_box_color_positive_text', '#101010' );

		$style = RIWTH_Box::get_feedback_box_button_style( 'positive' );

		$this->assertStringContainsString( 'background-color: #101010', $style );
		$this->assertStringContainsString( 'color: #101010', $style );
	}

	public function test_get_feedback_box_layout_falls_back_to_default() {
		delete_option( 'riwth_feedback_box_layout' );
		$this->assertSame( 'default', RIWTH_Box::get_feedback_box_layout() );

		update_option( 'riwth_feedback_box_layout', 'not-a-real-layout' );
		$this->assertSame( 'default', RIWTH_Box::get_feedback_box_layout() );
	}

	public function test_get_feedback_box_layout_returns_configured_value() {
		update_option( 'riwth_feedback_box_layout', 'one_line' );
		$this->assertSame( 'one_line', RIWTH_Box::get_feedback_box_layout() );
	}

	public function test_feedback_box_code_adds_one_line_class_when_configured() {
		update_option( 'riwth_feedback_box_layout', 'one_line' );
		delete_transient( 'riwth_feedback_box' );

		$html = RIWTH_Box::feedback_box_code();

		$this->assertStringContainsString( 'riwth-layout-one-line', $html );
	}

	public function test_feedback_box_code_omits_one_line_class_by_default() {
		update_option( 'riwth_feedback_box_layout', 'default' );
		delete_transient( 'riwth_feedback_box' );

		$html = RIWTH_Box::feedback_box_code();

		$this->assertStringNotContainsString( 'riwth-layout-one-line', $html );
	}

	public function test_add_feedback_box_appends_when_should_display() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$box = new RIWTH_Box();
		$content = $box->add_feedback_box( 'Original content' );

		$this->assertStringContainsString( 'Original content', $content );
		$this->assertStringContainsString( 'riwth-helpful-feedback', $content );
	}

	public function test_add_feedback_box_unchanged_when_should_not_display() {
		global $post;
		$post = null;

		$box = new RIWTH_Box();
		$content = $box->add_feedback_box( 'Original content' );

		$this->assertSame( 'Original content', $content );
	}
}
