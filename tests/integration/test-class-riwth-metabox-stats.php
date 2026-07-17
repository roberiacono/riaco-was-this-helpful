<?php
/**
 * Tests for RIWTH_Metabox_Stats.
 *
 * @package RIWTH
 */

class Test_RIWTH_Metabox_Stats extends RIWTH_Test_Case {

	/**
	 * @var RIWTH_Metabox_Stats
	 */
	private $metabox;

	public function set_up() {
		parent::set_up();
		$this->metabox = new RIWTH_Metabox_Stats();
	}

	public function test_render_metabox_no_feedback_yet() {
		$post = self::factory()->post->create_and_get();

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No feedback yet', $output );
	}

	public function test_render_metabox_with_feedback_percentage() {
		$post = self::factory()->post->create_and_get();
		RIWTH_Test_Factory::seed_feedback( $post->ID, 3, 1 );

		ob_start();
		$this->metabox->render_metabox( $post );
		$output = ob_get_clean();

		$this->assertStringContainsString( '75', $output );
	}

	public function test_after_metabox_stats_action_fires_with_post() {
		$post = self::factory()->post->create_and_get();

		$captured = null;
		add_action( 'riwth_after_metabox_stats', function ( $p ) use ( &$captured ) {
			$captured = $p;
		} );

		ob_start();
		$this->metabox->render_metabox( $post );
		ob_get_clean();

		$this->assertSame( $post->ID, $captured->ID );
	}
}
