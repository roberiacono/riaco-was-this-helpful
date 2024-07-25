<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Admin_Bar' ) ) {
	class RI_WTH_Admin_Bar {

		public function __construct() {
			$show_admin_bar_content = get_option( 'ri_wth_show_admin_bar_content' );

			if ( $show_admin_bar_content === '1' ) {
				add_action( 'admin_bar_menu', array( $this, 'add_feedback_count_to_admin_bar' ), 99 );
			}
		}

		public function add_feedback_count_to_admin_bar( $wp_admin_bar ) {
			if ( ! current_user_can( 'edit_posts' ) || ! RI_WTH_Functions::could_display_box() ) {
				return;
			}

			global $post;

			$positive_feedback_count = RI_WTH_Functions::get_positive_feedback_count( $post->ID );
			$total_feedback_count    = RI_WTH_Functions::get_total_feedback_count( $post->ID );

			if ( $total_feedback_count > 0 ) {
				$percentage = ( $positive_feedback_count / $total_feedback_count ) * 100;
				/* translators: %1$d: percentage of positive feedback. %2$d number of positive feedback. %3$d number of total feedback. */
				$title = sprintf( __( '%1$d%% positive (%2$d/%3$d)', 'ri-was-this-helpful' ), round( $percentage ), $positive_feedback_count, $total_feedback_count );
			} else {
				$title = __( 'No feedback yet', 'ri-was-this-helpful' );
			}

			$args = array(
				'id'    => 'ri-wth-feedback-count',
				'title' => sprintf(
					'<span class="ab-icon dashicons dashicons-thumbs-up"></span> %s',
					esc_html( $title )
				),
				'href'  => esc_url( get_edit_post_link( $post->ID ) ),
				'meta'  => array(
					'class' => 'ri-wth-feedback-count',
				),
				'icon'  => 'dashicons-thumbs-up',
			);
			$wp_admin_bar->add_node( $args );
		}
	}
}
