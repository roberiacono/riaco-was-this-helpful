<?php
/**
 * Tests for RIWTH_Admin_Bar.
 *
 * @package RIWTH
 */

class Test_RIWTH_Admin_Bar extends RIWTH_Test_Case {

	public function set_up() {
		parent::set_up();
		if ( ! class_exists( 'WP_Admin_Bar' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		}
	}

	public function tear_down() {
		global $post;
		$post = null;
		parent::tear_down();
	}

	public function test_hook_registered_when_option_enabled() {
		update_option( 'riwth_show_admin_bar_content', '1' );
		$admin_bar = new RIWTH_Admin_Bar();

		$this->assertNotFalse( has_action( 'admin_bar_menu', array( $admin_bar, 'add_feedback_count_to_admin_bar' ) ) );
	}

	public function test_hook_not_registered_when_option_disabled() {
		update_option( 'riwth_show_admin_bar_content', '0' );
		$admin_bar = new RIWTH_Admin_Bar();

		$this->assertFalse( has_action( 'admin_bar_menu', array( $admin_bar, 'add_feedback_count_to_admin_bar' ) ) );
	}

	public function test_no_node_added_without_capability() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );

		$admin_bar = new RIWTH_Admin_Bar();
		$wp_admin_bar = new WP_Admin_Bar();
		$admin_bar->add_feedback_count_to_admin_bar( $wp_admin_bar );

		$this->assertNull( $wp_admin_bar->get_node( 'riwth-feedback-count' ) );
	}

	public function test_node_added_with_no_feedback_yet() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$admin_bar = new RIWTH_Admin_Bar();
		$wp_admin_bar = new WP_Admin_Bar();
		$admin_bar->add_feedback_count_to_admin_bar( $wp_admin_bar );

		$node = $wp_admin_bar->get_node( 'riwth-feedback-count' );
		$this->assertNotNull( $node );
		$this->assertStringContainsString( 'No feedback yet', $node->title );
	}

	public function test_node_added_with_feedback_percentage() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		RIWTH_Test_Factory::seed_feedback( $post_id, 1, 1 );
		$this->go_to( get_permalink( $post_id ) );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		$admin_bar = new RIWTH_Admin_Bar();
		$wp_admin_bar = new WP_Admin_Bar();
		$admin_bar->add_feedback_count_to_admin_bar( $wp_admin_bar );

		$node = $wp_admin_bar->get_node( 'riwth-feedback-count' );
		$this->assertStringContainsString( '50', $node->title );
	}
}
