<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Shortcode' ) ) {
	class RI_WTH_Shortcode {

		public function __construct() {
			add_shortcode( 'helpful_box', array( $this, 'shortcode_func' ) );
		}

		public function shortcode_func( $atts ) {
			if ( RI_WTH_Functions::should_display_box() ) {
				$helpful_box = new RI_WTH_Box();
				return $helpful_box->feedback_box_code();
			}
			return false;
		}
	}
}
