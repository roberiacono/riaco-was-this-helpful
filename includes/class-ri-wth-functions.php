<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Functions' ) ) {
	class RI_WTH_Functions {

		// Function to get the positive feedback count for a post
		public static function get_positive_feedback_count( $post_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RI_WTH_DB_NAME;

			$positive_feedback = wp_cache_get( 'ri_wth_positive_feedback_' . $post_id );
			if ( false === $positive_feedback ) {
				$positive_feedback = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT( * ) FROM %i WHERE post_id = %d and helpful = 1', array( $table_name, $post_id ) ) );
				// Apply a filter to allow the Pro plugin to modify the query
				$positive_feedback = apply_filters( 'ri_wth_get_positive_feedback_filter', $positive_feedback, $table_name, $post_id );
				wp_cache_set( 'ri_wth_positive_feedback_' . $post_id, $positive_feedback, '', 24 * 60 * 60 );
			}
				return $positive_feedback;
		}

		// Function to get the positive feedback count for a post
		public static function get_total_feedback_count( $post_id ) {
			global $wpdb;
			$table_name     = $wpdb->prefix . RI_WTH_DB_NAME;
			$total_feedback = wp_cache_get( 'ri_wth_total_feedback_' . $post_id );
			if ( false === $total_feedback ) {
				$total_feedback = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE post_id = %d', array( $table_name, $post_id ) ) );
				// Apply a filter to allow the Pro plugin to modify the query
				$total_feedback = apply_filters( 'ri_wth_get_total_feedback_filter', $total_feedback, $table_name, $post_id );
				wp_cache_set( 'ri_wth_total_feedback_' . $post_id, $total_feedback, '', 24 * 60 * 60 );
			}

			return $total_feedback;
		}

		public static function should_display_box() {
			global $post;

			// If we don't have a post object, return false
			if ( ! $post ) {
				return false;
			}

			// Check if the box is disabled for the current post
			$disable_box = get_post_meta( $post->ID, '_ri_wth_disable_box', true );
			if ( '1' === $disable_box ) {
				return false;
			}

			return self::could_display_box();
		}

		public static function could_display_box() {
			global $post;

			// If we don't have a post object, return false

			if ( ! $post ) {
				return false;
			}

			$options = get_option( 'ri_wth_display_on', array() );
			$options = is_array( $options ) ? $options : array();

			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();

				if ( is_admin() && is_main_query() && in_array( $screen->base, $options ) && in_array( get_post_type(), $options ) ) {
					return true;
				}
			}

			if ( is_main_query() && is_singular() && in_array( get_post_type(), $options ) ) {
				return true;
			}
			return false;
		}

		public static function GreenYellowRed( $number ) {
			--$number; // working with 0-99 will be easier
			if ( $number < 0 ) {
				$number = 0;
			}

			// invert color scale
			$number = 99 - $number;

			if ( $number < 50 ) {
				// green to yellow
				$r = floor( 255 * ( $number / 50 ) );
				$g = 255;

			} else {
				// yellow to red
				$r = 255;
				$g = floor( 255 * ( ( 50 - $number % 50 ) / 50 ) );
			}
			$b = 0;

			return "$r,$g,$b";
		}

		/**
		 * Sanitize SVG markup for front-end display.
		 *
		 * @param  string $svg SVG markup to sanitize.
		 * @return string      Sanitized markup.
		 */
		public static function sanitize_svg( $svg = '' ) {
			$allowed_html = array(
				'svg'    => array(
					'xmlns'           => array(),
					'class'           => array(),
					'fill'            => array(),
					'viewbox'         => array(),
					'role'            => array(),
					'aria-hidden'     => array(),
					'focusable'       => array(),
					'height'          => array(),
					'width'           => array(),
					'stroke'          => array(),
					'stroke-width'    => array(),
					'stroke-linecap'  => array(),
					'stroke-linejoin' => array(),
				),
				'path'   => array(
					'd'    => array(),
					'fill' => array(),
				),
				'circle' => array(
					'cx' => array(),
					'cy' => array(),
					'r'  => array(),
				),
				'line'   => array(
					'x1' => array(),
					'x2' => array(),
					'y1' => array(),
					'y2' => array(),
				),
			);

			return wp_kses( $svg, $allowed_html );
		}

	}
}
