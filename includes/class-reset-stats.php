<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Reset_Stats' ) ) {
	class RIWTH_Reset_Stats {
		public function __construct() {

			// Handle AJAX request to reset feedback
			add_action( 'wp_ajax_riwth_reset_stats', array( $this, 'reset_stats_callback' ) );

			add_action( 'riwth_after_metabox_stats', array( $this, 'riwth_add_reset_button_to_meta_box' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
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

			$reset_date         = get_post_meta( $post->ID, '_riwth_reset_date', true );
			$reset_date_display = $reset_date ? $reset_date : __( 'Never', 'riaco-was-this-helpful' );
			?>
			<div class="riwth-reset-container" style="margin-top: 15px;">
				<div style="text-transform: uppercase;">
					<?php echo esc_html__( 'Reset Statistics', 'riaco-was-this-helpful' ); ?>
				</div>
		
				<p class="riwth-reset-description">
					<strong><?php esc_html_e( 'Last Reset Date:', 'riaco-was-this-helpful' ); ?></strong>
					<span class="riwth-reset-description--date">
						<?php echo esc_html( $reset_date_display ); ?>
					</span>
				</p>
		
				<p>
					<input type="button" 
							id="riwth-reset-button" 
							class="button" 
							value="<?php esc_attr_e( 'Reset Statistics', 'riaco-was-this-helpful' ); ?>">
				</p>
		
				<!-- Message area for JS -->
				<div class="riwth-reset-message" style="display:none; margin-top:5px;"></div>
			</div>
			<?php
		}

		public function enqueue_admin_scripts( $hook ) {
			// Load script only on post editor screens
			if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_enqueue_script(
					'riwth-admin',
					RIWTH_PLUGIN_URL . 'assets/admin/js/riwth-admin.js',
					array( 'jquery' ),
					'1.0',
					true
				);

				wp_localize_script(
					'riwth-admin',
					'riwthReset',
					array(
						'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
						'nonce'    => wp_create_nonce( 'riwth_reset_stats_nonce' ),
						'confirm'  => esc_html__( 'Are you sure you want to reset the feedback statistics for this post?', 'riaco-was-this-helpful' ),
						'success'  => esc_html__( 'Feedback statistics reset successfully.', 'riaco-was-this-helpful' ),
					)
				);
			}
		}
	}

}
