<?php
/**
 * Tests for RIWTH_User_Role.
 *
 * @package RIWTH
 */

class Test_RIWTH_User_Role extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_User_Role
	 */
	private $user_role;

	public function set_up() {
		parent::set_up();
		$this->user_role = new RIWTH_User_Role();
	}

	public function test_default_option_allows_administrator() {
		update_option( 'riwth_display_by_user_role', array( 'administrator', 'editor' ) );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$this->assertTrue( $this->user_role->can_user_see_stats() );
	}

	public function test_default_option_denies_subscriber() {
		update_option( 'riwth_display_by_user_role', array( 'administrator', 'editor' ) );
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$this->assertFalse( $this->user_role->can_user_see_stats() );
	}

	public function test_editor_allowed_when_in_option() {
		update_option( 'riwth_display_by_user_role', array( 'administrator', 'editor' ) );
		wp_set_current_user( RIWTH_Test_Factory::create_editor_user() );

		$this->assertTrue( $this->user_role->can_user_see_stats() );
	}

	public function test_logged_out_user_is_denied() {
		update_option( 'riwth_display_by_user_role', array( 'administrator', 'editor' ) );
		wp_set_current_user( 0 );

		$this->assertFalse( $this->user_role->can_user_see_stats() );
	}

	public function test_malformed_option_is_coerced_to_array() {
		update_option( 'riwth_display_by_user_role', 'not-an-array' );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$this->assertFalse( $this->user_role->can_user_see_stats() );
	}

	public function test_filter_can_force_true() {
		update_option( 'riwth_display_by_user_role', array() );
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		add_filter( 'riwth_can_user_see_stats', '__return_true' );
		$this->assertTrue( $this->user_role->can_user_see_stats() );
		remove_filter( 'riwth_can_user_see_stats', '__return_true' );
	}

	public function test_filter_can_force_false() {
		update_option( 'riwth_display_by_user_role', array( 'administrator' ) );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		add_filter( 'riwth_can_user_see_stats', '__return_false' );
		$this->assertFalse( $this->user_role->can_user_see_stats() );
		remove_filter( 'riwth_can_user_see_stats', '__return_false' );
	}

	public function test_filter_receives_user_object() {
		update_option( 'riwth_display_by_user_role', array( 'administrator' ) );
		$user_id = RIWTH_Test_Factory::create_admin_user();
		wp_set_current_user( $user_id );

		$captured = null;
		add_filter(
			'riwth_can_user_see_stats',
			function ( $can_see, $user ) use ( &$captured ) {
				$captured = $user;
				return $can_see;
			},
			10,
			2
		);

		$this->user_role->can_user_see_stats();

		$this->assertInstanceOf( WP_User::class, $captured );
		$this->assertSame( $user_id, $captured->ID );
	}
}
