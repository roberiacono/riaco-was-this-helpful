<?php
/**
 * Tests for RIWTH_Was_This_Helpful::maybe_enqueue_scripts()/admin_enqueue_scripts().
 *
 * @package RIWTH
 */

class Test_RIWTH_Enqueue extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Was_This_Helpful
	 */
	private $instance;

	public function set_up() {
		parent::set_up();
		$this->instance = RIWTH_Was_This_Helpful::get_instance();
	}

	public function tear_down() {
		wp_dequeue_script( 'riwth-script' );
		wp_dequeue_style( 'riwth-style' );
		wp_dequeue_style( 'riwth-admin-style' );
		wp_deregister_script( 'riwth-script' );
		wp_deregister_style( 'riwth-style' );
		wp_deregister_style( 'riwth-admin-style' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_registers_style_and_script_when_options_enabled() {
		update_option( 'riwth_load_styles', 1 );
		update_option( 'riwth_load_scripts', 1 );

		$this->instance->maybe_enqueue_scripts();

		$this->assertTrue( wp_style_is( 'riwth-style', 'registered' ) );
		$this->assertTrue( wp_script_is( 'riwth-script', 'registered' ) );
	}

	public function test_does_not_register_when_options_disabled() {
		wp_deregister_style( 'riwth-style' );
		wp_deregister_script( 'riwth-script' );
		update_option( 'riwth_load_styles', 0 );
		update_option( 'riwth_load_scripts', 0 );

		$this->instance->maybe_enqueue_scripts();

		$this->assertFalse( wp_style_is( 'riwth-style', 'registered' ) );
		$this->assertFalse( wp_script_is( 'riwth-script', 'registered' ) );
	}

	public function test_enqueues_only_when_should_display_box() {
		update_option( 'riwth_load_styles', 1 );
		update_option( 'riwth_load_scripts', 1 );
		update_option( 'riwth_display_on', array( 'post' ) );

		global $post;
		$post = null;

		$this->instance->maybe_enqueue_scripts();

		$this->assertFalse( wp_style_is( 'riwth-style', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'riwth-script', 'enqueued' ) );

		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$this->instance->maybe_enqueue_scripts();

		$this->assertTrue( wp_style_is( 'riwth-style', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'riwth-script', 'enqueued' ) );
	}

	public function test_localize_script_data_filter_adds_extra_keys() {
		update_option( 'riwth_load_scripts', 1 );

		add_filter(
			'riwth_localize_script_data',
			function ( $data ) {
				$data['extra_key'] = 'extra_value';
				return $data;
			}
		);

		$this->instance->maybe_enqueue_scripts();

		$data = wp_scripts()->get_data( 'riwth-script', 'data' );
		$this->assertStringContainsString( 'extra_value', $data );

		remove_all_filters( 'riwth_localize_script_data' );
	}

	public function test_admin_enqueue_scripts_on_plugin_page() {
		set_current_screen( 'toplevel_page_riwth-settings' );

		$this->instance->admin_enqueue_scripts();

		$this->assertTrue( wp_style_is( 'riwth-admin-style', 'enqueued' ) );
	}

	public function test_admin_enqueue_scripts_not_on_unrelated_page() {
		set_current_screen( 'edit.php' );

		$this->instance->admin_enqueue_scripts();

		$this->assertFalse( wp_style_is( 'riwth-admin-style', 'enqueued' ) );
	}
}
