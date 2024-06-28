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

		public static function feedback_box_code() {
			$nonce = self::get_feedback_box_nonce();

			$feedback_box_text  = self::get_feedback_box_text();
			$feedback_box_style = self::get_feedback_box_style();

			$positive_button_text = self::get_feedback_box_button_text( 'positive' );
			$positive_button_icon = self::get_feedback_box_button_icon( 'positive' );

			$negative_button_text = self::get_feedback_box_button_text( 'negative' );
			$negative_button_icon = self::get_feedback_box_button_icon( 'negative' );

			$code  = '<div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback" ' . $feedback_box_style . '>';
			$code .= '<div class="ri-wth-text">' . esc_html( $feedback_box_text ) . '</div>';
			$code .= '<div class="ri-wth-buttons-container">';
			$code .= '<button id="ri-wth-helpful-yes" class="helpful-yes" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">';
			$code .= $positive_button_text;
			$code .= $positive_button_icon;
			$code .= '</button>';
			$code .= '<button id="ri-wth-helpful-no" class="helpful-no" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">';
			$code .= $negative_button_text;
			$code .= $negative_button_icon;
			$code .= '</button>';
			$code .= '</div>';
			$code .= '</div>';

			return $code;
		}

		public static function get_feedback_box_nonce() {
			return wp_create_nonce( 'ri_was_this_helpful_nonce' );
		}

		public static function get_feedback_box_style() {
			$bg_color = get_option( 'ri_wth_feedback_box_color_background' );
			if ( $bg_color ) {
				return 'style="background-color:' . esc_attr( $bg_color ) . ';"';
			}
			return '';
		}

		public static function get_feedback_box_text() {
			return get_option( 'ri_wth_feedback_box_text' );
		}

		public static function get_feedback_box_button_icon( $type ) {
			if ( ! in_array( $type, array( 'positive', 'negative' ) ) ) {
				return;
			}

			$svg_icons = $type === 'positive' ? RI_WTH_SVG_Icons::get_svg_positive_icons() : RI_WTH_SVG_Icons::get_svg_negative_icons();

			$button_icon = get_option( 'ri_wth_feedback_box_' . $type . '_button_icon' );
			if ( ! $button_icon || $button_icon === 'empty' ) {
				$button_icon = '';
			} else {
				$button_icon = $svg_icons[ $button_icon ];
			}
			return $button_icon;
		}

		public static function get_feedback_box_button_text( $type ) {
			if ( ! in_array( $type, array( 'positive', 'negative' ) ) ) {
				return;
			}

			$button_text = get_option( 'ri_wth_feedback_box_' . $type . '_button_text' );
			if ( $button_text ) {
				$button_text = '<span> ' . $button_text . '</span>';
			}
			return $button_text;
		}
	}
}
