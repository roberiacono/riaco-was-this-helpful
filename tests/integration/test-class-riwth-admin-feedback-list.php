<?php
/**
 * Tests for RIWTH_Admin_Feedback_List.
 *
 * Note: handle_delete_single(), handle_delete_all(), and export_csv() all
 * end with a raw `exit;` after their success-path side effects. Only their
 * guard clauses (capability + nonce, via wp_die()/WPDieException) are
 * exercised here — see the plan's "Known, deliberate coverage limits"
 * section for why the post-exit behavior can't be run in-process.
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Feedback_List extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Admin_Feedback_List
	 */
	private $list;

	public function set_up() {
		parent::set_up();
		$this->list = new RIWTH_Admin_Feedback_List();
	}

	public function tear_down() {
		$_GET = array();
		set_current_screen( 'front' );
		parent::tear_down();
	}

	// -----------------------------------------------------------------
	// get_feedback_records() / get_total_records() — no exit, fully testable
	// -----------------------------------------------------------------

	public function test_get_feedback_records_empty_table() {
		$this->assertSame( array(), $this->list->get_feedback_records() );
	}

	public function test_get_feedback_records_returns_rows_newest_first() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-01 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0, array( 'created_at' => '2024-06-01 00:00:00' ) );

		$records = $this->list->get_feedback_records();

		$this->assertCount( 2, $records );
		$this->assertSame( '2024-06-01 00:00:00', $records[0]->created_at );
	}

	public function test_get_feedback_records_pagination() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 5, 0 );

		$page_one = $this->list->get_feedback_records( 1, 2 );
		$page_two = $this->list->get_feedback_records( 2, 2 );

		$this->assertCount( 2, $page_one );
		$this->assertCount( 2, $page_two );
		$this->assertNotSame( $page_one[0]->id, $page_two[0]->id );
	}

	public function test_get_total_records_matches_row_count() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 2, 3 );

		$this->assertSame( 5, $this->list->get_total_records() );
	}

	// -----------------------------------------------------------------
	// handle_delete_single() — guard clauses only
	// -----------------------------------------------------------------

	public function test_delete_single_dies_without_capability() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$this->expectException( WPDieException::class );
		$this->list->handle_delete_single();
	}

	public function test_delete_single_dies_without_nonce() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_GET['feedback_id'] = 1;
		unset( $_GET['_wpnonce'] );

		$this->expectException( WPDieException::class );
		$this->list->handle_delete_single();
	}

	public function test_delete_single_dies_with_invalid_nonce() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_GET['feedback_id'] = 1;
		$_GET['_wpnonce']    = 'invalid';

		$this->expectException( WPDieException::class );
		$this->list->handle_delete_single();
	}

	// -----------------------------------------------------------------
	// handle_delete_all() — guard clauses only
	// -----------------------------------------------------------------

	public function test_delete_all_dies_without_capability() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$this->expectException( WPDieException::class );
		$this->list->handle_delete_all();
	}

	public function test_delete_all_dies_with_invalid_nonce() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_GET['_wpnonce'] = 'invalid';

		$this->expectException( WPDieException::class );
		$this->list->handle_delete_all();
	}

	// -----------------------------------------------------------------
	// export_csv() — guard clauses only
	// -----------------------------------------------------------------

	public function test_export_csv_dies_without_capability() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );
		$_GET['page']   = 'riwth-feedback-list';
		$_GET['export'] = 'csv';

		$this->expectException( WPDieException::class );
		$this->list->maybe_export_csv();
	}

	public function test_maybe_export_csv_noop_when_not_requested() {
		$_GET = array();

		// Should simply return, no exception, no output.
		$this->list->maybe_export_csv();
		$this->addToAssertionCount( 1 );
	}

	// -----------------------------------------------------------------
	// show_action_notice()
	// -----------------------------------------------------------------

	public function test_show_action_notice_only_on_own_screen() {
		set_current_screen( 'edit.php' );
		$_GET['riwth_notice'] = 'deleted';

		ob_start();
		$this->list->show_action_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_show_action_notice_deleted() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
		$_GET['riwth_notice'] = 'deleted';

		ob_start();
		$this->list->show_action_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Feedback record deleted', $output );
	}

	public function test_show_action_notice_unknown_value_produces_no_output() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
		$_GET['riwth_notice'] = 'something_unexpected';

		ob_start();
		$this->list->show_action_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
