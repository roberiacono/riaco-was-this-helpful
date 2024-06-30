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

			$feedback_box_text    = self::get_feedback_box_text();
			$positive_button_text = self::get_feedback_box_button_text( 'positive' );
			$negative_button_text = self::get_feedback_box_button_text( 'negative' );

			if ( false === ( $attr = get_transient( 'ri_wth_feedback_box' ) ) ) {
				$attr = array(
					'feedback_box_style'    => self::get_feedback_box_style(),
					'positive_button_icon'  => self::get_feedback_box_button_icon( 'positive' ),
					'positive_button_style' => self::get_feedback_box_button_style( 'positive' ),
					'negative_button_icon'  => self::get_feedback_box_button_icon( 'negative' ),
					'negative_button_style' => self::get_feedback_box_button_style( 'negative' ),
				);
				set_transient( 'ri_wth_feedback_box', $attr, 365 * DAY_IN_SECONDS );
			}
			$feedback_box_style = $attr['feedback_box_style'];

			$positive_button_icon  = $attr['positive_button_icon'];
			$positive_button_style = $attr['positive_button_style'];

			$negative_button_icon  = $attr['negative_button_icon'];
			$negative_button_style = $attr['negative_button_style'];

			$code  = '<div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback" style="' . esc_attr( $feedback_box_style ) . '">';
			$code .= '<div class="ri-wth-text">' . esc_html( $feedback_box_text ) . '</div>';
			$code .= '<div class="ri-wth-buttons-container">';
			$code .= '<button id="ri-wth-helpful-yes" class="helpful-yes" style="' . esc_attr( $positive_button_style ) . '" data-post_id="' . get_the_ID() . '" data-nonce="' . esc_attr( $nonce ) . '">';
			$code .= wp_kses_post( $positive_button_text );
			$code .= RI_WTH_Functions::sanitize_svg( $positive_button_icon );
			$code .= '</button>';
			$code .= '<button id="ri-wth-helpful-no" class="helpful-no" style="' . esc_attr( $negative_button_style ) . '" data-post_id="' . get_the_ID() . '" data-nonce="' . esc_attr( $nonce ) . '">';
			$code .= wp_kses_post( $negative_button_text );
			$code .= RI_WTH_Functions::sanitize_svg( $negative_button_icon );
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
				return 'background-color:' . $bg_color . ';';
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

		public static function get_feedback_box_button_style( $type ) {
			if ( ! in_array( $type, array( 'positive', 'negative' ) ) ) {
				return;
			}

			$return = '';

			$button_color = get_option( 'ri_wth_feedback_box_color_' . $type . '_button' );
			if ( $button_color ) {
				$return .= 'background-color: ' . $button_color . '; ';
			}

			$text_color = get_option( 'ri_wth_feedback_box_color_' . $type . '_text' );
			if ( $text_color ) {
				$return .= 'color: ' . $text_color . '; ';
			}

			$border_radius = get_option( 'ri_wth_feedback_box_border_button_rounded' );
			if ( $border_radius ) {
				$return .= 'border-radius: ' . $border_radius . '%; ';
			}

			return $return;
		}
	}
}
