<?php
/**
 * Class RIWTH_Reset_Stats
 *
 * Handles resetting feedback statistics for posts/pages.
 *
 * @package RIACO_Was_This_Helpful
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Reset_Stats' ) ) {
	/**
	 * Class RIWTH_Reset_Stats
	 */
	class RIWTH_Reset_Stats {

		/**
		 * Constructor for the RIWTH_Reset_Stats class.
		 */
		public function __construct() {

			// Handle AJAX request to reset feedback.
			add_action( 'wp_ajax_riwth_reset_stats', array( $this, 'reset_stats_callback' ) );

			add_action( 'riwth_after_metabox_stats', array( $this, 'riwth_add_reset_button_to_meta_box' ) );

			add_filter( 'post_row_actions', array( $this, 'riwth_add_reset_link' ), 10, 2 );
			add_filter( 'page_row_actions', array( $this, 'riwth_add_reset_link' ), 10, 2 );

			add_action( 'admin_action_riwth_reset_stats', array( $this, 'riwth_handle_reset_stats_action' ) );
			add_action( 'admin_notices', array( $this, 'show_reset_notice' ) );
		}

		/**
		 * Add the Reset link to post/page row actions.
		 *
		 * @since 2.1.0
		 * @param array   $actions Array of row actions.
		 * @param WP_Post $post The current post object.
		 * @return array Modified array of row actions.
		 */
		public function riwth_add_reset_link( $actions, $post ) {
			if ( current_user_can( 'manage_options' ) ) {

				// Build a safe URL with nonce
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'post'   => absint( $post->ID ),
							'action' => 'riwth_reset_stats',
						),
						admin_url( 'post.php' )
					),
					'riwth_reset_stats_' . absint( $post->ID )
				);

				// Add the reset link to the row actions
				$actions['riwth_reset'] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( $url ),
					esc_html__( 'Reset Helpful Stats', 'riaco-was-this-helpful' )
				);
			}

			return $actions;
		}


		/**
		 * Handle the reset stats action (like trash does).
		 */
		public function riwth_handle_reset_stats_action() {
			if ( ! isset( $_GET['post'], $_GET['_wpnonce'] ) ) {
				wp_die( esc_html__( 'Invalid request.', 'riaco-was-this-helpful' ) );
			}

			$post_id = absint( wp_unslash( $_GET['post'] ) );

			$wpnonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $wpnonce, 'riwth_reset_stats_' . $post_id ) ) {
				wp_die( esc_html__( 'You are not allowed to do this.', 'riaco-was-this-helpful' ) );
			}

			// Reset logic: update meta and clear caches.
			update_post_meta( $post_id, '_riwth_reset_date', current_time( 'mysql', true ) );
			delete_transient( 'riwth_total_feedback_' . $post_id );
			delete_transient( 'riwth_positive_feedback_' . $post_id );

			// Redirect back to the list table with message.
			$redirect = add_query_arg(
				array(
					'riwth_reset' => 1,
					'post_type'   => get_post_type( $post_id ),
				),
				admin_url( 'edit.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		/**
		 * Show admin notice after resetting stats.
		 */
		public function show_reset_notice() {
			if ( isset( $_GET['riwth_reset'], $_GET['_wpnonce'] ) && 1 === $_GET['riwth_reset'] ) {
				check_admin_referer( 'riwth_reset_stats_nonce' );
				echo '<div class="notice notice-success is-dismissible"><p>'
				. esc_html__( 'Helpful stats reset successfully.', 'riaco-was-this-helpful' )
				. '</p></div>';
			}
		}


		public function reset_stats_callback() {
			check_ajax_referer( 'riwth_reset_stats_nonce', 'nonce' );

			// Sanitize and validate post_id.
			$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

			if ( ! $post_id || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Invalid post ID or insufficient permissions.', 'riaco-was-this-helpful' ) );
			}

			// Validate that post exists.
			$post = get_post( $post_id );
			if ( ! $post ) {
				wp_send_json_error( esc_html__( 'Post not found.', 'riaco-was-this-helpful' ) );
			}

			// Perform soft reset logic (update/reset meta field for the post)
			$reset_date_updated = update_post_meta( $post_id, '_riwth_reset_date', current_time( 'mysql', true ) );

			if ( $reset_date_updated ) {
				delete_transient( 'riwth_total_feedback_' . $post_id );
				delete_transient( 'riwth_positive_feedback_' . $post_id );

				wp_send_json_success( esc_html__( 'Feedback statistics reset successfully.', 'riaco-was-this-helpful' ) );
			} else {
				wp_send_json_error( esc_html__( 'Failed to reset feedback statistics.', 'riaco-was-this-helpful' ) );
			}

			wp_die();
		}


		public function riwth_add_reset_button_to_meta_box( $post ) {
			wp_nonce_field( 'riwth_reset_nonce', 'riwth_reset_nonce' );

			$reset_date          = get_post_meta( $post->ID, '_riwth_reset_date', true );
			$is_reset_date_valid = $reset_date && strtotime( $reset_date ) !== false;

			if ( ! $is_reset_date_valid ) {
				return;
			}

			$reset_date_display = $reset_date ? $reset_date : __( 'Never', 'riaco-was-this-helpful' );
			?>
			<div class="riwth-reset-container" style="margin-top: 15px;">
		
				<p class="riwth-reset-description">
					<strong><?php esc_html_e( 'Last Reset Date:', 'riaco-was-this-helpful' ); ?></strong>
					<span class="riwth-reset-description--date">
						<?php echo esc_html( $reset_date_display ); ?>
					</span>
				</p>
		
				<!-- <p>
					<input type="button" 
							id="riwth-reset-button" 
							class="button" 
							value="<?php esc_attr_e( 'Reset Statistics', 'riaco-was-this-helpful' ); ?>">
				</p> -->
		
				<!-- Message area for JS -->
				<div class="riwth-reset-message" style="display:none; margin-top:5px;"></div>
			</div>
			<?php
		}
	}

}
