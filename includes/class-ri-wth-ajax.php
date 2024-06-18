<?php

class RI_WTH_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_ri_wth_save_feedback', array( $this, 'save_feedback' ) );
		add_action( 'wp_ajax_nopriv_ri_wth_save_feedback', array( $this, 'save_feedback' ) );
	}

	public function save_feedback() {
		check_ajax_referer( 'ri_was_this_helpful_nonce', 'nonce' );

		global $wpdb;
		$post_id = intval( $_POST['post_id'] );
		$helpful = intval( $_POST['helpful'] ) ? 1 : 0;

		$table_name = $wpdb->prefix . 'ri_helpful_feedback';
		$wpdb->insert(
			$table_name,
			array(
				'post_id' => $post_id,
				'helpful' => $helpful,
			)
		);

		wp_cache_delete( 'ri_wth_total_feedback_' . $post_id );
		wp_cache_delete( 'ri_wth_positive_feedback_' . $post_id );

		wp_die();
	}
}

new RI_WTH_Ajax();
