<?php
/**
 * Tests for RIWTH_Admin_Feedback_List.
 *
 * Note: handle_delete_single(), handle_delete_all(), process_bulk_delete_request(),
 * and export_csv() all end with a raw `exit;` after their success-path side
 * effects. Only their guard clauses (capability + nonce, via
 * wp_die()/WPDieException) are exercised here — see the plan's "Known,
 * deliberate coverage limits" section for why the post-exit behavior can't
 * be run in-process. delete_records() itself (the non-exiting part of the
 * bulk-delete path) is fully covered directly, same as get_feedback_records()/
 * get_total_records().
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

	public function test_get_total_records_default_args_matches_all_rows() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 1, 1 );

		$this->assertSame( 2, $this->list->get_total_records( array() ) );
	}

	// -----------------------------------------------------------------
	// setup_list_table()
	// -----------------------------------------------------------------

	public function test_setup_list_table_constructs_and_registers_list_table() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );

		$this->list->setup_list_table();

		$this->assertInstanceOf( 'RIWTH_Feedback_List_Table', $this->list->get_list_table() );
	}

	// -----------------------------------------------------------------
	// get_feedback_records() / get_total_records() — orderby
	// -----------------------------------------------------------------

	public function test_get_feedback_records_orderby_helpful_asc() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0 );

		$records = $this->list->get_feedback_records( 1, 20, array( 'orderby' => 'helpful', 'order' => 'ASC' ) );

		$this->assertSame( '0', $records[0]->helpful );
		$this->assertSame( '1', $records[1]->helpful );
	}

	public function test_get_feedback_records_orderby_helpful_desc() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0 );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$records = $this->list->get_feedback_records( 1, 20, array( 'orderby' => 'helpful', 'order' => 'DESC' ) );

		$this->assertSame( '1', $records[0]->helpful );
		$this->assertSame( '0', $records[1]->helpful );
	}

	public function test_get_feedback_records_orderby_post_title_asc() {
		$alpha = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Alpha Post' ) );
		$beta  = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Beta Post' ) );
		RIWTH_Test_Factory::insert_feedback_row( $beta, 1 );
		RIWTH_Test_Factory::insert_feedback_row( $alpha, 1 );

		$records = $this->list->get_feedback_records( 1, 20, array( 'orderby' => 'post_title', 'order' => 'ASC' ) );

		$this->assertSame( $alpha, (int) $records[0]->post_id );
		$this->assertSame( $beta, (int) $records[1]->post_id );
	}

	public function test_get_feedback_records_orderby_post_title_desc() {
		$alpha = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Alpha Post' ) );
		$beta  = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Beta Post' ) );
		RIWTH_Test_Factory::insert_feedback_row( $alpha, 1 );
		RIWTH_Test_Factory::insert_feedback_row( $beta, 1 );

		$records = $this->list->get_feedback_records( 1, 20, array( 'orderby' => 'post_title', 'order' => 'DESC' ) );

		$this->assertSame( $beta, (int) $records[0]->post_id );
		$this->assertSame( $alpha, (int) $records[1]->post_id );
	}

	public function test_get_feedback_records_orderby_whitelist_rejects_malicious_value() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-01 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0, array( 'created_at' => '2024-06-01 00:00:00' ) );

		$malicious = 'id); DROP TABLE wp_riwth_helpful_feedback; --';
		$records   = $this->list->get_feedback_records( 1, 20, array( 'orderby' => $malicious ) );

		// Falls back to the default created_at DESC ordering rather than erroring.
		$this->assertCount( 2, $records );
		$this->assertSame( '2024-06-01 00:00:00', $records[0]->created_at );
	}

	// -----------------------------------------------------------------
	// get_feedback_records() / get_total_records() — month + search filters
	// -----------------------------------------------------------------

	public function test_get_feedback_records_month_filter_narrows_results() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-15 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-02-15 00:00:00' ) );

		$records = $this->list->get_feedback_records( 1, 20, array( 'm' => '202401' ) );

		$this->assertCount( 1, $records );
		$this->assertSame( '2024-01-15 00:00:00', $records[0]->created_at );
	}

	public function test_get_feedback_records_month_filter_invalid_value_ignored() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 2, 0 );

		$records = $this->list->get_feedback_records( 1, 20, array( 'm' => 'not-a-month' ) );

		$this->assertCount( 2, $records );
	}

	public function test_get_feedback_records_search_filters_by_post_title() {
		$alpha = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Alpha Post' ) );
		$beta  = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Beta Post' ) );
		RIWTH_Test_Factory::insert_feedback_row( $alpha, 1 );
		RIWTH_Test_Factory::insert_feedback_row( $beta, 1 );

		$records = $this->list->get_feedback_records( 1, 20, array( 's' => 'Alpha' ) );

		$this->assertCount( 1, $records );
		$this->assertSame( $alpha, (int) $records[0]->post_id );
	}

	public function test_get_total_records_respects_month_filter() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-15 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-02-15 00:00:00' ) );

		$this->assertSame( 1, $this->list->get_total_records( array( 'm' => '202401' ) ) );
	}

	public function test_get_total_records_respects_search_filter() {
		$alpha = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Alpha Post' ) );
		$beta  = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'Beta Post' ) );
		RIWTH_Test_Factory::insert_feedback_row( $alpha, 1 );
		RIWTH_Test_Factory::insert_feedback_row( $beta, 1 );

		$this->assertSame( 1, $this->list->get_total_records( array( 's' => 'Beta' ) ) );
	}

	// -----------------------------------------------------------------
	// get_feedback_months()
	// -----------------------------------------------------------------

	public function test_get_feedback_months_returns_distinct_buckets_newest_first() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-15 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-20 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-03-01 00:00:00' ) );

		$months = $this->list->get_feedback_months();

		$this->assertCount( 2, $months );
		$this->assertSame( 2024, (int) $months[0]->year );
		$this->assertSame( 3, (int) $months[0]->month );
		$this->assertSame( 1, (int) $months[1]->month );
	}

	// -----------------------------------------------------------------
	// delete_records()
	// -----------------------------------------------------------------

	public function test_delete_records_removes_rows_and_returns_count() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		$id1     = RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );
		$id2     = RIWTH_Test_Factory::insert_feedback_row( $post_id, 0 );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$deleted = $this->list->delete_records( array( $id1, $id2 ) );

		$this->assertSame( 2, $deleted );
		$this->assertSame( 1, $this->list->get_total_records() );
	}

	public function test_delete_records_returns_zero_for_empty_array() {
		$this->assertSame( 0, $this->list->delete_records( array() ) );
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
	// handle_bulk_delete() / process_bulk_delete_request()
	// -----------------------------------------------------------------

	public function test_handle_bulk_delete_noop_when_wrong_page() {
		$_GET['page']       = 'some-other-page';
		$_REQUEST['action'] = RIWTH_Admin_Feedback_List::BULK_DELETE_ACTION;

		$this->list->handle_bulk_delete();
		$this->addToAssertionCount( 1 );
	}

	public function test_handle_bulk_delete_noop_when_action_not_bulk_delete() {
		$_GET['page']       = 'riwth-feedback-list';
		$_REQUEST['action'] = '-1';

		$this->list->handle_bulk_delete();
		$this->addToAssertionCount( 1 );
	}

	public function test_handle_bulk_delete_dispatches_when_action_matches() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );
		$_GET['page']       = 'riwth-feedback-list';
		$_REQUEST['action'] = RIWTH_Admin_Feedback_List::BULK_DELETE_ACTION;

		$this->expectException( WPDieException::class );
		$this->list->handle_bulk_delete();
	}

	public function test_process_bulk_delete_request_dies_without_capability() {
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$this->expectException( WPDieException::class );
		$this->list->process_bulk_delete_request();
	}

	public function test_process_bulk_delete_request_dies_with_invalid_nonce() {
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_GET['_wpnonce'] = 'invalid';

		$this->expectException( WPDieException::class );
		$this->list->process_bulk_delete_request();
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

	public function test_show_action_notice_bulk_deleted() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
		$_GET['riwth_notice'] = 'bulk_deleted';

		ob_start();
		$this->list->show_action_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Selected feedback records deleted', $output );
	}

	public function test_show_action_notice_bulk_delete_none() {
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
		$_GET['riwth_notice'] = 'bulk_delete_none';

		ob_start();
		$this->list->show_action_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No feedback records were selected', $output );
	}
}
