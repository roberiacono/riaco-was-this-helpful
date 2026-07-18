<?php
/**
 * Tests for RIWTH_Block.
 *
 * @package RIWTH
 */

class Test_RIWTH_Block extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Block
	 */
	private $block;

	public function set_up() {
		parent::set_up();
		$this->block = new RIWTH_Block();
	}

	public function tear_down() {
		wp_dequeue_script( 'riwth-script' );
		wp_dequeue_style( 'riwth-style' );
		wp_deregister_script( 'riwth-script' );
		wp_deregister_style( 'riwth-style' );
		parent::tear_down();
	}

	public function test_block_is_registered() {
		// Already registered once via the real 'init' hook fired at bootstrap.
		$this->assertTrue(
			WP_Block_Type_Registry::get_instance()->is_registered( 'riaco-was-this-helpful/helpful-box-block' )
		);
	}

	public function test_block_has_no_persisted_html_attribute() {
		// The editor preview is fetched live via ServerSideRender (block-renderer REST
		// endpoint) rather than baked into a block attribute, so nothing stale ever
		// gets written into post_content when a post is saved without touching the block.
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( 'riaco-was-this-helpful/helpful-box-block' );

		$this->assertArrayNotHasKey( 'helpfulBox', (array) $block_type->attributes );
	}

	public function test_get_feedback_block_for_editor_returns_box_html() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$html = $this->block->get_feedback_block_for_editor();

		$this->assertStringContainsString( 'riwth-helpful-feedback', $html );
	}

	public function test_enqueue_if_block_is_present_enqueues_script() {
		update_option( 'riwth_load_scripts', 1 );
		// enqueue_if_block_is_present() only enqueues an already-registered
		// handle; registration normally happens in
		// RIWTH_Was_This_Helpful::maybe_enqueue_scripts().
		wp_register_script( 'riwth-script', RIWTH_PLUGIN_URL . 'assets/public/js/script.js', array(), RIWTH_PLUGIN_VERSION, true );
		$post_id = RIWTH_Test_Factory::create_published_post(
			array( 'post_content' => '<!-- wp:riaco-was-this-helpful/helpful-box-block /-->' )
		);
		$this->go_to( get_permalink( $post_id ) );

		$this->block->enqueue_if_block_is_present();

		$this->assertTrue( wp_script_is( 'riwth-script', 'enqueued' ) );
	}

	public function test_enqueue_if_block_is_absent_does_not_enqueue() {
		update_option( 'riwth_load_scripts', 1 );
		wp_register_script( 'riwth-script', RIWTH_PLUGIN_URL . 'assets/public/js/script.js', array(), RIWTH_PLUGIN_VERSION, true );
		$post_id = RIWTH_Test_Factory::create_published_post( array( 'post_content' => 'Just text, no block.' ) );
		$this->go_to( get_permalink( $post_id ) );

		$this->block->enqueue_if_block_is_present();

		$this->assertFalse( wp_script_is( 'riwth-script', 'enqueued' ) );
	}

	public function test_render_feedback_block_returns_html_when_eligible() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();
		$this->go_to( get_permalink( $post_id ) );

		$output = $this->block->render_feedback_block();

		$this->assertStringContainsString( 'riwth-helpful-feedback', $output );
	}

	public function test_render_feedback_block_false_when_not_eligible() {
		global $post;
		$post = null;

		$this->assertFalse( $this->block->render_feedback_block() );
	}

	public function test_render_feedback_block_returns_html_during_rest_style_request() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post();

		// Simulate the block-renderer REST context: the main query never
		// resolves to a singular view (REST dispatch exits before
		// WP::query_posts() runs, so is_singular()/is_main_query() stay
		// false), but $post is populated via setup_postdata() exactly like
		// WP_REST_Block_Renderer_Controller::get_item() does when
		// ServerSideRender passes a post_id.
		$this->go_to( home_url( '/' ) );
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$output = $this->block->render_feedback_block();

		$this->assertStringContainsString( 'riwth-helpful-feedback', $output );
	}

	public function test_render_feedback_block_false_when_post_type_not_allowed_during_rest_style_request() {
		update_option( 'riwth_display_on', array( 'post' ) );
		$post_id = RIWTH_Test_Factory::create_published_post( array( 'post_type' => 'page' ) );

		$this->go_to( home_url( '/' ) );
		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		$this->assertFalse( $this->block->render_feedback_block() );
	}
}
