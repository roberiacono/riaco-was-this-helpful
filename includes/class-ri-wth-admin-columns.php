<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Admin_Columns' ) ) {
	class RI_WTH_Admin_Columns {

		private $post_types;

		public function __construct() {
			add_action( 'admin_init', array( $this, 'add_filters_and_actions' ) );
		}

		public function add_filters_and_actions() {

			$this->post_types = apply_filters( 'ri_wth_custom_columns_post_types', array( 'post', 'page' ) );

			foreach ( $this->post_types as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_feedback_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'display_feedback_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_feedback_column_sortable' ) );
			}

			add_action( 'pre_get_posts', array( $this, 'order_by_feedback' ) );
		}

		public function add_feedback_column( $columns ) {
			$columns['helpful_feedback'] = __( 'Was this helpful?', 'ri-was-this-helpful' );
			return $columns;
		}

		public function display_feedback_column( $column, $post_id ) {
			if ( $column == 'helpful_feedback' ) {
				global $wpdb;
				$table_name = $wpdb->prefix . RI_WTH_DB_NAME;

				$total_feedback = RI_WTH_Functions::get_total_feedback_count( $post_id );

				$positive_feedback_count = RI_WTH_Functions::get_positive_feedback_count( $post_id );

				if ( $total_feedback > 0 ) {
					$percentage = ( $positive_feedback_count / $total_feedback ) * 100;
					$rgb        = RI_WTH_Functions::GreenYellowRed( round( $percentage ) );
					echo '<span style="background-color: rgb(' . esc_html( $rgb ) . '); margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block;"></span>';
					echo esc_html( round( $percentage ) . '% ' . __( 'positive', 'ri-was-this-helpful' ) . ' (' . $positive_feedback_count . '/' . $total_feedback . ')' );
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
				$table_name = $wpdb->prefix . RI_WTH_DB_NAME;

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
}
