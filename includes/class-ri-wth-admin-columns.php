<?php

class RI_WTH_Admin_Columns {

	public function __construct() {
		add_filter( 'manage_posts_columns', array( $this, 'add_feedback_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'display_feedback_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'make_feedback_column_sortable' ) );
		add_action( 'pre_get_posts', array( $this, 'order_by_feedback' ) );
	}

	public function add_feedback_column( $columns ) {
		$columns['helpful_feedback'] = __( 'Was this helpful?', 'ri-was-this-helpful' );
		return $columns;
	}

	public function display_feedback_column( $column, $post_id ) {
		if ( $column == 'helpful_feedback' ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'ri_helpful_feedback';

			$total_feedback = wp_cache_get( 'ri_wth_total_feedback_' . $post_id );
			if ( false === $total_feedback ) {
				$total_feedback = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id ) );
				wp_cache_set( 'ri_wth_total_feedback_' . $post_id, $total_feedback, '', 24 * 60 * 60 );
			}

			$positive_feedback = wp_cache_get( 'ri_wth_positive_feedback_' . $post_id );
			if ( false === $positive_feedback ) {
				$positive_feedback = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND helpful = 1", $post_id ) );
				wp_cache_set( 'ri_wth_positive_feedback_' . $post_id, $positive_feedback, '', 24 * 60 * 60 );
			}

			if ( $total_feedback > 0 ) {
				$percentage = ( $positive_feedback / $total_feedback ) * 100;
				echo esc_html( round( $percentage, 2 ) . '% ' . __( 'positive', 'ri-was-this-helpful' ) . ' (' . $positive_feedback . '/' . $total_feedback . ')' );
			} else {
				echo esc_html( __( 'No feedback yet', 'ri-was-this-helpful' ) );
			}
		}
	}

	public function make_feedback_column_sortable( $columns ) {
		$columns['helpful_feedback'] = 'helpful_feedback';
		return $columns;
	}

	public function order_by_feedback( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'helpful_feedback' === $orderby ) {
			$order = strtoupper( $query->get( 'order' ) );

			global $wpdb;
			$table_name = $wpdb->prefix . 'ri_helpful_feedback';

			// Adding the join to the feedback table
			add_filter(
				'posts_join',
				function ( $join ) use ( $wpdb, $table_name ) {
					$join .= " LEFT JOIN (
                    SELECT post_id, COUNT(*) as total_feedback, SUM(helpful) as positive_feedback 
                    FROM $table_name GROUP BY post_id
                ) as feedback_stats ON {$wpdb->posts}.ID = feedback_stats.post_id ";
					return $join;
				}
			);

			// Adding the order by clause
			add_filter(
				'posts_orderby',
				function ( $orderby ) use ( $order ) {
					$orderby = "feedback_stats.positive_feedback $order, feedback_stats.total_feedback $order, $orderby";
					return $orderby;
				}
			);
		}
	}
}

new RI_WTH_Admin_Columns();
