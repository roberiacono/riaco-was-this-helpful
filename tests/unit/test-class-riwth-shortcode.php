<?php
/**
 * Tests for RIWTH_Shortcode.
 *
 * @package RIWTH
 */

class Test_RIWTH_Shortcode extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Shortcode
	 */
	private $shortcode;

	public function set_up() {
		parent::set_up();
		$this->shortcode = new RIWTH_Shortcode();
	}

	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'riwth_helpful_box' ) );
	}

	public function test_returns_html_when_eligible() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$output = $this->shortcode->shortcode_func( array() );

		$this->assertStringContainsString( 'riwth-helpful-feedback', $output );
	}

	public function test_returns_empty_string_when_feedback_already_given() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$_COOKIE['riwth_feedback_given'] = (string) $post_id;
		$output = $this->shortcode->shortcode_func( array() );
		unset( $_COOKIE['riwth_feedback_given'] );

		$this->assertSame( '', $output );
	}

	public function test_returns_false_when_could_not_display() {
		global $post;
		$post = null;

		$this->assertFalse( $this->shortcode->shortcode_func( array() ) );
	}

	public function test_before_shortcode_action_fires_with_no_args() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$fired = 0;
		add_action(
			'riwth_before_show_helpful_box_using_shortcode',
			function () use ( &$fired ) {
				$fired++;
			}
		);

		$this->shortcode->shortcode_func( array() );

		$this->assertSame( 1, $fired );
	}

	public function test_atts_parameter_is_ignored() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		// The shortcode signature accepts $atts but never reads it — passing
		// arbitrary attributes must produce byte-identical output to passing
		// none at all.
		delete_transient( 'riwth_feedback_box' );
		$with_no_atts = $this->shortcode->shortcode_func( array() );

		delete_transient( 'riwth_feedback_box' );
		$with_atts = $this->shortcode->shortcode_func( array( 'foo' => 'bar' ) );

		$this->assertSame( $with_no_atts, $with_atts );
	}
}
