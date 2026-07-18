<?php
/**
 * Tests for uninstall.php.
 *
 * uninstall.php is flat top-level procedural code (no function/class
 * wrapper), so it can be require()'d repeatedly per test. Two of its four
 * guards (`!defined('WP_UNINSTALL_PLUGIN')` and
 * `!current_user_can('delete_plugins')`) call a raw `exit;` on failure and
 * so cannot be tested in-process (see the plan's "Known, deliberate
 * coverage limits" section) — only their true/passing branch is exercised.
 * The other two guards (Pro-plugin escape hatch, the data-removal option
 * gate) use `return;`, which is safe to test both ways.
 *
 * The Pro-plugin-escape-hatch test permanently declares a dummy
 * RIWTH_Was_This_Helpful_Pro class for the rest of this PHP process, so it
 * must run last — it is the final method in this file and this file
 * ("test-uninstall.php") sorts alphabetically after every other
 * "test-class-riwth-*.php" integration test file.
 *
 * @package RIWTH
 */

class Test_RIWTH_Uninstall extends RIWTH_Test_Case {

	public function set_up() {
		parent::set_up();

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'riaco-was-this-helpful/riaco-was-this-helpful.php' );
		}

		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		// Reset every option uninstall.php might touch, to a known non-default state.
		foreach ( $this->all_uninstall_options() as $option ) {
			update_option( $option, 'seeded-value' );
		}
		update_option( 'riwth_uninstall_remove_data', 1 );

		set_transient( 'riwth_feedback_box', array( 'x' => 'y' ), DAY_IN_SECONDS );
		set_transient( 'riwth_review_notice_maybe_later', true, DAY_IN_SECONDS );
		set_transient( 'riwth_some_other_custom_transient', 'value', DAY_IN_SECONDS );
	}

	public function tear_down() {
		// Recreate what uninstall.php may have just dropped, so later tests/
		// files see a normal schema. Bypass WP_UnitTestCase's per-test
		// _create_temporary_tables filter first: without doing so, this
		// CREATE TABLE would be rewritten into a transaction-scoped
		// CREATE TEMPORARY TABLE that vanishes the instant this test's
		// transaction is rolled back a few lines down in parent::tear_down().
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		RIWTH_Was_This_Helpful::get_instance()->create_database();
		RIWTH_Was_This_Helpful::get_instance()->set_initial_settings();
		parent::tear_down();
	}

	private function all_uninstall_options() {
		return array(
			'riwth_display_on',
			'riwth_display_by_user_role',
			'riwth_load_styles',
			'riwth_load_scripts',
			'riwth_show_admin_bar_content',
			'riwth_feedback_box_template',
			'riwth_feedback_box_text',
			'riwth_feedback_box_positive_button_text',
			'riwth_feedback_box_positive_button_icon',
			'riwth_feedback_box_negative_button_text',
			'riwth_feedback_box_negative_button_icon',
			'riwth_feedback_box_color_background',
			'riwth_feedback_box_color_positive_button',
			'riwth_feedback_box_color_positive_text',
			'riwth_feedback_box_color_negative_button',
			'riwth_feedback_box_color_negative_text',
			'riwth_feedback_box_border_button_rounded',
			'riwth_thanks_link_migrated',
			'riwth_review_notice_done',
		);
	}

	public function test_uninstall_deletes_all_options_when_gate_enabled() {
		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		foreach ( $this->all_uninstall_options() as $option ) {
			$this->assertFalse( get_option( $option ), "Option '{$option}' should have been deleted" );
		}
		$this->assertFalse( get_option( 'riwth_uninstall_remove_data' ) );
	}

	public function test_uninstall_deletes_named_transients() {
		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		$this->assertFalse( get_transient( 'riwth_feedback_box' ) );
		$this->assertFalse( get_transient( 'riwth_review_notice_maybe_later' ) );
	}

	public function test_uninstall_sweeps_arbitrary_riwth_transients() {
		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		$this->assertFalse( get_transient( 'riwth_some_other_custom_transient' ) );
	}

	public function test_uninstall_drops_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'riwth_helpful_feedback';
		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );

		// The plugin table was created as a real (non-temporary) table
		// during the shared test bootstrap, before WP_UnitTestCase's own
		// per-test _create_temporary_tables/_drop_temporary_tables 'query'
		// filters existed. Left in place, _drop_temporary_tables would
		// rewrite our real "DROP TABLE" into "DROP TEMPORARY TABLE", which
		// silently no-ops against a non-temporary table — so bypass it for
		// this one query, matching what a real uninstall does outside tests.
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		$this->assertNull( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
	}

	public function test_uninstall_noop_when_gate_disabled() {
		update_option( 'riwth_uninstall_remove_data', 0 );

		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		$this->assertSame( 'seeded-value', get_option( 'riwth_feedback_box_text' ) );

		global $wpdb;
		$table = $wpdb->prefix . 'riwth_helpful_feedback';
		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
	}

	public function test_uninstall_noop_when_gate_option_never_set() {
		delete_option( 'riwth_uninstall_remove_data' );

		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		// get_option() has no default arg for this gate, so a never-set
		// option (false) is treated the same as an explicit "off" — an
		// intentional gap: the documented default of 1 only actually
		// applies once set_initial_settings() has run during activation.
		$this->assertSame( 'seeded-value', get_option( 'riwth_feedback_box_text' ) );
	}

	public function test_uninstall_does_not_clean_up_post_meta() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		update_post_meta( $post_id, '_riwth_disable_box', '1' );
		update_post_meta( $post_id, '_riwth_reset_date', '2024-01-01 00:00:00' );

		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		// Documents a known gap: uninstall.php removes options/transients/the
		// table but leaves per-post meta orphaned.
		$this->assertSame( '1', get_post_meta( $post_id, '_riwth_disable_box', true ) );
		$this->assertSame( '2024-01-01 00:00:00', get_post_meta( $post_id, '_riwth_reset_date', true ) );
	}

	/**
	 * Must run last: permanently declares RIWTH_Was_This_Helpful_Pro for the
	 * remainder of this PHP process.
	 */
	public function test_zzz_uninstall_aborts_entirely_when_pro_plugin_active() {
		eval( 'class RIWTH_Was_This_Helpful_Pro {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

		require RIWTH_PLUGIN_DIR . 'uninstall.php';

		// Nothing should have been touched.
		$this->assertSame( 'seeded-value', get_option( 'riwth_feedback_box_text' ) );
		global $wpdb;
		$table = $wpdb->prefix . 'riwth_helpful_feedback';
		$this->assertSame( $table, $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) );
	}
}
