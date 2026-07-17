<?php
/**
 * Tests for RIWTH_Admin_Pages_Footer.
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Pages_Footer extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Admin_Pages_Footer
	 */
	private $footer;

	public function set_up() {
		parent::set_up();
		$this->footer = new RIWTH_Admin_Pages_Footer();
	}

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_is_plugin_link_page_true_for_settings_screen() {
		set_current_screen( 'toplevel_page_riwth-settings' );
		$this->assertTrue( $this->footer->is_plugin_link_page() );
	}

	public function test_is_plugin_link_page_true_for_shortcode_screen() {
		set_current_screen( 'was-this-helpful_page_riwth-shortcode' );
		$this->assertTrue( $this->footer->is_plugin_link_page() );
	}

	public function test_is_plugin_link_page_true_for_feedback_list_screen() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
		$this->assertTrue( $this->footer->is_plugin_link_page() );
	}

	public function test_is_plugin_link_page_false_for_unrelated_screen() {
		set_current_screen( 'edit.php' );
		$this->assertFalse( $this->footer->is_plugin_link_page() );
	}

	public function test_admin_footer_text_unchanged_on_unrelated_screen() {
		set_current_screen( 'edit.php' );
		$this->assertSame( 'Original', $this->footer->admin_footer( 'Original' ) );
	}

	public function test_admin_footer_text_replaced_on_plugin_screen() {
		set_current_screen( 'toplevel_page_riwth-settings' );
		$this->assertStringContainsString( 'Was This Helpful', $this->footer->admin_footer( 'Original' ) );
	}
}
