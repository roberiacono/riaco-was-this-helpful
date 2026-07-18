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
				wp_send_json_error( array( 'message' => __( 'Missing parameters.', 'riaco-was-this-helpful' ) ), 400 );
			}

			$post_id = intval( sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) );
			$helpful = intval( sanitize_text_field( wp_unslash( $_POST['helpful'] ) ) ) ? 1 : 0;

			$post = get_post( $post_id );
			if ( ! $post || 'publish' !== $post->post_status ) {
				wp_send_json_error( array( 'message' => __( 'Invalid post.', 'riaco-was-this-helpful' ) ), 400 );
			}
			$allowed_types = get_option( 'riwth_display_on', array() );
			if ( ! in_array( $post->post_type, (array) $allowed_types, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Feedback not enabled for this post.', 'riaco-was-this-helpful' ) ), 403 );
			}

			// Rate limiting: one vote per IP per post per 30 seconds.
			// Note: on sites behind a reverse proxy REMOTE_ADDR is the proxy IP;
			// checking HTTP_X_FORWARDED_FOR is more accurate but that header is
			// spoofable, so we keep this simple for the free tier.
			// The IP itself is never stored: get_client_ip_hash() salts it with
			// wp_salt() before it's used as an option-name key, so it can't be
			// reversed even by someone with DB read access.
			$rate_key = '_riwth_rl_' . RIWTH_Functions::get_client_ip_hash( $post_id );
			$now      = time();

			global $wpdb;

			// Claim the rate-limit slot with INSERT IGNORE: the wp_options unique key
			// on option_name makes this atomic across concurrent requests, unlike a
			// get_transient()/set_transient() pair which leaves a check-then-write race.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
			$claimed = $wpdb->query(
				$wpdb->prepare(
					'INSERT IGNORE INTO %i (option_name, option_value, autoload) VALUES (%s, %d, %s)',
					$wpdb->options,
					$rate_key,
					$now + 30,
					'no'
				)
			);

			if ( ! $claimed ) {
				// INSERT IGNORE returns a falsy result both when the row already exists
				// (expected — someone already holds this slot) and on a genuine SQL error.
				// Only treat it as "slot exists" when there's no actual DB error, so a real
				// failure here surfaces instead of silently falling through to allow the vote.
				if ( '' !== $wpdb->last_error ) {
					wp_send_json_error( array( 'message' => __( 'Could not process request.', 'riaco-was-this-helpful' ) ), 500 );
				}

				// Slot already exists — atomically reclaim it only if it has expired.
				// The WHERE condition makes the reclaim itself race-free: if two requests
				// hit this at the same moment, only one UPDATE can match the still-expired
				// row (the other affects 0 rows and is correctly rate-limited below), unlike
				// a separate SELECT-then-UPDATE which both requests could pass concurrently.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$reclaimed = $wpdb->query(
					$wpdb->prepare(
						'UPDATE %i SET option_value = %d WHERE option_name = %s AND option_value <= %d',
						$wpdb->options,
						$now + 30,
						$rate_key,
						$now
					)
				);

				if ( ! $reclaimed ) {
					wp_send_json_error( array( 'message' => __( 'Too many requests. Please wait before voting again.', 'riaco-was-this-helpful' ) ), 429 );
				}
			}

			// Lazily sweep expired rate-limit rows so they don't accumulate in wp_options
			// forever (there's no cron job for this — a small per-request chance keeps it
			// bounded without adding scheduling overhead to every vote).
			if ( 1 === wp_rand( 1, 200 ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM %i WHERE option_name LIKE %s AND option_value < %d',
						$wpdb->options,
						$wpdb->esc_like( '_riwth_rl_' ) . '%',
						$now
					)
				);
			}

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
				wp_send_json_error( array( 'message' => __( 'Could not save feedback.', 'riaco-was-this-helpful' ) ), 500 );
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

			$thanks_text = get_option( 'riwth_feedback_box_thanks_text', __( '✅ Thank you for your feedback!', 'riaco-was-this-helpful' ) );
			$thanks_url  = get_option( 'riwth_feedback_box_thanks_url', '' );

			if ( $thanks_url ) {
				$thanks_content = '<a href="' . esc_url( $thanks_url ) . '">' . esc_html( $thanks_text ) . '</a>';
			} else {
				$thanks_content = esc_html( $thanks_text );
			}

			$return = apply_filters(
				'riwth_ajax_feedback_sent_return',
				array(
					'trigger'    => 'showThankYou',
					'feedbackId' => absint( $feedback_id ),
					'content'    => '<div class="riwth-thank-you">' . $thanks_content . '</div>',
				)
			);

			wp_send_json( $return );
		}
	}
}
