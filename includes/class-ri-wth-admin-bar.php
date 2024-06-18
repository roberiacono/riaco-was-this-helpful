<?php

class RI_WTH_Admin_Bar {

	public function __construct() {
		add_action( 'admin_bar_menu', array( $this, 'add_feedback_count_to_admin_bar' ), 999 );
	}

	public function add_feedback_count_to_admin_bar( $wp_admin_bar ) {
		if ( ! current_user_can( 'edit_posts' ) || ! is_single() ) {
			return;
		}

		global $post, $wpdb;
		$table_name = $wpdb->prefix . 'ri_helpful_feedback';

		$positive_feedback_count = RI_WTH_Functions::get_positive_feedback_count( $post->ID );
		$total_feedback_count    = RI_WTH_Functions::get_total_feedback_count( $post->ID );

		if ( $total_feedback_count > 0 ) {
			$percentage = ( $positive_feedback_count / $total_feedback_count ) * 100;
			$title      = esc_html( round( $percentage, 2 ) . '% ' . __( 'positive', 'ri-was-this-helpful' ) . ' (' . $positive_feedback_count . '/' . $total_feedback_count . ')' );
		} else {
			$title = esc_html( __( 'No feedback yet', 'ri-was-this-helpful' ) );
		}

			$args = array(
				'id'    => 'ri-wth-feedback-count',
				'title' => sprintf(
					__( '<span class="ab-icon dashicons dashicons-smiley"></span> %s', 'ri-was-this-helpful' ),
					$title
				),
				'href'  => get_edit_post_link( $post->ID ),
				'meta'  => array(
					'class' => 'ri-wth-feedback-count',
				),
				'icon'  => 'dashicons-smiley',
			);
			$wp_admin_bar->add_node( $args );
	}
}

new RI_WTH_Admin_Bar();
