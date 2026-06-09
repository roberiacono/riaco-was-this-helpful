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

				$options = get_option( 'riwth_display_on', array() );
				$options = is_array( $options ) ? $options : array();

				if ( ! in_array( get_post_type(), $options ) ) {
					return $actions;
				}

				// Build a safe URL with nonce
				$url = wp_nonce_url(
					add_query_arg(
						array(
							'post'   => absint( $post->ID ),
							'action' => 'riwth_reset_stats',
						),
						admin_url( 'admin.php' )
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

			do_action( 'riwth_before_reset_stats', $post_id );

			// Reset logic: update meta and clear caches.
			update_post_meta( $post_id, '_riwth_reset_date', current_time( 'mysql', true ) );

			wp_cache_delete( 'riwth_total_feedback_' . $post_id, 'riwth_feedback' );
			delete_transient( 'riwth_total_feedback_' . $post_id );

			wp_cache_delete( 'riwth_positive_feedback_' . $post_id, 'riwth_feedback' );
			delete_transient( 'riwth_positive_feedback_' . $post_id );

			do_action( 'riwth_after_reset_stats', $post_id );

			// Store a one-time notice in a transient instead of passing the nonce in the URL.
			set_transient( 'riwth_reset_notice_' . get_current_user_id(), $post_id, 60 );

			wp_safe_redirect( add_query_arg(
				array( 'post_type' => get_post_type( $post_id ) ),
				admin_url( 'edit.php' )
			) );
			exit;
		}

		/**
		 * Show admin notice after resetting stats.
		 */
		public function show_reset_notice() {
			$transient_key = 'riwth_reset_notice_' . get_current_user_id();
			$post_id       = (int) get_transient( $transient_key );
			if ( $post_id ) {
				delete_transient( $transient_key );
				echo '<div class="notice notice-success is-dismissible"><p>'
					. esc_html__( 'Helpful stats reset successfully.', 'riaco-was-this-helpful' )
					. '</p></div>';
			}
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
