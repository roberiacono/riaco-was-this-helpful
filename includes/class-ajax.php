<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Ajax' ) ) {
	class RIWTH_Ajax {

		public function __construct() {
			add_action( 'wp_ajax_riwth_save_feedback', array( $this, 'save_feedback' ) );
			add_action( 'wp_ajax_nopriv_riwth_save_feedback', array( $this, 'save_feedback' ) );
		}

		public function save_feedback() {
			check_ajax_referer( 'riwth_was_this_helpful_nonce', 'nonce' );

			global $wpdb;

			if ( ! isset( $_POST['post_id'] ) && ! isset( $_POST['helpful'] ) ) {
				return;
			}

			$post_id = intval( sanitize_text_field( $_POST['post_id'] ) );
			$helpful = intval( sanitize_text_field( $_POST['helpful'] ) ) ? 1 : 0;

			$table_name = $wpdb->prefix . RIWTH_DB_NAME;
			$wpdb->insert(
				$table_name,
				array(
					'post_id'    => $post_id,
					'helpful'    => $helpful,
					'created_at' => current_time( 'mysql' ),
				),
			);

			$feedback_id = $wpdb->insert_id;

			delete_transient( 'riwth_total_feedback_' . $post_id );
			delete_transient( 'riwth_positive_feedback_' . $post_id );

			$return = apply_filters(
				'riwth_ajax_feedback_sent_return',
				array(
					'trigger'    => 'showThankYou',
					'feedbackId' => esc_attr( $feedback_id ),
					'content'    => '<div class="riwth-thank-you">' . esc_html( get_option( 'riwth_feedback_box_thanks_text' ) ) . '</div>',
				)
			);

			wp_send_json( $return );
			// wp_die();
		}
	}
}
