<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Shortcode' ) ) {
	class RIWTH_Shortcode {

		public function __construct() {
			add_shortcode( 'riwth_helpful_box', array( $this, 'shortcode_func' ) );
		}

		public function shortcode_func( $atts ) {
			if ( RIWTH_Functions::could_display_box() ) {

				if ( RIWTH_Functions::feedback_given( get_the_ID() ) ) {
					return false;
				}

				if ( get_option( 'riwth_load_styles' ) ) {
					wp_enqueue_style( 'riwth-style' );
				}
				if ( get_option( 'riwth_load_scripts' ) ) {
					wp_enqueue_script( 'riwth-script' );
				}

				do_action( 'riwth_before_show_helpful_box_using_shortcode' );

				$helpful_box = new RIWTH_Box();
				return $helpful_box->feedback_box_code();
			}
			return false;
		}
	}
}
