<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Box' ) ) {
	class RI_WTH_Box {

		public function __construct() {
			add_filter( 'the_content', array( $this, 'add_feedback_box' ) );
		}

		public function add_feedback_box( $content ) {
			if ( RI_WTH_Functions::should_display_box() ) {
				$content .= $this->feedback_box_code();
			}
			return $content;
		}

		public function feedback_box_code() {
			$nonce = wp_create_nonce( 'ri_was_this_helpful_nonce' );
			$code  = '
                <div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback">
                    <div class="ri-wth-text">' . __( 'Was this helpful?', 'ri-was-this-helpful' ) . '</div>
                    <div class="ri-wth-buttons-container">
                    <button id="ri-wth-helpful-yes" class="helpful-yes" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">👍</button>
                    <button id="ri-wth-helpful-no" class="helpful-no" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">👎</button>
                    </div>
                </div>
            ';
			return $code;
		}
	}


}
