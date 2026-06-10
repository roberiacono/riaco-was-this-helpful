<?php
/**
 * Admin Feedback Records list, delete, and CSV export.
 *
 * @package RIACO\Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Admin_Feedback_List' ) ) {
	/**
	 * Admin page that lists all raw feedback rows with delete and CSV export.
	 */
	class RIWTH_Admin_Feedback_List {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_init', array( $this, 'maybe_export_csv' ) );
			add_action( 'admin_action_riwth_delete_feedback',     array( $this, 'handle_delete_single' ) );
			add_action( 'admin_action_riwth_delete_all_feedback', array( $this, 'handle_delete_all' ) );
			add_action( 'admin_notices', array( $this, 'show_action_notice' ) );
		}

		public function add_submenu_page() {
			add_submenu_page(
				'riwth-settings',
				__( 'Feedback Records', 'riaco-was-this-helpful' ),
				__( 'Feedback Records', 'riaco-was-this-helpful' ),
				'manage_options',
				'riwth-feedback-list',
				array( $this, 'render_page' )
			);
		}

		public function maybe_export_csv() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['page'], $_GET['export'] ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'riwth-feedback-list' !== $_GET['page'] || 'csv' !== $_GET['export'] ) {
				return;
			}
			$this->export_csv();
		}

		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You are not allowed to view this page.', 'riaco-was-this-helpful' ) );
			}

			require_once RIWTH_PLUGIN_DIR . 'templates/page-feedback-list.php';
		}

		/**
		 * Return a page of feedback rows ordered by newest first.
		 *
		 * @param int $page     1-based page number.
		 * @param int $per_page Rows per page.
		 * @return array
		 */
		public function get_feedback_records( $page = 1, $per_page = 20 ) {
			global $wpdb;
			$table  = $wpdb->prefix . RIWTH_DB_NAME;
			$offset = ( absint( $page ) - 1 ) * absint( $per_page );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, post_id, helpful, created_at FROM %i ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$wpdb->prefix . RIWTH_DB_NAME,
					$per_page,
					$offset
				)
			);
		}

		/**
		 * Return the total number of feedback rows.
		 *
		 * @return int
		 */
		public function get_total_records() {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->prefix . RIWTH_DB_NAME )
			);
		}

		/**
		 * Delete a single feedback row; bust per-post caches.
		 */
		public function handle_delete_single() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'riaco-was-this-helpful' ) );
			}

			$feedback_id = isset( $_GET['feedback_id'] ) ? absint( $_GET['feedback_id'] ) : 0;
			if ( ! $feedback_id || ! isset( $_GET['_wpnonce'] ) ) {
				wp_die( esc_html__( 'Invalid request.', 'riaco-was-this-helpful' ) );
			}
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'riwth_delete_feedback_' . $feedback_id ) ) {
				wp_die( esc_html__( 'Nonce check failed.', 'riaco-was-this-helpful' ) );
			}

			global $wpdb;

			// Fetch post_id before deleting so we can bust its cache.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT post_id FROM %i WHERE id = %d',
					$wpdb->prefix . RIWTH_DB_NAME,
					$feedback_id
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$deleted = $wpdb->delete( $wpdb->prefix . RIWTH_DB_NAME, array( 'id' => $feedback_id ), array( '%d' ) );

			if ( false === $deleted ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'page'         => 'riwth-feedback-list',
							'riwth_notice' => 'delete_failed',
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			if ( $post_id ) {
				wp_cache_delete( 'riwth_total_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_total_feedback_' . $post_id );
				wp_cache_delete( 'riwth_positive_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_positive_feedback_' . $post_id );
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'         => 'riwth-feedback-list',
						'riwth_notice' => 'deleted',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		/**
		 * Delete every feedback row and flush all related caches.
		 */
		public function handle_delete_all() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'riaco-was-this-helpful' ) );
			}
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'riwth_delete_all_feedback' ) ) {
				wp_die( esc_html__( 'Nonce check failed.', 'riaco-was-this-helpful' ) );
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare( 'DELETE FROM %i', $wpdb->prefix . RIWTH_DB_NAME )
			);

			wp_cache_flush();

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'         => 'riwth-feedback-list',
						'riwth_notice' => 'deleted_all',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		/**
		 * Show a success notice after a delete action.
		 */
		public function show_action_notice() {
			$screen = get_current_screen();
			if ( ! $screen || strpos( $screen->id, 'riwth-feedback-list' ) === false ) {
				return;
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- riwth_notice is a read-only redirect message, not form input.
			if ( ! isset( $_GET['riwth_notice'] ) ) {
				return;
			}

			$notice = sanitize_text_field( wp_unslash( $_GET['riwth_notice'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( 'deleted' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Feedback record deleted.', 'riaco-was-this-helpful' ) . '</p></div>';
			} elseif ( 'deleted_all' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All feedback records deleted.', 'riaco-was-this-helpful' ) . '</p></div>';
			} elseif ( 'delete_failed' === $notice ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Could not delete feedback record.', 'riaco-was-this-helpful' ) . '</p></div>';
			}
		}

		/**
		 * Stream all feedback as a CSV file download.
		 */
		private function export_csv() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'riaco-was-this-helpful' ) );
			}
			check_admin_referer( 'riwth_export_csv' );

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT post_id, helpful, created_at FROM %i ORDER BY created_at DESC',
					$wpdb->prefix . RIWTH_DB_NAME
				),
				ARRAY_A
			);

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="feedback-export-' . gmdate( 'Y-m-d' ) . '.csv"' );

			$output = fopen( 'php://output', 'w' );
			fputcsv( $output, array( 'Post ID', 'Post Title', 'Vote', 'Date (UTC)' ) );

			foreach ( $rows as $row ) {
				$title = get_the_title( (int) $row['post_id'] );
				$title = $title ? $title : __( '(Post deleted)', 'riaco-was-this-helpful' );
				// Neutralise CSV formula injection: prefix dangerous leading characters.
				if ( $title && in_array( $title[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
					$title = "'" . $title;
				}
				$vote = '1' === $row['helpful'] ? 'Yes' : 'No';
				fputcsv( $output, array( $row['post_id'], $title, $vote, $row['created_at'] ) );
			}

			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}
	}
}
