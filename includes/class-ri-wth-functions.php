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
				$base_query = "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND helpful = 1";

				// Apply a filter to allow the Pro plugin to modify the query
				$reset_date_condition = apply_filters( 'ri_wth_get_positive_feedback_count_query', '', $post_id );

				$query = $wpdb->prepare( "$base_query $reset_date_condition", $post_id );

				$positive_feedback = $wpdb->get_var( $query );

				wp_cache_set( 'ri_wth_positive_feedback_' . $post_id, $positive_feedback, '', 24 * 60 * 60 );
			}

			return $positive_feedback;
		}

		// Function to get the positive feedback count for a post
		public static function get_total_feedback_count( $post_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RI_WTH_DB_NAME;

			$total_feedback = wp_cache_get( 'ri_wth_total_feedback_' . $post_id );
			if ( false === $total_feedback ) {
				$base_query = "SELECT COUNT(*) FROM $table_name WHERE post_id = %d";

				// Apply a filter to allow the Pro plugin to modify the query
				$reset_date_condition = apply_filters( 'ri_wth_get_total_feedback_count_query', '', $post_id );

				$query = $wpdb->prepare( "$base_query $reset_date_condition", $post_id );

				$total_feedback = $wpdb->get_var( $query );
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
	}
}
