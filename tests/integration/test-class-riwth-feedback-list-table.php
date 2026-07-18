<?php
/**
 * Tests for RIWTH_Feedback_List_Table.
 *
 * Not required by RIWTH_Was_This_Helpful::includes() (it extends
 * WP_List_Table, which doesn't exist outside wp-admin), so this file loads
 * it explicitly before use.
 *
 * @package RIWTH
 */

require_once dirname( __DIR__, 2 ) . '/includes/class-feedback-list-table.php';

class Test_RIWTH_Feedback_List_Table extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Admin_Feedback_List
	 */
	private $admin_list;

	public function set_up() {
		parent::set_up();
		$this->admin_list = new RIWTH_Admin_Feedback_List();
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		set_current_screen( 'was-this-helpful_page_riwth-feedback-list' );
	}

	public function tear_down() {
		set_current_screen( 'front' );
		parent::tear_down();
	}

	// -----------------------------------------------------------------
	// get_columns() / get_sortable_columns() / get_bulk_actions()
	// -----------------------------------------------------------------

	public function test_get_columns_merges_riwth_feedback_list_columns_filter() {
		add_filter(
			'riwth_feedback_list_columns',
			function () {
				return array( 'Custom' );
			}
		);

		$table   = new RIWTH_Feedback_List_Table( $this->admin_list );
		$columns = $table->get_columns();

		$this->assertArrayHasKey( 'riwth_extra_0', $columns );
		$this->assertSame( 'Custom', $columns['riwth_extra_0'] );
	}

	public function test_get_columns_has_expected_base_keys() {
		$table   = new RIWTH_Feedback_List_Table( $this->admin_list );
		$columns = $table->get_columns();

		$this->assertSame( array( 'cb', 'post', 'slug', 'vote', 'date' ), array_keys( $columns ) );
	}

	public function test_get_sortable_columns_has_expected_keys() {
		$table    = new RIWTH_Feedback_List_Table( $this->admin_list );
		$sortable = $table->get_sortable_columns();

		$this->assertSame( 'post_title', $sortable['post'][0] );
		$this->assertSame( 'helpful', $sortable['vote'][0] );
		$this->assertSame( 'created_at', $sortable['date'][0] );
	}

	public function test_get_bulk_actions_uses_namespaced_delete_key() {
		$table   = new RIWTH_Feedback_List_Table( $this->admin_list );
		$actions = $table->get_bulk_actions();

		$this->assertArrayHasKey( RIWTH_Admin_Feedback_List::BULK_DELETE_ACTION, $actions );
		$this->assertArrayNotHasKey( 'delete', $actions );
	}

	// -----------------------------------------------------------------
	// column_default() — riwth_feedback_list_row_cells memoization
	// -----------------------------------------------------------------

	public function test_row_cells_filter_fires_once_per_row_not_once_per_column() {
		add_filter(
			'riwth_feedback_list_columns',
			function () {
				return array( 'Extra One', 'Extra Two' );
			}
		);

		$calls = 0;
		add_filter(
			'riwth_feedback_list_row_cells',
			function () use ( &$calls ) {
				++$calls;
				return array( 'a', 'b' );
			}
		);

		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 2, 0 );

		$table = new RIWTH_Feedback_List_Table( $this->admin_list );
		$table->prepare_items();

		ob_start();
		$table->display();
		ob_get_clean();

		// 2 rows, 2 extra columns each: once per row (2), never once per column (4).
		$this->assertSame( 2, $calls );
	}

	// -----------------------------------------------------------------
	// prepare_items()
	// -----------------------------------------------------------------

	public function test_prepare_items_builds_items_and_pagination() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 3, 2 );

		$table = new RIWTH_Feedback_List_Table( $this->admin_list );
		$table->prepare_items();

		$this->assertCount( 5, $table->items );
		$this->assertSame( 5, $table->get_pagination_arg( 'total_items' ) );
	}

	public function test_prepare_items_orderby_whitelist_rejects_malicious_get_value() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-01 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0, array( 'created_at' => '2024-06-01 00:00:00' ) );

		$_GET['orderby'] = 'id); DROP TABLE wp_riwth_helpful_feedback; --';

		$table = new RIWTH_Feedback_List_Table( $this->admin_list );
		$table->prepare_items();

		// Falls back to the default created_at DESC ordering rather than erroring.
		$this->assertSame( '2024-06-01 00:00:00', $table->items[0]->created_at );
	}

	// -----------------------------------------------------------------
	// extra_tablenav() — month dropdown
	// -----------------------------------------------------------------

	public function test_extra_tablenav_top_outputs_month_options_in_yyyymm_format() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-01-15 00:00:00' ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2024-03-01 00:00:00' ) );

		$table  = new RIWTH_Feedback_List_Table( $this->admin_list );
		$method = new ReflectionMethod( $table, 'extra_tablenav' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $table, 'top' );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'value="202401"', $output );
		$this->assertStringContainsString( 'value="202403"', $output );
	}

	public function test_extra_tablenav_bottom_outputs_nothing() {
		$table  = new RIWTH_Feedback_List_Table( $this->admin_list );
		$method = new ReflectionMethod( $table, 'extra_tablenav' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $table, 'bottom' );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	// -----------------------------------------------------------------
	// column_post() / column_vote()
	// -----------------------------------------------------------------

	public function test_column_post_and_column_vote_render_expected_content() {
		$post_id = RIWTH_Test_Factory::create_published_post( array( 'post_title' => 'A Sample Post' ) );
		$id      = RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$table = new RIWTH_Feedback_List_Table( $this->admin_list );
		$item  = (object) array(
			'id'         => $id,
			'post_id'    => $post_id,
			'helpful'    => '1',
			'created_at' => current_time( 'mysql', true ),
		);

		$column_post = new ReflectionMethod( $table, 'column_post' );
		$column_post->setAccessible( true );
		$column_vote = new ReflectionMethod( $table, 'column_vote' );
		$column_vote->setAccessible( true );

		$post_html = $column_post->invoke( $table, $item );
		$vote_html = $column_vote->invoke( $table, $item );

		$this->assertStringContainsString( 'A Sample Post', $post_html );
		$this->assertStringContainsString( 'Delete', $post_html );
		$this->assertStringContainsString( 'Yes', $vote_html );
	}
}
