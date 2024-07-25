<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Metabox_Stats' ) ) {
	class RIWTH_Metabox_Stats {
		public function __construct() {
				add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		}

		public function add_metabox() {

			if ( ! current_user_can( 'edit_posts' ) || ! RIWTH_Functions::could_display_box() ) {
				return;
			}

			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'riwth_metabox_stats',
					esc_html__( 'Helpful Stats', 'riwth-was-this-helpful' ),
					array( $this, 'render_metabox' ),
					$post_type,
					'side',
					'default'
				);
			}
		}

		public function render_metabox( $post ) {
			global $post;

			$positive_feedback_count = RIWTH_Functions::get_positive_feedback_count( $post->ID );
			$total_feedback_count    = RIWTH_Functions::get_total_feedback_count( $post->ID );

			if ( $total_feedback_count > 0 ) {
				$percentage = ( $positive_feedback_count / $total_feedback_count ) * 100;
				$rgb        = RIWTH_Functions::GreenYellowRed( round( $percentage ) );
				echo '<span style="background-color: rgb(' . esc_attr( $rgb ) . '); margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block;"></span>';
				/* translators: %1$d: positive percentage. %2$d Positive feedback. %3$d total feedback. */
				echo esc_html( sprintf( __( '%1$d%% positive (%2$d/%3$d)', 'riwth-was-this-helpful' ), round( $percentage ), $positive_feedback_count, $total_feedback_count ) );
			} else {
				esc_html_e( 'No feedback yet', 'riwth-was-this-helpful' );
			}
		}
	}
}
