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
		 * Sort direction validated in order_by_feedback(), consumed by feedback_posts_orderby()
		 * so both stay in sync regardless of what triggered the query (URL sort click, REST, cron, etc.).
		 *
		 * @var string 'ASC'|'DESC'
		 */
		private $sort_order = 'DESC';

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

			$post_types = apply_filters( 'riwth_custom_columns_post_types', array( 'post', 'page' ) );
			$enabled    = get_option( 'riwth_display_on', array() );

			$this->post_types = array_values( array_intersect( $post_types, $enabled ) );

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
				$total_feedback          = RIWTH_Functions::get_total_feedback_count( $post_id );
				$positive_feedback_count = RIWTH_Functions::get_positive_feedback_count( $post_id );

				if ( $total_feedback > 0 ) {
					$percentage = ( $positive_feedback_count / $total_feedback ) * 100;
					$color      = RIWTH_Functions::green_yellow_red( round( $percentage ) );
					$content    = '<span style="background-color: ' . esc_attr( $color ) . '; margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block;"></span>'
						/* translators: %1$d: positive percentage. %2$d Positive feedback. %3$d total feedback. */
						. esc_html( sprintf( __( '%1$d%% positive (%2$d/%3$d)', 'riaco-was-this-helpful' ), round( $percentage ), $positive_feedback_count, $total_feedback ) );
				} else {
					$content = esc_html( __( 'No feedback yet', 'riaco-was-this-helpful' ) );
				}

				echo wp_kses_post( apply_filters( 'riwth_admin_column_content', $content, $post_id, $total_feedback, $positive_feedback_count ) );
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
				// Get the order direction (ASC or DESC).
				$order            = $query->get( 'order' );
				$order            = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';
				$this->sort_order = $order;

				// Modify the query to join with feedback table and calculate percentage.
				$query->set( 'orderby', 'feedback_percentage' );
				$query->set( 'order', $order );

				// Add filters to modify the SQL query.
				add_filter( 'posts_join', array( $this, 'feedback_posts_join' ) );
				add_filter( 'posts_fields', array( $this, 'feedback_posts_fields' ) );
				add_filter( 'posts_orderby', array( $this, 'feedback_posts_orderby' ) );
				add_filter( 'posts_groupby', array( $this, 'feedback_posts_groupby' ) );

				// Unhook once this query has resolved so these SQL-modifying filters
				// don't leak into any other WP_Query run later in the same request
				// (dashboard widgets, other plugins' admin queries, etc.).
				add_filter( 'the_posts', array( $this, 'unhook_feedback_query_filters' ), 10, 2 );
			}
		}

		/**
		 * Remove the SQL-modifying filters added in order_by_feedback() right
		 * after the query they were added for has resolved.
		 *
		 * @param array    $posts Query results, passed through unchanged.
		 * @param WP_Query $query The query that just resolved.
		 * @return array
		 */
		public function unhook_feedback_query_filters( $posts, $query ) {
			remove_filter( 'posts_join', array( $this, 'feedback_posts_join' ) );
			remove_filter( 'posts_fields', array( $this, 'feedback_posts_fields' ) );
			remove_filter( 'posts_orderby', array( $this, 'feedback_posts_orderby' ) );
			remove_filter( 'posts_groupby', array( $this, 'feedback_posts_groupby' ) );
			remove_filter( 'the_posts', array( $this, 'unhook_feedback_query_filters' ), 10 );

			return $posts;
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
			// Add the calculated fields to SELECT. MAX() satisfies ONLY_FULL_GROUP_BY (the
			// default SQL mode on MySQL 5.7+/MariaDB 10.2.4+) since the subquery already
			// guarantees at most one feedback_stats row per post_id.
			$fields .= ', MAX(feedback_stats.total_feedback) AS total_feedback, MAX(feedback_stats.positive_feedback) AS positive_feedback, MAX(feedback_stats.feedback_percentage) AS feedback_percentage';

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

			// Use the direction validated in order_by_feedback() rather than re-reading $_GET,
			// so this stays correct for any query (REST, cron, programmatic WP_Query) that sets
			// orderby=helpful_feedback without a matching $_GET['order'].
			$order = $this->sort_order;

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
