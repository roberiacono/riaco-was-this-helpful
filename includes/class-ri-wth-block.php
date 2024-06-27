<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Block' ) ) {
	class RI_WTH_Block {

		public function __construct() {
			add_action( 'init', array( $this, 'ri_wth_register_feedback_block' ) );
		}

		public function ri_wth_register_feedback_block() {

			if ( ! function_exists( 'register_block_type' ) ) {
				// Block editor is not available.
				return;
			}

			register_block_type(
				RI_WTH_PLUGIN_DIR . 'helpful-box-block/build',
				array(
					'render_callback' => array( $this, 'ri_wth_render_feedback_block' ),
					/*
					'attributes'      => array(
						'helpfulBox' => array(
							'default' => $this->ri_wth_render_feedback_block_editor(),
							'type'    => 'string',
						),
					), */
				)
			);
		}


		public function ri_wth_render_feedback_block() {
			// return 'Wholesome Plugin - hello from the editor! <div style="background-color: #fff;">Qualcosa</div>';

			/*
			if ( RI_WTH_Functions::should_display_box() ) {
				$helpful_box = new RI_WTH_Box();
				return $helpful_box->feedback_box_code();
			}
			return false; */

			if ( RI_WTH_Functions::should_display_box() ) {
				return RI_WTH_Box::feedback_box_code();
			}
			return false;
		}
	}
}
