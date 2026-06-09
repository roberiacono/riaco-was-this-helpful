<?php
/**
 * AJAX class
 *
 * @package RIACO\Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Ajax' ) ) {
	/**
	 * AJAX class
	 */
	class RIWTH_Ajax {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp_ajax_riwth_save_feedback', array( $this, 'save_feedback' ) );
			add_action( 'wp_ajax_nopriv_riwth_save_feedback', array( $this, 'save_feedback' ) );
		}

		/**
		 * Save feedback
		 */
		public function save_feedback() {
			check_ajax_referer( 'riwth_was_this_helpful_nonce', 'nonce' );

			if ( ! isset( $_POST['post_id'], $_POST['helpful'] ) ) {
				wp_send_json_error( array( 'message' => 'Missing parameters.' ), 400 );
			}

			$post_id = intval( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
			$helpful = intval( sanitize_text_field( wp_unslash( $_POST['helpful'] ) ) ) ? 1 : 0;

			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				wp_send_json_error( array( 'message' => 'Invalid post.' ), 400 );
			}
			$allowed_types = get_option( 'riwth_display_on', array() );
			if ( ! in_array( $post->post_type, (array) $allowed_types, true ) ) {
				wp_send_json_error( array( 'message' => 'Feedback not enabled for this post.' ), 403 );
			}

			// Rate limiting: one vote per IP per post per 30 seconds.
			// Note: on sites behind a reverse proxy REMOTE_ADDR is the proxy IP;
			// checking HTTP_X_FORWARDED_FOR is more accurate but that header is
			// spoofable, so we keep this simple for the free tier.
			$ip_raw   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			$rate_key = 'riwth_rate_' . md5( $ip_raw . '|' . $post_id );

			if ( ! add_transient( $rate_key, 1, 30 ) ) {
				wp_send_json_error( array( 'message' => 'Too many requests. Please wait before voting again.' ), 429 );
			}

			global $wpdb;

			$table_name = $wpdb->prefix . RIWTH_DB_NAME;

			/**
			 * Fires just before a feedback entry is saved to the database.
			 *
			 * Use this hook for logging, rate-limiting, or other side-effects that
			 * should run before the record is written. To prevent the save entirely,
			 * use the 'riwth_should_display_box' filter instead.
			 *
			 * @param int $post_id The post receiving feedback.
			 * @param int $helpful 1 for positive feedback, 0 for negative.
			 */
			do_action( 'riwth_before_save_feedback', $post_id, $helpful );

			$feedback_data   = apply_filters(
				'riwth_insert_feedback_data',
				array(
					'post_id'    => $post_id,
					'helpful'    => $helpful,
					'created_at' => current_time( 'mysql', true ), // set true for UTC.
				),
				$post_id,
				$helpful
			);
			$feedback_format = apply_filters(
				'riwth_insert_feedback_format',
				array( '%d', '%d', '%s' ),
				$feedback_data
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using custom table, no core function available.
			$result = $wpdb->insert( $table_name, $feedback_data, $feedback_format );

			if ( false === $result ) {
				wp_send_json_error( array( 'message' => 'Could not save feedback.' ), 500 );
			}

			$feedback_id = $wpdb->insert_id;

			if ( $feedback_id ) {
				// delete cache.
				wp_cache_delete( 'riwth_total_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_total_feedback_' . $post_id );

				wp_cache_delete( 'riwth_positive_feedback_' . $post_id, 'riwth_feedback' );
				delete_transient( 'riwth_positive_feedback_' . $post_id );
			}

			/**
			 * Fires after a feedback entry has been successfully saved.
			 *
			 * Use this hook to send notifications, update external analytics,
			 * or integrate with a CRM.
			 *
			 * @param int $feedback_id The ID of the newly inserted feedback row.
			 * @param int $post_id     The post that received the feedback.
			 * @param int $helpful     1 for positive feedback, 0 for negative.
			 */
			do_action( 'riwth_feedback_saved', $feedback_id, $post_id, $helpful );

			$return = apply_filters(
				'riwth_ajax_feedback_sent_return',
				array(
					'trigger'    => 'showThankYou',
					'feedbackId' => absint( $feedback_id ),
					'content'    => '<div class="riwth-thank-you">' . esc_html( get_option( 'riwth_feedback_box_thanks_text', __( '✅ Thank you for your feedback!', 'riaco-was-this-helpful' ) ) ) . '</div>',
				)
			);

			wp_send_json( $return );
		}
	}
}
