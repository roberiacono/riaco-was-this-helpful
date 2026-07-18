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

		/**
		 * Bulk-action value for the checkbox-based delete action. Namespaced
		 * (not the generic 'delete') so a submit never collides with WP
		 * core's own generic admin_action_delete semantics used elsewhere.
		 */
		const BULK_DELETE_ACTION = 'riwth_bulk_delete';

		/**
		 * WP_List_Table 'plural' arg. Also the string WP core's
		 * display_tablenav() uses to build the bulk-action nonce field name
		 * ('bulk-' . plural), so this must match what RIWTH_Feedback_List_Table
		 * passes to its own parent constructor.
		 */
		const LIST_TABLE_PLURAL = 'feedback_records';

		/**
		 * Screen Options option name for the per-page control.
		 */
		const PER_PAGE_OPTION = 'riwth_feedback_per_page';

		/**
		 * @var RIWTH_Feedback_List_Table|null Constructed on load-{hook}, see setup_list_table().
		 */
		private $list_table;

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_init', array( $this, 'maybe_export_csv' ) );
			add_action( 'admin_init', array( $this, 'handle_bulk_delete' ) );
			add_action( 'admin_action_riwth_delete_feedback',     array( $this, 'handle_delete_single' ) );
			add_action( 'admin_action_riwth_delete_all_feedback', array( $this, 'handle_delete_all' ) );
			add_action( 'admin_notices', array( $this, 'show_action_notice' ) );
			add_filter( 'set_screen_option_' . self::PER_PAGE_OPTION, array( $this, 'save_screen_option' ), 10, 3 );
		}

		public function add_submenu_page() {
			$hook = add_submenu_page(
				RIWTH_MENU_SLUG,
				__( 'Feedback Records', 'riaco-was-this-helpful' ),
				__( 'Feedback Records', 'riaco-was-this-helpful' ),
				'manage_options',
				'riwth-feedback-list',
				array( $this, 'render_page' )
			);

			if ( $hook ) {
				add_action( "load-{$hook}", array( $this, 'setup_list_table' ) );
			}
		}

		/**
		 * Register the "Feedback records per page" Screen Options control and
		 * construct RIWTH_Feedback_List_Table.
		 *
		 * Must happen on load-{hook} — before wp-admin/admin-header.php runs
		 * — not later inside render_page(). WP core's own
		 * WP_Screen::render_screen_options() calls get_column_headers( $this )
		 * during admin-header.php, and that function has a static per-screen-id
		 * cache: whichever result it gets first for this screen ID is cached
		 * for the rest of the request. RIWTH_Feedback_List_Table's constructor
		 * is what registers the manage_{screen_id}_columns filter that supplies
		 * get_columns() to that cache — construct it too late (e.g. inside
		 * render_page()'s template) and the cache has already locked in an
		 * empty column set, silently rendering a columnless, checkbox-less
		 * table with empty rows.
		 */
		public function setup_list_table() {
			add_screen_option(
				'per_page',
				array(
					'label'   => __( 'Feedback records per page', 'riaco-was-this-helpful' ),
					'default' => 20,
					'option'  => self::PER_PAGE_OPTION,
				)
			);

			require_once RIWTH_PLUGIN_DIR . 'includes/class-feedback-list-table.php';
			$this->list_table = new RIWTH_Feedback_List_Table( $this );
		}

		/**
		 * @return RIWTH_Feedback_List_Table
		 */
		public function get_list_table() {
			return $this->list_table;
		}

		/**
		 * Persist the "Feedback records per page" Screen Options value.
		 *
		 * @param mixed  $status Default filter value (false unless another filter already handled it).
		 * @param string $option Option name being saved.
		 * @param mixed  $value  Submitted value.
		 * @return mixed
		 */
		public function save_screen_option( $status, $option, $value ) {
			return ( self::PER_PAGE_OPTION === $option ) ? (int) $value : $status;
		}

		public function maybe_export_csv() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- values are sanitized via sanitize_text_field( wp_unslash() ) below; used only for page routing, not a form submission.
			if ( ! isset( $_GET['page'], $_GET['export'] ) ) {
				return;
			}
			$page   = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			$export = sanitize_text_field( wp_unslash( $_GET['export'] ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			if ( 'riwth-feedback-list' !== $page || 'csv' !== $export ) {
				return;
			}
			$this->export_csv();
		}

		/**
		 * Handle the checkbox bulk-delete submission from the Feedback
		 * Records table.
		 *
		 * Must run on admin_init rather than an admin_action_* hook: a bulk
		 * form necessarily posts back to page=riwth-feedback-list, so
		 * render_page() would already have streamed HTML by the time
		 * admin_action_* dispatches at the very end of wp-admin/admin.php —
		 * a redirect at that point would hit "headers already sent". The
		 * existing single/delete-all links avoid this only because their
		 * URLs omit `page=`.
		 */
		public function handle_bulk_delete() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- routing check only; the actual nonce is verified in process_bulk_delete_request().
			if ( ! isset( $_GET['page'] ) || 'riwth-feedback-list' !== $_GET['page'] ) {
				return;
			}

			$requested = '';
			if ( ! empty( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) {
				$requested = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			} elseif ( ! empty( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) {
				$requested = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( self::BULK_DELETE_ACTION !== $requested ) {
				return;
			}

			$this->process_bulk_delete_request();
		}

		/**
		 * Verify capability + nonce, delete the checked rows, and redirect
		 * with a result notice.
		 */
		public function process_bulk_delete_request() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Not allowed.', 'riaco-was-this-helpful' ) );
			}
			check_admin_referer( 'bulk-' . self::LIST_TABLE_PLURAL );

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above via check_admin_referer().
			$ids = isset( $_REQUEST['feedback'] ) ? array_map( 'absint', (array) wp_unslash( $_REQUEST['feedback'] ) ) : array();
			$ids = array_values( array_filter( $ids ) );

			$deleted = $ids ? $this->delete_records( $ids ) : 0;

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified above via check_admin_referer().
			$redirect_args = array(
				'page'         => 'riwth-feedback-list',
				'riwth_notice' => $deleted ? 'bulk_deleted' : 'bulk_delete_none',
				'paged'        => isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0,
			);
			foreach ( array( 'orderby', 'order', 'm', 's' ) as $key ) {
				if ( ! empty( $_REQUEST[ $key ] ) ) {
					$redirect_args[ $key ] = sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
				}
			}

			wp_safe_redirect( add_query_arg( array_filter( $redirect_args ), admin_url( 'admin.php' ) ) );
			exit;
		}

		/**
		 * Delete a set of feedback rows by ID and bust their caches.
		 *
		 * @param int[] $ids Feedback row IDs.
		 * @return int Number of rows deleted.
		 */
		public function delete_records( array $ids ) {
			global $wpdb;

			$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
			if ( ! $ids ) {
				return 0;
			}

			$table        = $wpdb->prefix . RIWTH_DB_NAME;
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM %i WHERE id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge( array( $table ), $ids )
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM %i WHERE id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge( array( $table ), $ids )
				)
			);

			if ( $deleted ) {
				$this->bust_feedback_caches( array_map( 'intval', $post_ids ) );
			}

			return (int) $deleted;
		}

		/**
		 * Bust feedback-count caches for a set of posts plus the site-wide
		 * total. Shared by single delete, delete-all, and bulk delete.
		 *
		 * @param int[] $post_ids Post IDs whose caches need busting.
		 */
		private function bust_feedback_caches( array $post_ids ) {
			foreach ( array_unique( array_filter( $post_ids ) ) as $post_id ) {
				wp_cache_delete( 'riwth_total_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_total_feedback_' . $post_id );
				wp_cache_delete( 'riwth_positive_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_positive_feedback_' . $post_id );
			}
			wp_cache_delete( 'riwth_overall_total_feedback', 'riwth_feedback' );
			delete_transient( 'riwth_overall_total_feedback' );
		}

		public function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You are not allowed to view this page.', 'riaco-was-this-helpful' ) );
			}

			require_once RIWTH_PLUGIN_DIR . 'templates/page-feedback-list.php';
		}

		/**
		 * Return a page of feedback rows.
		 *
		 * @param int   $page     1-based page number.
		 * @param int   $per_page Rows per page.
		 * @param array $args {
		 *     Optional. Query refinements. All default to today's unfiltered,
		 *     newest-first behavior.
		 *
		 *     @type string $orderby One of 'created_at' (default), 'helpful', 'post_title'.
		 *     @type string $order   'ASC' or 'DESC' (default).
		 *     @type string $m       Month filter, YYYYMM. Default '' (no filter).
		 *     @type string $s       Post title search term. Default '' (no filter).
		 * }
		 * @return array
		 */
		public function get_feedback_records( $page = 1, $per_page = 20, $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'orderby' => 'created_at',
					'order'   => 'DESC',
					'm'       => '',
					's'       => '',
				)
			);

			global $wpdb;
			$table  = $wpdb->prefix . RIWTH_DB_NAME;
			$offset = ( absint( $page ) - 1 ) * absint( $per_page );

			// Hardcoded whitelist: the actual SQL column expression never comes
			// from $_GET/$args directly, only the choice of which entry to use.
			$orderby_map = array(
				'created_at' => 'f.created_at',
				'helpful'    => 'f.helpful',
				'post_title' => 'p.post_title',
			);
			$orderby_key = isset( $orderby_map[ $args['orderby'] ] ) ? $args['orderby'] : 'created_at';
			$order       = ( 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';
			$needs_join  = ( 'post_title' === $orderby_key ) || ( '' !== $args['s'] );

			list( $where_sql, $where_params ) = $this->build_feedback_where( $args['m'], $args['s'] );

			$sql    = 'SELECT f.id, f.post_id, f.helpful, f.created_at FROM %i f';
			$params = array( $table );

			if ( $needs_join ) {
				$sql     .= ' LEFT JOIN %i p ON p.ID = f.post_id';
				$params[] = $wpdb->posts;
			}
			if ( $where_sql ) {
				$sql    .= ' WHERE ' . $where_sql;
				$params  = array_merge( $params, $where_params );
			}
			$sql     .= ' ORDER BY ' . $orderby_map[ $orderby_key ] . ' ' . $order . ' LIMIT %d OFFSET %d';
			$params[] = $per_page;
			$params[] = $offset;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		/**
		 * Return the total number of feedback rows matching the given filters.
		 *
		 * @param array $args {
		 *     Optional. Query refinements. Default '' (no filter) for both.
		 *
		 *     @type string $m Month filter, YYYYMM.
		 *     @type string $s Post title search term.
		 * }
		 * @return int
		 */
		public function get_total_records( $args = array() ) {
			$args = wp_parse_args(
				$args,
				array(
					'm' => '',
					's' => '',
				)
			);

			global $wpdb;
			$table      = $wpdb->prefix . RIWTH_DB_NAME;
			$needs_join = '' !== $args['s'];

			list( $where_sql, $where_params ) = $this->build_feedback_where( $args['m'], $args['s'] );

			$sql    = 'SELECT COUNT(*) FROM %i f';
			$params = array( $table );

			if ( $needs_join ) {
				$sql     .= ' LEFT JOIN %i p ON p.ID = f.post_id';
				$params[] = $wpdb->posts;
			}
			if ( $where_sql ) {
				$sql   .= ' WHERE ' . $where_sql;
				$params = array_merge( $params, $where_params );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		/**
		 * Build a WHERE clause (and matching prepare() params) for the month
		 * and search filters, shared by get_feedback_records() and
		 * get_total_records().
		 *
		 * The month filter buckets by the raw UTC created_at column rather
		 * than converting to site-local time first — a deliberate, documented
		 * simplification; display already has an equivalent asymmetry
		 * (created_at is stored UTC but rendered via get_date_from_gmt()).
		 *
		 * @param string $m Month filter, YYYYMM, or '' for none.
		 * @param string $s Post title search term, or '' for none.
		 * @return array Two-element array: [0] WHERE SQL (no leading "WHERE", '' if unfiltered), [1] prepare() params.
		 */
		private function build_feedback_where( $m, $s ) {
			global $wpdb;

			$where  = array();
			$params = array();

			if ( $m && preg_match( '/^\d{6}$/', $m ) ) {
				$year  = (int) substr( $m, 0, 4 );
				$month = (int) substr( $m, 4, 2 );

				if ( $month >= 1 && $month <= 12 ) {
					$start = sprintf( '%04d-%02d-01 00:00:00', $year, $month );
					$next  = ( 12 === $month )
						? sprintf( '%04d-01-01 00:00:00', $year + 1 )
						: sprintf( '%04d-%02d-01 00:00:00', $year, $month + 1 );

					$where[]  = 'f.created_at >= %s AND f.created_at < %s';
					$params[] = $start;
					$params[] = $next;
				}
			}

			if ( $s ) {
				$where[]  = 'p.post_title LIKE %s';
				$params[] = '%' . $wpdb->esc_like( $s ) . '%';
			}

			return array( $where ? implode( ' AND ', $where ) : '', $params );
		}

		/**
		 * Return distinct year/month buckets present in the feedback table,
		 * newest first — feeds the Feedback Records month/year filter dropdown.
		 *
		 * @return array
		 */
		public function get_feedback_months() {
			global $wpdb;
			$table = $wpdb->prefix . RIWTH_DB_NAME;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT DISTINCT YEAR(created_at) AS year, MONTH(created_at) AS month FROM %i ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC',
					$table
				)
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

			$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;

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

			if ( false === $deleted || 0 === $deleted ) {
				wp_safe_redirect(
					add_query_arg(
						array_filter(
							array(
								'page'         => 'riwth-feedback-list',
								'riwth_notice' => 'delete_failed',
								'paged'        => $paged,
							)
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			$this->bust_feedback_caches( $post_id ? array( $post_id ) : array() );

			wp_safe_redirect(
				add_query_arg(
					array_filter(
						array(
							'page'         => 'riwth-feedback-list',
							'riwth_notice' => 'deleted',
							'paged'        => $paged,
						)
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

			$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;

			global $wpdb;
			$table = $wpdb->prefix . RIWTH_DB_NAME;

			// Fetch every distinct post_id before deleting so we can bust their per-post caches.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_ids = $wpdb->get_col(
				$wpdb->prepare( 'SELECT DISTINCT post_id FROM %i', $table )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare( 'DELETE FROM %i', $table )
			);

			if ( false === $result ) {
				wp_safe_redirect(
					add_query_arg(
						array_filter(
							array(
								'page'         => 'riwth-feedback-list',
								'riwth_notice' => 'delete_failed',
								'paged'        => $paged,
							)
						),
						admin_url( 'admin.php' )
					)
				);
				exit;
			}

			$this->bust_feedback_caches( array_map( 'intval', $post_ids ) );
			wp_cache_flush();

			wp_safe_redirect(
				add_query_arg(
					array_filter(
						array(
							'page'         => 'riwth-feedback-list',
							'riwth_notice' => 'deleted_all',
							'paged'        => $paged,
						)
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
			} elseif ( 'bulk_deleted' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Selected feedback records deleted.', 'riaco-was-this-helpful' ) . '</p></div>';
			} elseif ( 'bulk_delete_none' === $notice ) {
				echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'No feedback records were selected.', 'riaco-was-this-helpful' ) . '</p></div>';
			}
		}

		/**
		 * Stream all feedback as a CSV file download. Always exports the full,
		 * unfiltered table (ignores any on-screen search/date filter/sort) —
		 * the same deliberate "always complete" contract as Delete All.
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
					'SELECT id, post_id, helpful, created_at FROM %i ORDER BY created_at DESC',
					$wpdb->prefix . RIWTH_DB_NAME
				),
				ARRAY_A
			);

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="feedback-export-' . gmdate( 'Y-m-d' ) . '.csv"' );

			$output = fopen( 'php://output', 'w' );

			/**
			 * Filter extra CSV export column headers, inserted after 'Vote' and before 'Date (UTC)'.
			 *
			 * @param array $extra_columns Column header labels, in order.
			 */
			$base_columns  = array( 'Post ID', 'Post Title', 'Post URL', 'Post Slug', 'Vote' );
			$extra_columns = apply_filters( 'riwth_csv_export_columns', array() );
			$columns       = array_merge( $base_columns, $extra_columns, array( 'Date (UTC)' ) );
			fputcsv( $output, $columns );

			foreach ( $rows as $row ) {
				$title = get_the_title( (int) $row['post_id'] );
				$title = $title ? $title : __( '(Post deleted)', 'riaco-was-this-helpful' );
				// Neutralise CSV formula injection: prefix dangerous leading characters.
				if ( $title && in_array( $title[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
					$title = "'" . $title;
				}
				$url  = get_permalink( (int) $row['post_id'] );
				$url  = $url ? $url : '';
				$slug = get_post_field( 'post_name', (int) $row['post_id'] );
				$slug = $slug ? $slug : '';
				$vote = '1' === $row['helpful'] ? 'Yes' : 'No';

				/**
				 * Filter extra CSV export column values for a row, in the same order as 'riwth_csv_export_columns'.
				 *
				 * @param array $extra_columns Extra column values, in order.
				 * @param array $row           The raw feedback row (includes 'id' for looking up related data).
				 */
				$base_row  = array( $row['post_id'], $title, $url, $slug, $vote );
				$extra_row = apply_filters( 'riwth_csv_export_row', array(), $row );
				$csv_row   = array_merge( $base_row, $extra_row, array( $row['created_at'] ) );
				fputcsv( $output, $csv_row );
			}

			fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			exit;
		}
	}
}
