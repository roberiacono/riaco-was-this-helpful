<?php
/**
 * Class RIWTH_Block
 *
 * @package RIACO_Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Block' ) ) {
	/**
	 * Class RIWTH_Block
	 *
	 * This class handles the registration and rendering of the "Was This Helpful?" feedback block.
	 */
	class RIWTH_Block {

		/**
		 * Constructor for the RIWTH_Block class.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'riwth_register_feedback_block' ) );
			add_action( 'enqueue_block_assets', array( $this, 'enqueue_if_block_is_present' ) ); // Can only be loaded in the footer
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		}

		/**
		 * Register the feedback block.
		 */
		public function riwth_register_feedback_block() {

			if ( ! function_exists( 'register_block_type' ) ) {
				// Block editor is not available.
				return;
			}

			register_block_type(
				RIWTH_PLUGIN_DIR . 'helpful-box-block/build',
				array(
					'render_callback' => array( $this, 'render_feedback_block' ),

					'attributes'      => array(

						'helpfulBox' => array(
							'default' => $this->get_feedback_block_for_editor(),
							'type'    => 'string',
						),
					),
				)
			);
		}


		/**
		 *  Enqueue assets on frontend if a block is there
		 */
		public function enqueue_if_block_is_present() {

			if ( has_block( 'riaco-was-this-helpful/helpful-box-block' ) ) {
				/*
				if ( get_option( 'riwth_load_styles' ) ) {
					wp_enqueue_style( 'riwth-style' );
				} */
				if ( get_option( 'riwth_load_scripts' ) ) {
					wp_enqueue_script( 'riwth-script' );
				}
			}
		}

		/**
		 * Enqueue Editor assets.
		 */
		public function enqueue_editor_assets() {
			if ( get_option( 'riwth_load_styles' ) ) {
				wp_enqueue_style( 'riwth-style', RIWTH_PLUGIN_URL . 'assets/public/css/style.css', array(), RIWTH_PLUGIN_VERSION );
			}
		}

		/**
		 * Render the feedback block.
		 */
		public function render_feedback_block() {
			if ( ! RIWTH_Functions::feedback_given( get_the_ID() ) ) {
				return RIWTH_Box::feedback_box_code();
			}
			return false;
		}

		/**
		 * Get the feedback block code for the editor.
		 */
		public function get_feedback_block_for_editor() {
				return RIWTH_Box::feedback_box_code();
		}
	}
}
