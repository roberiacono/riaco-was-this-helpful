<?php
/**
 * Tests for RIWTH_Admin_Columns.
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Columns extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Admin_Columns
	 */
	private $columns;

	public function set_up() {
		parent::set_up();
		$this->columns = new RIWTH_Admin_Columns();
		$this->columns->add_filters_and_actions();
	}

	public function tear_down() {
		remove_filter( 'posts_join', array( $this->columns, 'feedback_posts_join' ) );
		remove_filter( 'posts_fields', array( $this->columns, 'feedback_posts_fields' ) );
		remove_filter( 'posts_orderby', array( $this->columns, 'feedback_posts_orderby' ) );
		remove_filter( 'posts_groupby', array( $this->columns, 'feedback_posts_groupby' ) );
		$_GET = array();
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_add_feedback_column_adds_key() {
		$columns = $this->columns->add_feedback_column( array( 'title' => 'Title' ) );
		$this->assertArrayHasKey( 'helpful_feedback', $columns );
	}

	public function test_custom_columns_post_types_filter_extends_defaults() {
		add_filter(
			'riwth_custom_columns_post_types',
			function ( $types ) {
				$types[] = 'product';
				return $types;
			}
		);

		// Re-run to pick up the filtered list.
		$columns = new RIWTH_Admin_Columns();
		$columns->add_filters_and_actions();

		$this->assertTrue( has_filter( 'manage_product_posts_columns' ) !== false );

		remove_all_filters( 'riwth_custom_columns_post_types' );
	}

	public function test_display_feedback_column_no_feedback_yet() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		ob_start();
		$this->columns->display_feedback_column( 'helpful_feedback', $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No feedback yet', $output );
	}

	public function test_display_feedback_column_with_feedback() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 3, 1 );

		ob_start();
		$this->columns->display_feedback_column( 'helpful_feedback', $post_id );
		$output = ob_get_clean();

		$this->assertStringContainsString( '75', $output );
	}

	public function test_display_feedback_column_ignores_other_columns() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		ob_start();
		$this->columns->display_feedback_column( 'title', $post_id );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_admin_column_content_filter_can_replace_output() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		add_filter(
			'riwth_admin_column_content',
			function () {
				return 'Replaced content';
			}
		);

		ob_start();
		$this->columns->display_feedback_column( 'helpful_feedback', $post_id );
		$output = ob_get_clean();

		$this->assertSame( 'Replaced content', $output );

		remove_all_filters( 'riwth_admin_column_content' );
	}

	public function test_make_feedback_column_sortable() {
		$columns = $this->columns->make_feedback_column_sortable( array() );
		$this->assertArrayHasKey( 'helpful_feedback', $columns );
	}

	// -----------------------------------------------------------------
	// SQL-injection attempt via $_GET['order'] / $_GET['orderby']
	// -----------------------------------------------------------------

	public function test_orderby_whitelists_order_direction_against_injection() {
		$_GET['order'] = "ASC; DROP TABLE wp_posts;--";

		$clause = $this->columns->feedback_posts_orderby( '' );

		$this->assertStringNotContainsString( 'DROP TABLE', $clause );
		// Anything not exactly 'ASC' collapses to 'DESC'.
		$this->assertStringContainsString( 'COALESCE(feedback_stats.feedback_percentage, 0) DESC,', $clause );
	}

	public function test_orderby_accepts_valid_asc() {
		$_GET['order'] = 'asc';

		$clause = $this->columns->feedback_posts_orderby( '' );

		$this->assertStringContainsString( 'COALESCE(feedback_stats.feedback_percentage, 0) ASC,', $clause );
	}

	public function test_query_with_malicious_order_get_params_does_not_error() {
		set_current_screen( 'edit-post' );
		$_GET['order']   = "'; DROP TABLE wp_posts; --";
		$_GET['orderby'] = "'; DROP TABLE wp_posts; --";

		$post_id = RIWTH_Test_Factory::create_published_post();

		$query = new WP_Query(
			array(
				'post_type' => 'post',
				'orderby'   => 'helpful_feedback',
				'order'     => $_GET['order'],
			)
		);

		$this->assertFalse( $query->is_wp_error() ?? false );
		$this->assertNotEmpty( $query->posts );

		// The wp_posts table must still exist (nothing was actually dropped).
		global $wpdb;
		$this->assertSame( $wpdb->posts, $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->posts}'" ) );
	}

	public function test_feedback_posts_groupby_defaults_to_post_id() {
		global $wpdb;
		$groupby = $this->columns->feedback_posts_groupby( '' );
		$this->assertSame( "{$wpdb->posts}.ID", $groupby );
	}

	public function test_feedback_posts_fields_appends_calculated_fields() {
		$fields = $this->columns->feedback_posts_fields( 'wp_posts.*' );
		$this->assertStringContainsString( 'feedback_stats.total_feedback', $fields );
	}
}
