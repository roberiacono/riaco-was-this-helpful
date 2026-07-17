<?php
/**
 * Tests for RIWTH_Reset_Stats.
 *
 * Note: riwth_handle_reset_stats_action() ends with wp_safe_redirect(); exit;
 * on its success path. Only the guard clauses (missing params, capability,
 * nonce — all via wp_die(), which the WP test harness turns into a
 * catchable WPDieException) are exercised here; see the plan's "Known,
 * deliberate coverage limits" section for why the post-exit success path
 * cannot be run in-process.
 *
 * @package RIWTH
 */

class Test_RIWTH_Reset_Stats extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Reset_Stats
	 */
	private $reset_stats;

	public function set_up() {
		parent::set_up();
		$this->reset_stats = new RIWTH_Reset_Stats();
	}

	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	// -----------------------------------------------------------------
	// riwth_add_reset_link()
	// -----------------------------------------------------------------

	public function test_reset_link_added_for_manage_options_and_allowed_post_type() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post = self::factory()->post->create_and_get();
		// riwth_add_reset_link() checks get_post_type() (the global current
		// post), not $post->post_type from its own parameter — matching how
		// WP_Posts_List_Table actually calls this filter (inside the loop,
		// with the row's post set up as the global current post).
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$actions = $this->reset_stats->riwth_add_reset_link( array(), $post );

		$this->assertArrayHasKey( 'riwth_reset', $actions );
		$this->assertStringContainsString( 'riwth_reset_stats', $actions['riwth_reset'] );
	}

	public function test_reset_link_not_added_without_capability() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post = self::factory()->post->create_and_get();
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$actions = $this->reset_stats->riwth_add_reset_link( array(), $post );

		$this->assertArrayNotHasKey( 'riwth_reset', $actions );
	}

	public function test_reset_link_not_added_for_disallowed_post_type() {
		update_option( 'riwth_display_on', array( 'page' ) );
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'post' ) );
		$GLOBALS['post'] = $post;
		setup_postdata( $post );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$actions = $this->reset_stats->riwth_add_reset_link( array(), $post );

		$this->assertArrayNotHasKey( 'riwth_reset', $actions );
	}

	// -----------------------------------------------------------------
	// riwth_handle_reset_stats_action() — guard clauses only
	// -----------------------------------------------------------------

	public function test_handle_reset_stats_dies_without_get_params() {
		unset( $_GET['post'], $_GET['_wpnonce'] );

		$this->expectException( WPDieException::class );
		$this->reset_stats->riwth_handle_reset_stats_action();
	}

	public function test_handle_reset_stats_dies_with_invalid_nonce() {
		$post_id        = self::factory()->post->create();
		$_GET['post']     = $post_id;
		$_GET['_wpnonce'] = 'invalid';
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$this->expectException( WPDieException::class );
		$this->reset_stats->riwth_handle_reset_stats_action();
	}

	public function test_handle_reset_stats_dies_without_capability() {
		$post_id          = self::factory()->post->create();
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );
		$_GET['post']     = $post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'riwth_reset_stats_' . $post_id );

		$this->expectException( WPDieException::class );
		$this->reset_stats->riwth_handle_reset_stats_action();
	}

	// -----------------------------------------------------------------
	// riwth_add_reset_button_to_meta_box()
	// -----------------------------------------------------------------

	public function test_reset_button_no_output_without_reset_date() {
		$post = self::factory()->post->create_and_get();

		ob_start();
		$this->reset_stats->riwth_add_reset_button_to_meta_box( $post );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}

	public function test_reset_button_shows_valid_reset_date() {
		$post = self::factory()->post->create_and_get();
		update_post_meta( $post->ID, '_riwth_reset_date', '2024-01-01 12:00:00' );

		ob_start();
		$this->reset_stats->riwth_add_reset_button_to_meta_box( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( '2024-01-01 12:00:00', $output );
	}

	public function test_reset_button_no_output_for_unparseable_date() {
		$post = self::factory()->post->create_and_get();
		update_post_meta( $post->ID, '_riwth_reset_date', 'not-a-real-date' );

		ob_start();
		$this->reset_stats->riwth_add_reset_button_to_meta_box( $post );
		$output = ob_get_clean();

		$this->assertSame( '', $output );
	}
}
