<?php
/**
 * Tests for RIWTH_Admin_Review_Notice::handle_ajax_action() (wp_ajax_riwth_review_action).
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Review_Notice_Ajax extends WP_Ajax_UnitTestCase {

	/**
	 * @var RIWTH_Admin_Review_Notice
	 */
	private $notice;

	public function set_up() {
		parent::set_up();
		$this->notice = new RIWTH_Admin_Review_Notice();
	}

	public function tear_down() {
		delete_option( 'riwth_review_notice_done' );
		delete_transient( 'riwth_review_notice_maybe_later' );
		$_POST = array();
		parent::tear_down();
	}

	private function valid_post( $overrides = array() ) {
		return array_merge(
			array(
				'action'      => 'riwth_review_action',
				'nonce'       => wp_create_nonce( 'riwth_review_nonce' ),
				'action_type' => 'review',
			),
			$overrides
		);
	}

	private function handle() {
		try {
			$this->_handleAjax( 'riwth_review_action' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	public function test_missing_nonce_stops_execution() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = array( 'action' => 'riwth_review_action', 'action_type' => 'review' );

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'riwth_review_action' );
	}

	public function test_invalid_nonce_stops_execution() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post( array( 'nonce' => 'invalid' ) );

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'riwth_review_action' );
	}

	public function test_subscriber_denied_as_unauthorized() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );
		$_POST = $this->valid_post();

		$response = $this->handle();

		$this->assertSame( 'Unauthorized', $response['data']['message'] );
	}

	public function test_guest_denied_as_unauthorized() {
		wp_set_current_user( 0 );
		$_POST = $this->valid_post();

		$response = $this->handle();

		$this->assertSame( 'Unauthorized', $response['data']['message'] );
	}

	public function test_review_action_marks_done() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post( array( 'action_type' => 'review' ) );

		$response = $this->handle();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 1, get_option( 'riwth_review_notice_done' ) );
	}

	public function test_dismiss_action_marks_done() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post( array( 'action_type' => 'dismiss' ) );

		$response = $this->handle();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 1, get_option( 'riwth_review_notice_done' ) );
	}

	public function test_later_action_sets_transient() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post( array( 'action_type' => 'later' ) );

		$response = $this->handle();

		$this->assertTrue( $response['success'] );
		$this->assertNotFalse( get_transient( 'riwth_review_notice_maybe_later' ) );
		$this->assertFalse( get_option( 'riwth_review_notice_done' ) );
	}

	public function test_missing_action_type_is_invalid() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post();
		unset( $_POST['action_type'] );

		$response = $this->handle();

		$this->assertSame( 'Invalid action', $response['data']['message'] );
	}

	public function test_unknown_action_type_is_invalid() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST = $this->valid_post( array( 'action_type' => 'not-a-real-action' ) );

		$response = $this->handle();

		$this->assertSame( 'Invalid action', $response['data']['message'] );
	}

	/**
	 * Contrast with RIWTH_Ajax::save_feedback(), which enforces a 30-second
	 * per-IP/post rate limit — this handler has none.
	 */
	public function test_no_rate_limiting_on_this_handler() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$_POST = $this->valid_post( array( 'action_type' => 'later' ) );
		$first = $this->handle();

		$this->_last_response = '';
		$_POST                = $this->valid_post( array( 'action_type' => 'later', 'nonce' => wp_create_nonce( 'riwth_review_nonce' ) ) );
		$second                = $this->handle();

		$this->assertTrue( $first['success'] );
		$this->assertTrue( $second['success'] );
	}
}
