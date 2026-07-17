<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Admin_Bar' ) ) {
	class RIWTH_Admin_Bar {

		public function __construct() {
			$show_admin_bar_content = get_option( 'riwth_show_admin_bar_content' );

			if ( $show_admin_bar_content === '1' ) {
				add_action( 'admin_bar_menu', array( $this, 'add_feedback_count_to_admin_bar' ), 99 );
			}
		}

		public function add_feedback_count_to_admin_bar( $wp_admin_bar ) {
			if ( ! current_user_can( 'edit_posts' ) || ! RIWTH_Functions::could_display_box() ) {
				return;
			}

			global $post;

			$positive_feedback_count = RIWTH_Functions::get_positive_feedback_count( $post->ID );
			$total_feedback_count    = RIWTH_Functions::get_total_feedback_count( $post->ID );

			$dot = '';
			if ( $total_feedback_count > 0 ) {
				$percentage = ( $positive_feedback_count / $total_feedback_count ) * 100;
				/* translators: %1$d: positive percentage. %2$d Positive feedback. %3$d total feedback. */
				$title = sprintf( __( '%1$d%% positive (%2$d/%3$d)', 'riaco-was-this-helpful' ), round( $percentage ), $positive_feedback_count, $total_feedback_count );
				$color = RIWTH_Functions::GreenYellowRed( round( $percentage ) );
				$dot   = '<span style="background-color: ' . esc_attr( $color ) . '; margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block; vertical-align: middle;"></span>';
			} else {
				$title = __( 'No feedback yet', 'riaco-was-this-helpful' );
			}

			$args = array(
				'id'    => 'riwth-feedback-count',
				'title' => sprintf(
					'<span class="ab-icon dashicons dashicons-thumbs-up"></span> %s%s',
					$dot,
					esc_html( $title )
				),
				'href'  => esc_url( get_edit_post_link( $post->ID ) ),
				'meta'  => array(
					'class' => 'riwth-feedback-count',
				),
				'icon'  => 'dashicons-thumbs-up',
			);
			$wp_admin_bar->add_node( $args );
		}
	}
}
