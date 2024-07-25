<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Ajax' ) ) {
	class RI_WTH_Ajax {

		public function __construct() {
			add_action( 'wp_ajax_ri_wth_save_feedback', array( $this, 'save_feedback' ) );
			add_action( 'wp_ajax_nopriv_ri_wth_save_feedback', array( $this, 'save_feedback' ) );
		}

		public function save_feedback() {
			check_ajax_referer( 'ri_was_this_helpful_nonce', 'nonce' );

			global $wpdb;
			$post_id = intval( sanitize_text_field( $_POST['post_id'] ) );
			$helpful = intval( sanitize_text_field( $_POST['helpful'] ) ) ? 1 : 0;

			$table_name = $wpdb->prefix . RI_WTH_DB_NAME;
			$wpdb->insert(
				$table_name,
				array(
					'post_id'    => $post_id,
					'helpful'    => $helpful,
					'created_at' => current_time( 'mysql' ),
				),
			);

			$feedback_id = $wpdb->insert_id;

			delete_transient( 'ri_wth_total_feedback_' . $post_id );
			delete_transient( 'ri_wth_positive_feedback_' . $post_id );

			$return = apply_filters(
				'ri_wth_ajax_feedback_sent_return',
				array(
					'trigger'    => 'showThankYou',
					'feedbackId' => esc_arr( $feedback_id ),
					'content'    => '<div class="ri-wth-thank-you">' . esc_html( get_option( 'ri_wth_feedback_box_thanks_text' ) ) . '</div>',
				)
			);

			wp_send_json( $return );
			// wp_die();
		}
	}
}
