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

				// Get the order direction (ASC or DESC)
				$order = $query->get( 'order' );
				$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

				// Modify the query to join with feedback table and calculate percentage
				$query->set( 'meta_query', array() );
				$query->set( 'orderby', 'feedback_percentage' );
				$query->set( 'order', $order );

				// Add filters to modify the SQL query
				add_filter( 'posts_join', array( $this, 'feedback_posts_join' ) );
				add_filter( 'posts_fields', array( $this, 'feedback_posts_fields' ) );
				add_filter( 'posts_orderby', array( $this, 'feedback_posts_orderby' ) );
				add_filter( 'posts_groupby', array( $this, 'feedback_posts_groupby' ) );

			}
		}

		public function feedback_posts_join( $join ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RIWTH_DB_NAME;

			// Left join with feedback table and post meta to consider reset date
			$join .= " LEFT JOIN {$wpdb->postmeta} pm_reset ON {$wpdb->posts}.ID = pm_reset.post_id AND pm_reset.meta_key = '_riwth_reset_date'";

			$join .= " LEFT JOIN (
				SELECT 
					f.post_id,
					COUNT(*) as total_feedback,
					SUM(CASE WHEN f.helpful = 1 THEN 1 ELSE 0 END) as positive_feedback,
					CASE 
						WHEN COUNT(*) > 0 THEN (SUM(CASE WHEN f.helpful = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*))
						ELSE 0 
					END as feedback_percentage
				FROM {$table_name} f
				LEFT JOIN {$wpdb->postmeta} pm ON f.post_id = pm.post_id AND pm.meta_key = '_riwth_reset_date'
				WHERE (
					pm.meta_value IS NULL 
					OR f.created_at > pm.meta_value
				)
				GROUP BY f.post_id
			) feedback_stats ON {$wpdb->posts}.ID = feedback_stats.post_id";

			return $join;
		}

		public function feedback_posts_fields( $fields ) {
			global $wpdb;

			// Add the calculated fields to SELECT
			$fields .= ', feedback_stats.total_feedback, feedback_stats.positive_feedback, feedback_stats.feedback_percentage';

			return $fields;
		}

		public function feedback_posts_orderby( $orderby ) {
			global $wpdb;

			$order = isset( $_GET['order'] ) ? strtoupper( $_GET['order'] ) : 'DESC';
			$order = ( 'ASC' === $order ) ? 'ASC' : 'DESC';

			// Reverse order for total feedback count (more feedback = higher priority)
			$feedback_count_order = ( 'ASC' === $order ) ? 'DESC' : 'ASC';

			// Order by:
			// 1. Posts with feedback first, then posts without feedback
			// 2. Feedback percentage (primary sort)
			// 3. Total feedback count (secondary sort - more feedback = higher priority)
			// 4. Post ID as final tiebreaker for consistency
			$orderby = "CASE 
                    WHEN feedback_stats.total_feedback IS NULL OR feedback_stats.total_feedback = 0 THEN 1 
                    ELSE 0 
                END ASC, 
                COALESCE(feedback_stats.feedback_percentage, 0) {$order},
                COALESCE(feedback_stats.total_feedback, 0) DESC,
                {$wpdb->posts}.ID ASC";

			return $orderby;
		}

		public function feedback_posts_groupby( $groupby ) {
			global $wpdb;

			// Group by post ID to avoid duplicates
			if ( ! $groupby ) {
				$groupby = "{$wpdb->posts}.ID";
			}

			return $groupby;
		}
	}
}
