<?php
/**
 * Tests for RIWTH_Ajax::save_feedback() (wp_ajax_riwth_save_feedback).
 *
 * RIWTH_Ajax is already instantiated once via the shared test bootstrap
 * (it's one of the classes unconditionally instantiated in
 * RIWTH_Was_This_Helpful::init()), so its hooks are already registered —
 * do not re-instantiate it here.
 *
 * @package RIWTH
 */

class Test_RIWTH_Ajax extends WP_Ajax_UnitTestCase {

	public function tear_down() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . RIWTH_DB_NAME );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_riwth\_rl\_%'" );

		$_POST = array();
		unset( $_SERVER['REMOTE_ADDR'] );

		parent::tear_down();
	}

	/**
	 * Build a full, valid $_POST payload, with per-test overrides.
	 *
	 * @param array $overrides Keys to override in the default payload.
	 * @return array The payload actually assigned to $_POST.
	 */
	private function valid_post( $post_id, $overrides = array() ) {
		$_POST = array_merge(
			array(
				'action'   => 'riwth_save_feedback',
				'nonce'    => wp_create_nonce( 'riwth_was_this_helpful_nonce' ),
				'post_id'  => $post_id,
				'helpful'  => 1,
			),
			$overrides
		);

		return $_POST;
	}

	/**
	 * Run the ajax action, swallowing the "successful" wp_send_json* die.
	 *
	 * @return array Decoded JSON response.
	 */
	private function handle() {
		try {
			$this->_handleAjax( 'riwth_save_feedback' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	// -----------------------------------------------------------------
	// Nonce
	// -----------------------------------------------------------------

	public function test_missing_nonce_stops_execution() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = array(
			'action'  => 'riwth_save_feedback',
			'post_id' => $post_id,
			'helpful' => 1,
		);

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'riwth_save_feedback' );
	}

	public function test_invalid_nonce_stops_execution() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id, array( 'nonce' => 'invalid-nonce' ) );

		$this->expectException( WPAjaxDieStopException::class );
		$this->_handleAjax( 'riwth_save_feedback' );
	}

	// -----------------------------------------------------------------
	// Parameter / post validation
	// -----------------------------------------------------------------

	public function test_missing_params_returns_400() {
		$_POST = array(
			'action' => 'riwth_save_feedback',
			'nonce'  => wp_create_nonce( 'riwth_was_this_helpful_nonce' ),
		);

		$response = $this->handle();

		$this->assertFalse( isset( $response['success'] ) && $response['success'] );
		$this->assertSame( 'Missing parameters.', $response['data']['message'] );
	}

	public function test_nonexistent_post_returns_400() {
		$_POST = $this->valid_post( 999999999 );

		$response = $this->handle();

		$this->assertSame( 'Invalid post.', $response['data']['message'] );
	}

	public function test_draft_post_returns_400() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_draft_post();
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		$this->assertSame( 'Invalid post.', $response['data']['message'] );
	}

	public function test_disallowed_post_type_returns_403() {
		update_option( 'riwth_display_on', array( 'page' ) );
		$post_id = RIWTH_Test_Factory::create_published_post( array( 'post_type' => 'post' ) );
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		$this->assertSame( 'Feedback not enabled for this post.', $response['data']['message'] );
	}

	// -----------------------------------------------------------------
	// Rate limiting
	// -----------------------------------------------------------------

	public function test_rate_limit_blocks_immediate_second_vote_same_ip_and_post() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.5';

		$_POST = $this->valid_post( $post_id );
		$first = $this->handle();
		$this->assertSame( 'showThankYou', $first['trigger'] );

		$this->_last_response = '';
		$_POST                = $this->valid_post( $post_id, array( 'nonce' => wp_create_nonce( 'riwth_was_this_helpful_nonce' ) ) );
		$second = $this->handle();

		$this->assertSame( 'Too many requests. Please wait before voting again.', $second['data']['message'] );
	}

	public function test_rate_limit_reclaim_after_backdated_expiry() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.6';

		$_POST = $this->valid_post( $post_id );
		$this->handle();

		RIWTH_Test_Factory::expire_rate_limit_claim( $post_id, '203.0.113.6' );

		$this->_last_response = '';
		$_POST                = $this->valid_post( $post_id, array( 'nonce' => wp_create_nonce( 'riwth_was_this_helpful_nonce' ) ) );
		$response              = $this->handle();

		$this->assertSame( 'showThankYou', $response['trigger'] );
	}

	public function test_rate_limit_independent_per_ip() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();

		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';
		$_POST                  = $this->valid_post( $post_id );
		$this->handle();

		$this->_last_response   = '';
		$_SERVER['REMOTE_ADDR'] = '203.0.113.11';
		$_POST                  = $this->valid_post( $post_id, array( 'nonce' => wp_create_nonce( 'riwth_was_this_helpful_nonce' ) ) );
		$response               = $this->handle();

		$this->assertSame( 'showThankYou', $response['trigger'] );
	}

	public function test_rate_limit_independent_per_post() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_a = RIWTH_Test_Factory::create_published_post();
		$post_b = RIWTH_Test_Factory::create_published_post();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.20';

		$_POST = $this->valid_post( $post_a );
		$this->handle();

		$this->_last_response = '';
		$_POST                = $this->valid_post( $post_b, array( 'nonce' => wp_create_nonce( 'riwth_was_this_helpful_nonce' ) ) );
		$response              = $this->handle();

		$this->assertSame( 'showThankYou', $response['trigger'] );
	}

	public function test_rate_limit_works_with_empty_remote_addr() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		unset( $_SERVER['REMOTE_ADDR'] );

		$_POST    = $this->valid_post( $post_id );
		$response = $this->handle();

		$this->assertSame( 'showThankYou', $response['trigger'] );
	}

	// -----------------------------------------------------------------
	// DB insert failure
	// -----------------------------------------------------------------

	public function test_db_insert_failure_returns_500() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();

		global $wpdb;
		$table = $wpdb->prefix . RIWTH_DB_NAME;
		$wpdb->query( "RENAME TABLE {$table} TO {$table}_tmp" );

		// Without this, $wpdb's own WP_DEBUG error output (the table being
		// temporarily missing) gets echoed into the same output buffer
		// _handleAjax() captures, corrupting the JSON before it can be
		// decoded.
		$suppress = $wpdb->suppress_errors( true );
		$_POST    = $this->valid_post( $post_id );
		$response = $this->handle();
		$wpdb->suppress_errors( $suppress );

		$wpdb->query( "RENAME TABLE {$table}_tmp TO {$table}" );

		$this->assertSame( 'Could not save feedback.', $response['data']['message'] );
	}

	// -----------------------------------------------------------------
	// Success path
	// -----------------------------------------------------------------

	public function test_success_inserts_row_and_busts_caches() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();

		// Warm caches so we can prove they get busted.
		RIWTH_Functions::get_total_feedback_count( $post_id );
		RIWTH_Functions::get_positive_feedback_count( $post_id );

		$_POST    = $this->valid_post( $post_id, array( 'helpful' => 1 ) );
		$response = $this->handle();

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE post_id = %d', $wpdb->prefix . RIWTH_DB_NAME, $post_id )
		);
		$this->assertSame( '1', $row->helpful );
		$this->assertSame( (int) $row->id, $response['feedbackId'] );

		$this->assertFalse( wp_cache_get( 'riwth_total_feedback_' . $post_id, 'riwth_feedback' ) );
		$this->assertFalse( wp_cache_get( 'riwth_positive_feedback_' . $post_id, 'riwth_feedback' ) );
	}

	public function test_success_response_has_no_top_level_success_key() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		// Deliberate: RIWTH_Ajax::save_feedback() uses wp_send_json(), not
		// wp_send_json_success(), so the response has no 'success' key.
		$this->assertArrayNotHasKey( 'success', $response );
	}

	public function test_success_helpful_zero_stores_zero() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id, array( 'helpful' => 0 ) );

		$this->handle();

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE post_id = %d', $wpdb->prefix . RIWTH_DB_NAME, $post_id )
		);
		$this->assertSame( '0', $row->helpful );
	}

	public function test_thanks_text_with_embedded_link_renders_link() {
		update_option( 'riwth_display_on', array( 'post' ) );
		update_option( 'riwth_feedback_box_thanks_text', 'Thanks! <a href="https://example.com/thanks">see more</a>' );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		$this->assertStringContainsString( '<a href="https://example.com/thanks">see more</a>', $response['content'] );

		delete_option( 'riwth_feedback_box_thanks_text' );
	}

	public function test_thanks_text_without_link_leaves_plain_text() {
		update_option( 'riwth_display_on', array( 'post' ) );
		update_option( 'riwth_feedback_box_thanks_text', 'Thanks!' );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		$this->assertStringNotContainsString( '<a href=', $response['content'] );

		delete_option( 'riwth_feedback_box_thanks_text' );
	}

	public function test_thanks_text_strips_disallowed_tags() {
		update_option( 'riwth_display_on', array( 'post' ) );
		update_option( 'riwth_feedback_box_thanks_text', 'Thanks! <script>alert(1)</script>' );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		$response = $this->handle();

		$this->assertStringNotContainsString( '<script>', $response['content'] );

		delete_option( 'riwth_feedback_box_thanks_text' );
	}

	// -----------------------------------------------------------------
	// Hooks
	// -----------------------------------------------------------------

	public function test_before_and_after_save_hooks_fire_with_correct_args() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id, array( 'helpful' => 1 ) );

		$before_args = null;
		$after_args  = null;
		add_action( 'riwth_before_save_feedback', function ( $pid, $helpful ) use ( &$before_args ) {
			$before_args = array( $pid, $helpful );
		}, 10, 2 );
		add_action( 'riwth_feedback_saved', function ( $fid, $pid, $helpful ) use ( &$after_args ) {
			$after_args = array( $fid, $pid, $helpful );
		}, 10, 3 );

		$this->handle();

		$this->assertSame( array( $post_id, 1 ), $before_args );
		$this->assertSame( $post_id, $after_args[1] );
		$this->assertSame( 1, $after_args[2] );
	}

	public function test_insert_data_and_format_filters_can_extend_insert() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		add_filter( 'riwth_insert_feedback_data', function ( $data ) {
			// created_at is overridden to prove the filter's return value wins.
			$data['created_at'] = '2020-01-01 00:00:00';
			return $data;
		} );

		$this->handle();

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM %i WHERE post_id = %d', $wpdb->prefix . RIWTH_DB_NAME, $post_id )
		);
		$this->assertSame( '2020-01-01 00:00:00', $row->created_at );

		remove_all_filters( 'riwth_insert_feedback_data' );
	}

	public function test_ajax_feedback_sent_return_filter_can_override_response() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST   = $this->valid_post( $post_id );

		add_filter( 'riwth_ajax_feedback_sent_return', function () {
			return array( 'custom' => 'value' );
		} );

		$response = $this->handle();

		$this->assertSame( array( 'custom' => 'value' ), $response );

		remove_all_filters( 'riwth_ajax_feedback_sent_return' );
	}
}
