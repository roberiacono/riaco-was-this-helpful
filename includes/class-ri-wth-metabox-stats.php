<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Metabox_Stats' ) ) {
	class RI_WTH_Metabox_Stats {
		public function __construct() {
				add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		}

		public function add_metabox() {

			if ( ! current_user_can( 'edit_posts' ) || ! RI_WTH_Functions::could_display_box() ) {
				return;
			}

			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'ri_wth_metabox_stats',
					__( 'Helpful Stats', 'ri-was-this-helpful' ),
					array( $this, 'render_metabox' ),
					$post_type,
					'side',
					'default'
				);
			}
		}

		public function render_metabox( $post ) {
			global $post;

			$positive_feedback_count = RI_WTH_Functions::get_positive_feedback_count( $post->ID );
			$total_feedback_count    = RI_WTH_Functions::get_total_feedback_count( $post->ID );

			if ( $total_feedback_count > 0 ) {
				$percentage = ( $positive_feedback_count / $total_feedback_count ) * 100;
				$rgb        = RI_WTH_Functions::GreenYellowRed( round( $percentage ) );
				echo '<span style="background-color: rgb(' . esc_html( $rgb ) . '); margin-right: 5px; border-radius: 50%; width: 0.5rem; height: 0.5rem; display: inline-block;"></span>';
				echo round( esc_html( $percentage ), 2 ) . '% ' . __( 'positive', 'ri-was-this-helpful' ) . ' (' . esc_html( $positive_feedback_count ) . '/' . esc_html( $total_feedback_count ) . ')';
			} else {
				esc_html( __( 'No feedback yet', 'ri-was-this-helpful' ) );
			}
		}
	}
}
