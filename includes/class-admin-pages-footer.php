<?php
/**
 * Admin Page Footer Class
 *
 * @package RIACO_Was_This_Helpful
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RIWTH_Admin_Pages_Footer' ) ) {
	/**
	 * Class RIWTH_Admin_Page_Footer
	 */
	class RIWTH_Admin_Pages_Footer {

		/**
		 * Constructor for the RIWTH_Admin_Pages_Footer class.
		 */
		public function __construct() {
			// Admin footer text.
			add_filter( 'admin_footer_text', array( $this, 'admin_footer' ), 1, 2 );
		}

		/**
		 * When user is on admin page, display footer text
		 * that graciously asks them to rate us.
		 *
		 * @since 2.1.1
		 *
		 * @param string $text Current footer text.
		 *
		 * @return string
		 */
		public function admin_footer( $text ) {
			global $current_screen;

			if ( ! empty( $current_screen->id ) && $this->is_plugin_link_page() ) {
				$url  = 'https://wordpress.org/support/plugin/riaco-was-this-helpful/reviews/?filter=5#new-post';
				$text = sprintf(
					wp_kses(
					/* translators: $1$s - WP.org review link; $2$s - WP.org review link. */
						__( 'Enjoying <strong>Was This Helpful</strong>? Please rate <a href="%1$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%2$s" target="_blank" rel="noopener">WordPress.org</a> to help us spread the word. Thank you! – Roberto Iacono', 'riaco-was-this-helpful' ),
						array(
							'a'      => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
							'strong' => array(),
						)
					),
					$url,
					$url
				);
			}

			return $text;
		}

		/**
		 * Get the current screen ID.
		 *
		 * @since 2.1.1
		 *
		 * @param string|null $hook The screen hook. Default null.
		 * @return string The screen ID.
		 */
		private function get_screen_id( $hook = null ) {
			if ( is_null( $hook ) ) {
				$screen = get_current_screen();
				$hook   = $screen->id;
			}

			return $hook;
		}

		/**
		 * Check if we are on the plugin settings page.
		 *
		 * @since 2.1.1
		 *
		 * @return bool
		 */
		public function is_plugin_link_page() {
			$hook = $this->get_screen_id();
			// List of pages to check
			$plugin_pages = array(
				'riwth-settings',
				'riwth-shortcode',
			);
			foreach ( $plugin_pages as $page ) {
				if ( strpos( $hook, $page ) !== false ) {
					return true;
				}
			}

			return false;
		}
	}
}
