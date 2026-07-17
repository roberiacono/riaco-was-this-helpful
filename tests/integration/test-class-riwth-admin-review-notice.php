<?php
/**
 * Tests for RIWTH_Admin_Review_Notice (non-AJAX behavior).
 *
 * Note: unlike RIWTH_Admin_Pages_Footer / RIWTH_Admin_Bar (which use
 * get_current_screen()), this class detects "am I on my own admin page" via
 * the global $hook_suffix, so tests set that global directly instead of
 * using set_current_screen(). Its AJAX handler (wp_ajax_riwth_review_action)
 * is covered separately in tests/ajax/.
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Review_Notice extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Admin_Review_Notice
	 */
	private $notice;

	public function set_up() {
		parent::set_up();
		$this->notice = new RIWTH_Admin_Review_Notice();
		delete_option( 'riwth_review_notice_done' );
		delete_transient( 'riwth_review_notice_maybe_later' );
	}

	public function tear_down() {
		unset( $GLOBALS['hook_suffix'] );
		delete_option( 'riwth_review_notice_done' );
		delete_transient( 'riwth_review_notice_maybe_later' );
		parent::tear_down();
	}

	private function seed_total_feedback( $count ) {
		$post_id = RIWTH_Test_Factory::create_published_post();
		for ( $i = 0; $i < $count; $i++ ) {
			RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );
		}
	}

	public function test_no_output_off_plugin_page() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		$this->seed_total_feedback( 150 );

		ob_start();
		$this->notice->maybe_show_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_no_output_when_done_option_set() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		update_option( 'riwth_review_notice_done', 1 );
		$this->seed_total_feedback( 150 );

		ob_start();
		$this->notice->maybe_show_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_no_output_when_maybe_later_transient_set() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		set_transient( 'riwth_review_notice_maybe_later', true, DAY_IN_SECONDS );
		$this->seed_total_feedback( 150 );

		ob_start();
		$this->notice->maybe_show_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_no_output_below_threshold() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		$this->seed_total_feedback( 5 );

		ob_start();
		$this->notice->maybe_show_notice();
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_renders_when_threshold_met() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		$this->seed_total_feedback( 100 );

		ob_start();
		$this->notice->maybe_show_notice();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'riwth-review-notice', $output );
		$this->assertStringContainsString( '100', $output );
	}

	public function test_overall_total_feedback_is_cached() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		$this->seed_total_feedback( 5 );

		ob_start();
		$this->notice->maybe_show_notice();
		ob_get_clean();

		// get_overall_total_feedback() never casts $wpdb->get_var()'s result,
		// so both cache layers store the raw string the DB driver returns.
		$this->assertSame( '5', wp_cache_get( 'riwth_overall_total_feedback', 'riwth_feedback' ) );
		$this->assertSame( '5', get_transient( 'riwth_overall_total_feedback' ) );
	}

	public function test_enqueue_scripts_only_on_plugin_page() {
		$GLOBALS['hook_suffix'] = 'toplevel_page_riwth-settings';
		set_current_screen( 'toplevel_page_riwth-settings' );

		$this->notice->enqueue_scripts();

		$this->assertTrue( wp_script_is( 'riwth-review-notice', 'enqueued' ) );

		wp_dequeue_script( 'riwth-review-notice' );
		wp_deregister_script( 'riwth-review-notice' );
		set_current_screen( 'front' );
	}

	public function test_enqueue_scripts_not_on_unrelated_page() {
		$GLOBALS['hook_suffix'] = 'edit.php';
		set_current_screen( 'edit.php' );

		$this->notice->enqueue_scripts();

		$this->assertFalse( wp_script_is( 'riwth-review-notice', 'enqueued' ) );

		set_current_screen( 'front' );
	}
}
