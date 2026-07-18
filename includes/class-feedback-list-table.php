<?php
/**
 * WP_List_Table subclass for the Feedback Records admin page.
 *
 * Loaded on demand from RIWTH_Admin_Feedback_List::render_page() only —
 * never required unconditionally from RIWTH_Was_This_Helpful::includes(),
 * because WP_List_Table doesn't exist outside wp-admin and this file would
 * fatal on every frontend page load if it were.
 *
 * @package RIACO\Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'RIWTH_Feedback_List_Table' ) ) {
	/**
	 * Renders the Feedback Records table with sortable columns, a month/year
	 * date filter, title search, checkbox bulk delete, and screen-options
	 * per-page control. Delegates all data access back to the
	 * RIWTH_Admin_Feedback_List instance it's constructed with.
	 */
	class RIWTH_Feedback_List_Table extends WP_List_Table {

		/**
		 * @var RIWTH_Admin_Feedback_List
		 */
		private $list;

		/**
		 * Extra per-row cell HTML from the riwth_feedback_list_row_cells
		 * filter, memoized once per row by single_row() so column_default()
		 * doesn't re-run the filter once per extra column.
		 *
		 * @var array|null
		 */
		private $current_row_cells = null;

		/**
		 * @param RIWTH_Admin_Feedback_List $list Data source.
		 */
		public function __construct( RIWTH_Admin_Feedback_List $list ) {
			$this->list = $list;

			parent::__construct(
				array(
					'singular' => 'feedback',
					'plural'   => RIWTH_Admin_Feedback_List::LIST_TABLE_PLURAL,
					'ajax'     => false,
				)
			);
		}

		public function no_items() {
			esc_html_e( 'No feedback records found.', 'riaco-was-this-helpful' );
		}

		/**
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'cb'   => '<input type="checkbox" />',
				'post' => __( 'Post', 'riaco-was-this-helpful' ),
				'slug' => __( 'Slug', 'riaco-was-this-helpful' ),
				'vote' => __( 'Vote', 'riaco-was-this-helpful' ),
			);

			/** This filter is documented in templates/page-feedback-list.php's former location; see CLAUDE.md. */
			foreach ( apply_filters( 'riwth_feedback_list_columns', array() ) as $index => $label ) {
				$columns[ 'riwth_extra_' . $index ] = $label;
			}

			$columns['date'] = __( 'Date', 'riaco-was-this-helpful' );

			return $columns;
		}

		/**
		 * @return array
		 */
		protected function get_sortable_columns() {
			return array(
				'post' => array( 'post_title', false ),
				'vote' => array( 'helpful', false ),
				'date' => array( 'created_at', true, '', true, 'desc' ),
			);
		}

		/**
		 * @return array
		 */
		protected function get_bulk_actions() {
			return array(
				RIWTH_Admin_Feedback_List::BULK_DELETE_ACTION => __( 'Delete', 'riaco-was-this-helpful' ),
			);
		}

		/**
		 * @param object $item Row.
		 * @return string
		 */
		protected function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="feedback[]" value="%d" />', (int) $item->id );
		}

		/**
		 * @param object $item Row.
		 * @return string
		 */
		protected function column_post( $item ) {
			$post_id = (int) $item->post_id;
			$title   = get_the_title( $post_id );
			$title   = $title ? $title : __( '(Post deleted)', 'riaco-was-this-helpful' );
			$edit    = get_edit_post_link( $post_id );

			$out = $edit
				? sprintf( '<a href="%s">%s</a>', esc_url( $edit ), esc_html( $title ) )
				: esc_html( $title );

			$delete_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'      => 'riwth_delete_feedback',
						'feedback_id' => $item->id,
					),
					admin_url( 'admin.php' )
				),
				'riwth_delete_feedback_' . $item->id
			);

			$actions = array(
				'delete' => sprintf(
					'<a href="%s" onclick="return confirm(\'%s\');" style="color:#c0392b;">%s</a>',
					esc_url( $delete_url ),
					esc_js( __( 'Delete this record?', 'riaco-was-this-helpful' ) ),
					esc_html__( 'Delete', 'riaco-was-this-helpful' )
				),
			);

			return $out . $this->row_actions( $actions );
		}

		/**
		 * @param object $item Row.
		 * @return string
		 */
		protected function column_slug( $item ) {
			$post_id  = (int) $item->post_id;
			$post_url = get_permalink( $post_id );

			if ( ! $post_url ) {
				return '&#8212;';
			}

			return sprintf(
				'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
				esc_url( $post_url ),
				esc_html( get_post_field( 'post_name', $post_id ) )
			);
		}

		/**
		 * @param object $item Row.
		 * @return string
		 */
		protected function column_vote( $item ) {
			$is_positive = '1' === (string) $item->helpful;
			$label       = $is_positive ? __( 'Yes', 'riaco-was-this-helpful' ) : __( 'No', 'riaco-was-this-helpful' );
			$color       = $is_positive ? '#1e8a1e' : '#c0392b';

			return sprintf(
				'<span style="background-color:%1$s; margin-right:5px; border-radius:50%%; width:0.5rem; height:0.5rem; display:inline-block; vertical-align:middle;"></span><strong style="color:%1$s">%2$s</strong>',
				esc_attr( $color ),
				esc_html( $label )
			);
		}

		/**
		 * @param object $item Row.
		 * @return string
		 */
		protected function column_date( $item ) {
			return esc_html( get_date_from_gmt( $item->created_at, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) );
		}

		/**
		 * Memoize the riwth_feedback_list_row_cells filter once per row so
		 * column_default() doesn't re-run it once per extra column.
		 *
		 * @param object $item Row.
		 */
		public function single_row( $item ) {
			/** This filter is documented in templates/page-feedback-list.php's former location; see CLAUDE.md. */
			$this->current_row_cells = apply_filters( 'riwth_feedback_list_row_cells', array(), $item );
			parent::single_row( $item );
			$this->current_row_cells = null;
		}

		/**
		 * @param object $item        Row.
		 * @param string $column_name Column key.
		 * @return string
		 */
		protected function column_default( $item, $column_name ) {
			if ( 0 !== strpos( $column_name, 'riwth_extra_' ) ) {
				return '';
			}

			$index = (int) substr( $column_name, strlen( 'riwth_extra_' ) );
			$cells = null !== $this->current_row_cells
				? $this->current_row_cells
				: apply_filters( 'riwth_feedback_list_row_cells', array(), $item );

			return isset( $cells[ $index ] ) ? wp_kses_post( $cells[ $index ] ) : '';
		}

		public function prepare_items() {
			$this->process_bulk_action();

			$per_page     = $this->get_items_per_page( RIWTH_Admin_Feedback_List::PER_PAGE_OPTION, 20 );
			$current_page = $this->get_pagenum();

			$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! in_array( $orderby, array( 'created_at', 'helpful', 'post_title' ), true ) ) {
				$orderby = 'created_at';
			}
			$order = ( isset( $_GET['order'] ) && 'asc' === strtolower( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$args = array(
				'orderby' => $orderby,
				'order'   => $order,
				'm'       => isset( $_GET['m'] ) ? sanitize_text_field( wp_unslash( $_GET['m'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				's'       => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);

			$total_items = $this->list->get_total_records( $args );
			$this->items = $this->list->get_feedback_records( $current_page, $per_page, $args );

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
				)
			);
		}

		/**
		 * @return string
		 */
		protected function get_primary_column_name() {
			return 'post';
		}

		/**
		 * In normal operation this is never actually reached with a pending
		 * bulk-delete request: RIWTH_Admin_Feedback_List::handle_bulk_delete()
		 * already intercepts and exits on admin_init, before render_page()
		 * (and therefore prepare_items()) ever runs. This guard exists so
		 * prepare_items() stays safe to call directly (e.g. in tests) without
		 * accidentally re-running the delete on every unrelated request.
		 */
		protected function process_bulk_action() {
			$action = '';
			if ( ! empty( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- compared only against a hardcoded constant below; the real nonce check happens inside process_bulk_delete_request().
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- compared only against a hardcoded constant below; the real nonce check happens inside process_bulk_delete_request().
			} elseif ( ! empty( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- compared only against a hardcoded constant below; the real nonce check happens inside process_bulk_delete_request().
				$action = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- compared only against a hardcoded constant below; the real nonce check happens inside process_bulk_delete_request().
			}

			if ( RIWTH_Admin_Feedback_List::BULK_DELETE_ACTION !== $action ) {
				return;
			}

			$this->list->process_bulk_delete_request();
		}

		/**
		 * Month/year filter dropdown, matching WP core's own "All dates"
		 * edit.php filter naming (name="m", submit name "filter_action") for
		 * visual and behavioral parity.
		 *
		 * @param string $which 'top'|'bottom'.
		 */
		protected function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			global $wp_locale;

			$months = $this->list->get_feedback_months();
			if ( empty( $months ) ) {
				return;
			}

			$selected = isset( $_GET['m'] ) ? sanitize_text_field( wp_unslash( $_GET['m'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			echo '<div class="alignleft actions">';
			echo '<label for="filter-by-date" class="screen-reader-text">' . esc_html__( 'Filter by date', 'riaco-was-this-helpful' ) . '</label>';
			echo '<select name="m" id="filter-by-date">';
			printf( '<option value="0"%s>%s</option>', selected( $selected, '0', false ), esc_html__( 'All dates', 'riaco-was-this-helpful' ) );
			foreach ( $months as $row ) {
				$value = sprintf( '%04d%02d', (int) $row->year, (int) $row->month );
				printf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $value ),
					selected( $selected, $value, false ),
					/* translators: 1: month name, 2: 4-digit year */
					esc_html( sprintf( __( '%1$s %2$d', 'riaco-was-this-helpful' ), $wp_locale->get_month( (int) $row->month ), (int) $row->year ) )
				);
			}
			echo '</select>';
			submit_button( __( 'Filter', 'riaco-was-this-helpful' ), '', 'filter_action', false );
			echo '</div>';
		}
	}
}
