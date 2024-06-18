<?php

class RI_WTH_Functions {

	// Function to get the positive feedback count for a post
	public static function get_positive_feedback_count( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ri_helpful_feedback';

		$positive_feedback = wp_cache_get( 'ri_wth_positive_feedback_' . $post_id );
		if ( false === $positive_feedback ) {
			$positive_feedback = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND helpful = 1", $post_id ) );
			wp_cache_set( 'ri_wth_positive_feedback_' . $post_id, $positive_feedback, '', 24 * 60 * 60 );
		}

		return $positive_feedback;
	}

	// Function to get the positive feedback count for a post
	public static function get_total_feedback_count( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ri_helpful_feedback';

		$total_feedback = wp_cache_get( 'ri_wth_total_feedback_' . $post_id );
		if ( false === $total_feedback ) {
			$total_feedback = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id ) );
			wp_cache_set( 'ri_wth_total_feedback_' . $post_id, $total_feedback, '', 24 * 60 * 60 );
		}

		return $total_feedback;
	}
}
