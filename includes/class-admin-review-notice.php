<?php
/**
 * Admin Review Notice Class
 *
 * Shows an admin notice asking users to leave a review after a certain amount of feedback has been collected.
 *
 * @package RIACO_Was_This_Helpful
 * @since 2.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RIWTH_Admin_Review_Notice
 */
class RIWTH_Admin_Review_Notice {

	/**
	 * Minimum feedback count to trigger the notice
	 *
	 * @var int
	 */
	private $feedback_threshold = 100;

	/**
	 * Options and Transients
	 *
	 * @var string
	 */
	private $option_done = 'riwth_review_notice_done';

	/**
	 * Transient to track "Maybe Later" action
	 *
	 * @var string
	 */
	private $transient_later = 'riwth_review_notice_maybe_later';

	/**
	 * Days to wait before showing the notice again after "Maybe Later"
	 *
	 * @var int
	 */
	private $maybe_later_days = 30;

	/**
	 * Review URL
	 *
	 * @var string
	 */
	private $review_url = 'https://wordpress.org/support/plugin/riaco-was-this-helpful/reviews/?filter=5#new-post';

	/**
	 * Plugin admin pages where the notice should be shown
	 *
	 * @var array
	 */
	private $plugin_pages = array( 'riwth-settings', 'riwth-shortcode' );

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_riwth_review_action', array( $this, 'handle_ajax_action' ) );
	}


	/**
	 * Maybe show the review notice
	 */
	public function maybe_show_notice() {
		if ( ! $this->is_plugin_page() ) {
			return;
		}

		// Already reviewed or dismissed.
		if ( get_option( $this->option_done, 0 ) ) {
			return;
		}

		// Temporary maybe later.
		if ( get_transient( $this->transient_later ) ) {
			return;
		}

		$overall_total_feedback = $this->get_overall_total_feedback();

		if ( $overall_total_feedback < $this->feedback_threshold ) {
			return;
		}

		$this->render_notice();
	}

	/**
	 * Render the review notice HTML
	 */
	private function render_notice() {
		?>
		<div class="notice notice-info riwth-review-notice ">
		
			<p>
			<?php
			$total_feedback = intval( $this->get_overall_total_feedback() );
			$plugin_name    = 'Was This Helpful';

			$notice_text = sprintf(
				/* translators: %1$s: plugin name, %2$d: total feedback count */
				__( 'Thank you for using %1$s plugin! So far <strong> we have collected %2$s feedbacks </strong>. If it’s been helpful, please consider leaving a review. It helps us improve and <strong>support the project</strong>!', 'riaco-was-this-helpful' ),
				'<strong>' . esc_html( $plugin_name ) . '</strong>',
				esc_html( $total_feedback )
			);

			echo wp_kses_post( $notice_text );
			?>
			</p>
			<p>
				<a href="<?php echo esc_url( $this->review_url ); ?>" target="_blank" class="button button-primary riwth-review-action" data-action="review"><?php echo esc_html__( 'Leave a Review', 'riaco-was-this-helpful' ); ?></a>
				<button class="button button-secondary riwth-review-action" data-action="later"><?php echo esc_html__( 'Maybe Later', 'riaco-was-this-helpful' ); ?></button>
				<button class="button button-link riwth-review-action" data-action="dismiss"><?php echo esc_html__( 'Never Show Again', 'riaco-was-this-helpful' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if current admin page is one of the plugin pages
	 *
	 * @return bool
	 */
	private function is_plugin_page() {
		global $hook_suffix;

		foreach ( $this->plugin_pages as $page ) {
			if ( strpos( $hook_suffix, $page ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the overall total feedback count from the database with caching
	 */
	private function get_overall_total_feedback() {
		global $wpdb;

		$table_name = esc_sql( $wpdb->prefix . RIWTH_DB_NAME );
		$cache_key  = 'riwth_overall_total_feedback';

		$overall_total_feedback = wp_cache_get( $cache_key, 'riwth_feedback' );

		if ( false === $overall_total_feedback ) {

			$overall_total_feedback = get_transient( $cache_key );

			if ( false === $overall_total_feedback ) {

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$overall_total_feedback = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i',
						$table_name
					)
				);

				wp_cache_set( $cache_key, $overall_total_feedback, 'riwth_feedback', 365 * DAY_IN_SECONDS );
				set_transient( $cache_key, $overall_total_feedback, 365 * DAY_IN_SECONDS );
			}
		}

		return $overall_total_feedback;
	}

	/**
	 * Enqueue admin scripts for handling the review notice actions
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! $this->is_plugin_page() ) {
			return;
		}

		wp_enqueue_script(
			'riwth-review-notice',
			RIWTH_PLUGIN_URL . 'assets/admin/js/riwth-review-notice.js',
			array( 'jquery' ),
			'1.0',
			true
		);

		wp_localize_script(
			'riwth-review-notice',
			'RIWTH_Review',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'riwth_review_nonce' ),
				'review_url' => $this->review_url,
			)
		);
	}

	/**
	 * Handle AJAX actions for the review notice
	 */
	public function handle_ajax_action() {
		check_ajax_referer( 'riwth_review_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$action = isset( $_POST['action_type'] ) ? sanitize_key( $_POST['action_type'] ) : '';

		switch ( $action ) {
			case 'review':
			case 'dismiss':
				update_option( $this->option_done, 1 ); // permanent, never show again.
				break;

			case 'later':
				set_transient( $this->transient_later, true, $this->maybe_later_days * DAY_IN_SECONDS );
				break;

			default:
				wp_send_json_error( array( 'message' => 'Invalid action' ) );
		}

		wp_send_json_success( array( 'action' => $action ) );
	}
}
