<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Block' ) ) {
	class RI_WTH_Block {

		public function __construct() {
			add_action( 'init', array( $this, 'ri_wth_register_feedback_block' ) );
			add_action( 'enqueue_block_assets', array( $this, 'enqueue_if_block_is_present' ) ); // Can only be loaded in the footer
			add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		}

		public function ri_wth_register_feedback_block() {

			if ( ! function_exists( 'register_block_type' ) ) {
				// Block editor is not available.
				return;
			}

			register_block_type(
				RI_WTH_PLUGIN_DIR . 'helpful-box-block/build',
				array(
					'render_callback' => array( $this, 'render_feedback_block' ),

					'attributes'      => array(

						'helpfulBox' => array(
							'default' => $this->get_feedback_block_for_editor(),
							'type'    => 'string',
						),
						/*
						'nonce'              => array(
							'default' => RI_WTH_Box::get_feedback_box_nonce(),
							'type'    => 'string',
						),
						'feedbackBoxText'    => array(
							'default' => RI_WTH_Box::get_feedback_box_text(),
							'type'    => 'string',
						),
						'positiveButtonText' => array(
							'default' => RI_WTH_Box::get_feedback_box_button_text( 'positive' ),
							'type'    => 'string',
						),
						'positiveButtonIcon' => array(
							'default' => RI_WTH_Box::get_feedback_box_button_icon( 'positive' ),
							'type'    => 'string',
						),
						'negativeButtonText' => array(
							'default' => RI_WTH_Box::get_feedback_box_button_text( 'negative' ),
							'type'    => 'string',
						),
						'negativeButtonIcon' => array(
							'default' => RI_WTH_Box::get_feedback_box_button_icon( 'negative' ),
							'type'    => 'string',
						), */
					),
				)
			);
		}


		// enqueue assets on frontend if a block is there
		public function enqueue_if_block_is_present() {

			if ( has_block( 'ri-was-this-helpful/helpful-box-block' ) ) {
				if ( get_option( 'ri_wth_load_styles' ) ) {
					wp_enqueue_style( 'ri-wth-style' );
				}
				if ( get_option( 'ri_wth_load_scripts' ) ) {
					wp_enqueue_script( 'ri-wth-script' );
				}
			}
		}

		/**
		 * Enqueue Editor assets.
		 */
		function enqueue_editor_assets() {
			if ( get_option( 'ri_wth_load_styles' ) ) {
				wp_enqueue_style( 'ri-wth-style', RI_WTH_PLUGIN_URL . 'public/css/style.css', array(), RI_WTH_PLUGIN_VERSION );
			}
		}

		public function render_feedback_block() {
			if ( RI_WTH_Functions::could_display_box() ) {
				return RI_WTH_Box::feedback_box_code();
			}
			return false;
		}
		public function get_feedback_block_for_editor() {

				return RI_WTH_Box::feedback_box_code();
		}
	}
}
