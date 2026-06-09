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

			global $wpdb;

			if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['helpful'] ) ) {
				return;
			}

			$post_id = intval( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
			$helpful = intval( sanitize_text_field( wp_unslash( $_POST['helpful'] ) ) ) ? 1 : 0;

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

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Using custom table, no core function available.
			$result = $wpdb->insert(
				$table_name,
				array(
					'post_id'    => $post_id,
					'helpful'    => $helpful,
					'created_at' => current_time( 'mysql', true ), // set true for UTC.
				),
				array(
					'%d', // post_id format.
					'%d', // helpful format.
					'%s',  // created_at format.
				)
			);

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
					'feedbackId' => esc_attr( $feedback_id ),
					'content'    => '<div class="riwth-thank-you">' . esc_html( get_option( 'riwth_feedback_box_thanks_text' ) ) . '</div>',
				)
			);

			wp_send_json( $return );
		}
	}
}
