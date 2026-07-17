<?php
/**
 * Tests for RIWTH_Metabox.
 *
 * @package RIWTH
 */

class Test_RIWTH_Metabox extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Metabox
	 */
	private $metabox;

	public function set_up() {
		parent::set_up();
		$this->metabox = new RIWTH_Metabox();
	}

	public function tear_down() {
		unset( $_POST['riwth_metabox_nonce'], $_POST['riwth_disable_box'] );
		if ( defined( 'DOING_AUTOSAVE' ) ) {
			// Can't undefine a constant; nothing further to do — PHPUnit
			// process isolation is not used here so this should never leak
			// across files in this suite.
			null;
		}
		parent::tear_down();
	}

	public function test_add_metabox_registers_for_public_post_types() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );

		global $wp_meta_boxes;
		$wp_meta_boxes = array();
		$this->metabox->add_metabox();

		$this->assertArrayHasKey( 'riwth_metabox', $wp_meta_boxes['post']['side']['default'] );
	}

	public function test_save_metabox_noop_without_nonce() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		unset( $_POST['riwth_metabox_nonce'] );

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, '_riwth_disable_box', true ) );
	}

	public function test_save_metabox_noop_with_invalid_nonce() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		$_POST['riwth_metabox_nonce'] = 'invalid-nonce';
		$_POST['riwth_disable_box']   = '1';

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, '_riwth_disable_box', true ) );
	}

	public function test_save_metabox_noop_without_capability() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		wp_set_current_user( RIWTH_Test_Factory::create_subscriber_user() );
		$_POST['riwth_metabox_nonce'] = wp_create_nonce( 'riwth_metabox_nonce' );
		$_POST['riwth_disable_box']   = '1';

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, '_riwth_disable_box', true ) );
	}

	public function test_save_metabox_sets_meta_on_valid_request() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST['riwth_metabox_nonce'] = wp_create_nonce( 'riwth_metabox_nonce' );
		$_POST['riwth_disable_box']   = '1';

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( '1', get_post_meta( $post_id, '_riwth_disable_box', true ) );
	}

	public function test_save_metabox_deletes_meta_when_checkbox_unchecked() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		update_post_meta( $post_id, '_riwth_disable_box', '1' );
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST['riwth_metabox_nonce'] = wp_create_nonce( 'riwth_metabox_nonce' );
		unset( $_POST['riwth_disable_box'] );

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( '', get_post_meta( $post_id, '_riwth_disable_box', true ) );
	}

	public function test_save_metabox_fires_before_and_after_actions() {
		$post_id = RIWTH_Test_Factory::create_published_post();
		wp_set_current_user( RIWTH_Test_Factory::create_admin_user() );
		$_POST['riwth_metabox_nonce'] = wp_create_nonce( 'riwth_metabox_nonce' );
		$_POST['riwth_disable_box']   = '1';

		$before_args = null;
		$after_args  = null;
		add_action( 'riwth_before_save_metabox', function ( $id ) use ( &$before_args ) {
			$before_args = $id;
		} );
		add_action( 'riwth_after_save_metabox', function ( $id ) use ( &$after_args ) {
			$after_args = $id;
		} );

		$this->metabox->save_metabox( $post_id );

		$this->assertSame( $post_id, $before_args );
		$this->assertSame( $post_id, $after_args );
	}
}
