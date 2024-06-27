<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_Metabox' ) ) {
	class RI_WTH_Metabox {
		public function __construct() {
			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post', array( $this, 'save_metabox' ) );
		}

		public function add_metabox() {
			if ( ! current_user_can( 'edit_posts' ) || ! RI_WTH_Functions::could_display_box() ) {
				return;
			}

			$post_types = get_post_types( array( 'public' => true ) );
			foreach ( $post_types as $post_type ) {
				add_meta_box(
					'ri_wth_metabox',
					__( 'Helpful Settings', 'ri-was-this-helpful' ),
					array( $this, 'render_metabox' ),
					$post_type,
					'side',
					'default'
				);
			}
		}

		public function render_metabox( $post ) {
			wp_nonce_field( 'ri_wth_metabox_nonce', 'ri_wth_metabox_nonce' );
			$value = get_post_meta( $post->ID, '_ri_wth_disable_box', true );
			?>
		<label for="ri_wth_disable_box">
			<input type="checkbox" name="ri_wth_disable_box" id="ri_wth_disable_box" value="1" <?php checked( $value, '1' ); ?>>
			<?php esc_attr_e( 'Disable Was This Helpful box on this post', 'ri-was-this-helpful' ); ?>
		</label>
			<?php
		}

		public function save_metabox( $post_id ) {
			if ( ! isset( $_POST['ri_wth_metabox_nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['ri_wth_metabox_nonce'], 'ri_wth_metabox_nonce' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			if ( isset( $_POST['ri_wth_disable_box'] ) ) {
				update_post_meta( $post_id, '_ri_wth_disable_box', '1' );
			} else {
				delete_post_meta( $post_id, '_ri_wth_disable_box' );
			}
		}
	}
}
