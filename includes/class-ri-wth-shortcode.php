<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Shortcode' ) ) {
	class RI_WTH_Shortcode {

		public function __construct() {
			add_shortcode( 'helpful_box', array( $this, 'shortcode_func' ) );
		}

		public function shortcode_func( $atts ) {
			if ( RI_WTH_Functions::could_display_box() ) {

				if ( RI_WTH_Functions::feedback_given( get_the_ID() ) ) {
					return false;
				}

				if ( get_option( 'ri_wth_load_styles' ) ) {
					wp_enqueue_style( 'ri-wth-style' );
				}
				if ( get_option( 'ri_wth_load_scripts' ) ) {
					wp_enqueue_script( 'ri-wth-script' );
				}

				do_action('before_show_helpful_box_using_shortcode');

				$helpful_box = new RI_WTH_Box();
				return $helpful_box->feedback_box_code();
			}
			return false;
		}
	}
}
