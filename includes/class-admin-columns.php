<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Admin_Columns' ) ) {
	class RIWTH_Admin_Columns {

		private $post_types;

		public function __construct() {
			add_action( 'admin_init', array( $this, 'add_filters_and_actions' ) );
		}

		public function add_filters_and_actions() {

			$this->post_types = apply_filters( 'riwth_custom_columns_post_types', array( 'post', 'page' ) );

			foreach ( $this->post_types as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_feedback_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'display_feedback_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_feedback_column_sortable' ) );
			}

			add_action( 'pre_get_posts', array( $this, 'order_by_feedback' ) );
		}

		public function add_feedback_column( $columns ) {
			$columns['helpful_feedback'] = esc_html__( 'Was this helpful?', 'riaco-was-this-helpful' );
			return $columns;
		}

		public function display_feedback_column( $column, $post_id ) {
			if ( $column == 'helpful_feedback' ) {
				global $wpdb;
				$table_name = $wpdb->prefix . RIWTH_DB_NAME;

				$total_feedback = RIWTH_Functions::get_total_feedback_count( $post_id );

				$positive_feedback_count = RIWTH_Functions::get_positive_feedback_count( $post_id );

				if ( $total_feedback > 0 ) {
					$percentage = ( $positive_feedback_count / $total_feedback ) * 100;
					$rgb        = RIWTH_Functions::GreenYellowRed( round( $percentage ) );
					echo '<span style="background-color: rgb(' . esc_attr( $rgb ) . '); margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block;"></span>';
					/* translators: %1$d: positive percentage. %2$d Positive feedback. %3$d total feedback. */
					echo esc_html( sprintf( __( '%1$d%% positive (%2$d/%3$d)', 'riaco-was-this-helpful' ), round( $percentage ), $positive_feedback_count, $total_feedback ) );
				} else {
					echo esc_html( __( 'No feedback yet', 'riaco-was-this-helpful' ) );
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
				$table_name = $wpdb->prefix . RIWTH_DB_NAME;

				// Adding the join to the feedback table

				add_filter(
					'posts_join',
					function ( $join ) use ( $wpdb, $table_name ) {
						$join .= $wpdb->prepare(
							" LEFT JOIN (
					SELECT post_id, COUNT(*) as total_feedback, SUM(helpful) as positive_feedback
					FROM %i GROUP BY post_id
					) as feedback_stats ON {$wpdb->posts}.ID = feedback_stats.post_id ",
							$table_name
						);
						return $join;
					}
				);

				// Adding the order by clause
				add_filter(
					'posts_orderby',
					function ( $orderby ) use ( $order ) {
						$orderby = "feedback_stats.positive_feedback/feedback_stats.total_feedback $order,  $orderby";
						return $orderby;
					}
				);

			}
		}
	}
}
