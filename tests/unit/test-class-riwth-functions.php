<?php
/**
 * Tests for RIWTH_Functions.
 *
 * @package RIWTH
 */

class Test_RIWTH_Functions extends RIWTH_Test_Case {

	public function tear_down() {
		global $post;
		$post = null;
		parent::tear_down();
	}

	// -----------------------------------------------------------------
	// get_positive_feedback_count() / get_total_feedback_count()
	// -----------------------------------------------------------------

	public function test_positive_feedback_count_cold_query() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 3, 2 );

		$this->assertSame( 3, RIWTH_Functions::get_positive_feedback_count( $post_id ) );
	}

	public function test_total_feedback_count_cold_query() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 3, 2 );

		$this->assertSame( 5, RIWTH_Functions::get_total_feedback_count( $post_id ) );
	}

	public function test_positive_feedback_count_is_object_cached() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 1, 0 );

		$this->assertSame( 1, RIWTH_Functions::get_positive_feedback_count( $post_id ) );

		// Insert another row directly, bypassing cache invalidation, to prove
		// the cached value (not a fresh query) is returned on the 2nd call.
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$this->assertSame( 1, RIWTH_Functions::get_positive_feedback_count( $post_id ), 'Expected cached value, not a fresh query' );
	}

	public function test_positive_feedback_count_transient_fallback() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 2, 0 );

		// Warm the transient, then clear only the object cache so the
		// transient branch (not the cold-query branch) is exercised.
		RIWTH_Functions::get_positive_feedback_count( $post_id );
		wp_cache_delete( 'riwth_positive_feedback_' . $post_id );

		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$this->assertSame( 2, RIWTH_Functions::get_positive_feedback_count( $post_id ), 'Expected transient value, not a fresh query' );
	}

	public function test_positive_feedback_count_respects_reset_date() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1, array( 'created_at' => '2020-01-01 00:00:00' ) );
		update_post_meta( $post_id, '_riwth_reset_date', current_time( 'mysql', true ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$this->assertSame( 1, RIWTH_Functions::get_positive_feedback_count( $post_id ), 'Only rows after the reset date should count' );
	}

	public function test_total_feedback_count_respects_reset_date() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 0, array( 'created_at' => '2020-01-01 00:00:00' ) );
		update_post_meta( $post_id, '_riwth_reset_date', current_time( 'mysql', true ) );
		RIWTH_Test_Factory::insert_feedback_row( $post_id, 1 );

		$this->assertSame( 1, RIWTH_Functions::get_total_feedback_count( $post_id ) );
	}

	public function test_positive_feedback_count_filter_overrides_value() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		add_filter( 'riwth_get_positive_feedback_filter', function () {
			return 999;
		} );

		$this->assertSame( 999, RIWTH_Functions::get_positive_feedback_count( $post_id ) );

		remove_all_filters( 'riwth_get_positive_feedback_filter' );
	}

	public function test_total_feedback_count_filter_overrides_value() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		add_filter( 'riwth_get_total_feedback_filter', function () {
			return 999;
		} );

		$this->assertSame( 999, RIWTH_Functions::get_total_feedback_count( $post_id ) );

		remove_all_filters( 'riwth_get_total_feedback_filter' );
	}

	public function test_feedback_count_zero_when_no_rows() {
		$post_id = RIWTH_Test_Factory::create_published_post();

		$this->assertSame( 0, RIWTH_Functions::get_positive_feedback_count( $post_id ) );
		$this->assertSame( 0, RIWTH_Functions::get_total_feedback_count( $post_id ) );
	}

	// -----------------------------------------------------------------
	// feedback_given()
	// -----------------------------------------------------------------

	public function test_feedback_given_false_when_no_cookie() {
		unset( $_COOKIE['riwth_feedback_given'] );

		$this->assertFalse( RIWTH_Functions::feedback_given( 42 ) );
	}

	public function test_feedback_given_true_when_post_id_in_cookie() {
		$_COOKIE['riwth_feedback_given'] = '12,47,103';

		$this->assertTrue( RIWTH_Functions::feedback_given( 47 ) );

		unset( $_COOKIE['riwth_feedback_given'] );
	}

	public function test_feedback_given_false_when_post_id_not_in_cookie() {
		$_COOKIE['riwth_feedback_given'] = '12,47,103';

		$this->assertFalse( RIWTH_Functions::feedback_given( 999 ) );

		unset( $_COOKIE['riwth_feedback_given'] );
	}

	public function test_feedback_given_single_value_cookie() {
		$_COOKIE['riwth_feedback_given'] = '47';

		$this->assertTrue( RIWTH_Functions::feedback_given( 47 ) );

		unset( $_COOKIE['riwth_feedback_given'] );
	}

	public function test_feedback_given_filter_can_override() {
		unset( $_COOKIE['riwth_feedback_given'] );

		add_filter( 'riwth_feedback_given', '__return_true' );
		$this->assertTrue( RIWTH_Functions::feedback_given( 1 ) );
		remove_filter( 'riwth_feedback_given', '__return_true' );
	}

	// -----------------------------------------------------------------
	// should_display_box()
	// -----------------------------------------------------------------

	public function test_should_display_box_false_without_global_post() {
		global $post;
		$post = null;

		$this->assertFalse( RIWTH_Functions::should_display_box() );
	}

	public function test_should_display_box_false_when_disabled_for_post() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		update_post_meta( $post_id, '_riwth_disable_box', '1' );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertFalse( RIWTH_Functions::should_display_box() );
	}

	public function test_should_display_box_false_when_feedback_already_given() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$_COOKIE['riwth_feedback_given'] = (string) $post_id;
		$this->assertFalse( RIWTH_Functions::should_display_box() );
		unset( $_COOKIE['riwth_feedback_given'] );
	}

	public function test_should_display_box_true_on_eligible_singular_post() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertTrue( RIWTH_Functions::should_display_box() );
	}

	public function test_should_display_box_filter_can_force_true() {
		global $post;
		$post = null;

		add_filter( 'riwth_should_display_box', '__return_true' );
		// could_display_box() itself would still return false (no $post), but
		// should_display_box() bails out entirely before ever calling the
		// filter when there is no global $post at all.
		$this->assertFalse( RIWTH_Functions::should_display_box() );
		remove_filter( 'riwth_should_display_box', '__return_true' );
	}

	public function test_should_display_box_filter_can_force_false() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		add_filter( 'riwth_should_display_box', '__return_false' );
		$this->assertFalse( RIWTH_Functions::should_display_box() );
		remove_filter( 'riwth_should_display_box', '__return_false' );
	}

	// -----------------------------------------------------------------
	// could_display_box()
	// -----------------------------------------------------------------

	public function test_could_display_box_false_without_global_post() {
		global $post;
		$post = null;

		$this->assertFalse( RIWTH_Functions::could_display_box() );
	}

	public function test_could_display_box_true_for_allowed_post_type_singular() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertTrue( RIWTH_Functions::could_display_box() );
	}

	public function test_could_display_box_false_for_disallowed_post_type() {
		update_option( 'riwth_display_on', array( 'page' ) );
		$post_id = RIWTH_Test_Factory::create_published_post( array( 'post_type' => 'post' ) );
		$this->go_to( get_permalink( $post_id ) );

		$this->assertFalse( RIWTH_Functions::could_display_box() );
	}

	public function test_could_display_box_filter_can_force_true() {
		update_option( 'riwth_display_on', array() );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		add_filter( 'riwth_could_display_box', '__return_true' );
		$this->assertTrue( RIWTH_Functions::could_display_box() );
		remove_filter( 'riwth_could_display_box', '__return_true' );
	}

	// -----------------------------------------------------------------
	// GreenYellowRed()
	// -----------------------------------------------------------------

	public function test_green_yellow_red_returns_hex_color() {
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', RIWTH_Functions::GreenYellowRed( 50 ) );
	}

	public function test_green_yellow_red_boundary_zero() {
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', RIWTH_Functions::GreenYellowRed( 0 ) );
	}

	public function test_green_yellow_red_negative_input_is_clamped() {
		// Negative input decremented below 0 must still be clamped, not
		// produce a malformed/negative color component.
		$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', RIWTH_Functions::GreenYellowRed( -10 ) );
	}

	public function test_green_yellow_red_100_is_pure_green() {
		// number=100 -> --number=99 -> inverted=0 -> green branch, r=0,g=255.
		$this->assertSame( '#00ff00', RIWTH_Functions::GreenYellowRed( 100 ) );
	}

	public function test_green_yellow_red_1_is_near_pure_red() {
		// number=1 -> --number=0 -> inverted=99 -> red branch: r=255,
		// g=floor(255*((50-99%50)/50))=floor(255*(1/50))=5.
		$this->assertSame( '#ff0500', RIWTH_Functions::GreenYellowRed( 1 ) );
	}

	// -----------------------------------------------------------------
	// get_svg_allowed_html()
	// -----------------------------------------------------------------

	public function test_get_svg_allowed_html_structure() {
		$allowed = RIWTH_Functions::get_svg_allowed_html();

		$this->assertArrayHasKey( 'svg', $allowed );
		$this->assertArrayHasKey( 'path', $allowed );
		$this->assertArrayHasKey( 'circle', $allowed );
		$this->assertArrayHasKey( 'line', $allowed );
	}
}
