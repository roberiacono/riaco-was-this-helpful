<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Metabox' ) ) {
	class RIWTH_Metabox {
		public function __construct() {
			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post', array( $this, 'save_metabox' ) );
		}

		public function add_metabox() {
			if ( ! current_user_can( 'edit_posts' ) || ! RIWTH_Functions::could_display_box() ) {
				return;
			}

			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'riwth_metabox',
					esc_html__( 'Helpful Settings', 'riaco-was-this-helpful' ),
					array( $this, 'render_metabox' ),
					esc_html( $post_type ),
					'side',
					'default'
				);
			}
		}

		public function render_metabox( $post ) {
			wp_nonce_field( 'riwth_metabox_nonce', 'riwth_metabox_nonce' );
			$value = get_post_meta( $post->ID, '_riwth_disable_box', true );
			?>
		<label for="riwth_disable_box">
			<input type="checkbox" name="riwth_disable_box" id="riwth_disable_box" value="1" <?php checked( $value, '1' ); ?>>
			<?php esc_attr_e( 'Disable automatic Helpful box at the bottom of this post.', 'riaco-was-this-helpful' ); ?>
		</label>
			<?php
		}

		public function save_metabox( $post_id ) {
			if ( ! isset( $_POST['riwth_metabox_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['riwth_metabox_nonce'] ) ), 'riwth_metabox_nonce' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( isset( $_POST['riwth_disable_box'] ) && $_POST['riwth_disable_box'] === '1' ) {
				update_post_meta( $post_id, '_riwth_disable_box', '1' );
			} else {
				delete_post_meta( $post_id, '_riwth_disable_box' );
			}
		}
	}
}
