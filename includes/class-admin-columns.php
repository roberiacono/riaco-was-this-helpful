<?php
/**
 * Admin Columns Class
 *
 * @package RIACO_Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Admin_Columns' ) ) {
	/**
	 * Class RIWTH_Admin_Columns
	 *
	 * Adds custom columns to post and page list tables to show feedback statistics.
	 */
	class RIWTH_Admin_Columns {

		/**
		 * Post types to add custom columns to.
		 *
		 * @var array
		 */
		private $post_types;

		/**
		 * Constructor for the RIWTH_Admin_Columns class.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'add_filters_and_actions' ) );
		}

		/**
		 * Add necessary filters and actions for custom columns.
		 */
		public function add_filters_and_actions() {

			$this->post_types = apply_filters( 'riwth_custom_columns_post_types', array( 'post', 'page' ) );

			foreach ( $this->post_types as $post_type ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_feedback_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'display_feedback_column' ), 10, 2 );
				add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'make_feedback_column_sortable' ) );
			}

			add_action( 'pre_get_posts', array( $this, 'order_by_feedback' ) );
		}

		/**
		 * Add a custom column for feedback statistics.
		 *
		 * @param array $columns Existing columns.
		 */
		public function add_feedback_column( $columns ) {
			$columns['helpful_feedback'] = esc_html__( 'Was this helpful?', 'riaco-was-this-helpful' );
			return $columns;
		}

		/**
		 * Display feedback statistics in the custom column.
		 *
		 * @param string $column The name of the column.
		 * @param int    $post_id The ID of the post.
		 */
		public function display_feedback_column( $column, $post_id ) {
			if ( 'helpful_feedback' === $column ) {
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

		/**
		 * Make the feedback column sortable.
		 *
		 * @param array $columns Existing sortable columns.
		 */
		public function make_feedback_column_sortable( $columns ) {
			$columns['helpful_feedback'] = 'helpful_feedback';
			return $columns;
		}

		/**
		 * Modify the query to sort by feedback percentage and total feedback count.
		 *
		 * @param WP_Query $query The current WP_Query instance.
		 */
		public function order_by_feedback( $query ) {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$orderby = $query->get( 'orderby' );

			if ( 'helpful_feedback' === $orderby ) {
				$order = strtoupper( $query->get( 'order' ) );

				// Get the order direction (ASC or DESC).
				$order = $query->get( 'order' );
				$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

				// Modify the query to join with feedback table and calculate percentage.
				$query->set( 'meta_query', array() );
				$query->set( 'orderby', 'feedback_percentage' );
				$query->set( 'order', $order );

				// Add filters to modify the SQL query.
				add_filter( 'posts_join', array( $this, 'feedback_posts_join' ) );
				add_filter( 'posts_fields', array( $this, 'feedback_posts_fields' ) );
				add_filter( 'posts_orderby', array( $this, 'feedback_posts_orderby' ) );
				add_filter( 'posts_groupby', array( $this, 'feedback_posts_groupby' ) );

			}
		}

		/**
		 * Modify the JOIN clause to include feedback statistics.
		 *
		 * @since 2.1.1
		 * @param string $join The existing JOIN clause.
		 */
		public function feedback_posts_join( $join ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RIWTH_DB_NAME;

			// Left join with feedback table and post meta to consider reset date.
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

		/**
		 * Modify the SELECT fields to include feedback statistics.
		 *
		 * @since 2.1.1
		 * @param string $fields The existing SELECT fields.
		 */
		public function feedback_posts_fields( $fields ) {
			global $wpdb;

			// Add the calculated fields to SELECT.
			$fields .= ', feedback_stats.total_feedback, feedback_stats.positive_feedback, feedback_stats.feedback_percentage';

			return $fields;
		}

		/**
		 * Modify the ORDER BY clause to sort by feedback percentage and total feedback count.
		 *
		 * @since 2.1.1 update SQL to handle sorting
		 * @param string $orderby The existing ORDER BY clause.
		 * @return string Modified ORDER BY clause.
		 */
		public function feedback_posts_orderby( $orderby ) {
			global $wpdb;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Safe usage, sorting only, no data mutation.
			$order = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC';
			$order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
			$order = ( 'ASC' === $order ) ? 'ASC' : 'DESC';

			// Reverse order for total feedback count (more feedback = higher priority).
			$feedback_count_order = ( 'ASC' === $order ) ? 'DESC' : 'ASC';

			// Order by:
			// 1. Posts with feedback first, then posts without feedback.
			// 2. Feedback percentage (primary sort).
			// 3. Total feedback count (secondary sort - more feedback = higher priority).
			// 4. Post ID as final tiebreaker for consistency.
			$orderby = "CASE 
                    WHEN feedback_stats.total_feedback IS NULL OR feedback_stats.total_feedback = 0 THEN 1 
                    ELSE 0 
                END ASC, 
                COALESCE(feedback_stats.feedback_percentage, 0) {$order},
                COALESCE(feedback_stats.total_feedback, 0) DESC,
                {$wpdb->posts}.ID ASC";

			return $orderby;
		}

		/**
		 * Modify the GROUP BY clause to group by post ID.
		 *
		 * @since 2.1.1
		 * @param string $groupby The existing GROUP BY clause.
		 */
		public function feedback_posts_groupby( $groupby ) {
			global $wpdb;

			// Group by post ID to avoid duplicates.
			if ( ! $groupby ) {
				$groupby = "{$wpdb->posts}.ID";
			}

			return $groupby;
		}
	}
}
