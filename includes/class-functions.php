<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_Functions' ) ) {
	class RIWTH_Functions {

		// Function to get the positive feedback count for a post
		public static function get_positive_feedback_count( $post_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RIWTH_DB_NAME;

			// Try to get the cached value
			$cache_key         = 'riwth_positive_feedback_' . $post_id;
			$positive_feedback = wp_cache_get( $cache_key, 'riwth_feedback' );

			if ( false === $positive_feedback ) {

				$positive_feedback = get_transient( $cache_key );
				if ( false === $positive_feedback ) {

					$reset_date = get_post_meta( $post_id, '_riwth_reset_date', true );

					// Execute query based on whether reset date exists.
					if ( $reset_date ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$positive_feedback = (int) $wpdb->get_var(
							$wpdb->prepare(
								'SELECT COUNT(*) FROM %i WHERE post_id = %d AND helpful = 1 AND created_at >= %s',
								$table_name,
								$post_id,
								$reset_date
							)
						);
					} else {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$positive_feedback = (int) $wpdb->get_var(
							$wpdb->prepare(
								'SELECT COUNT(*) FROM %i WHERE post_id = %d AND helpful = 1',
								$table_name,
								$post_id
							)
						);
					}

					// Apply a filter to allow the Pro plugin to modify the query
					$positive_feedback = apply_filters( 'riwth_get_positive_feedback_filter', $positive_feedback, $table_name, $post_id );

					// Store the result in both object cache and transient cache
					wp_cache_set( $cache_key, $positive_feedback, 'riwth_feedback', 365 * DAY_IN_SECONDS ); // Object cache (for better performance)
					set_transient( $cache_key, $positive_feedback, 365 * DAY_IN_SECONDS );
				}
			}
			return $positive_feedback;
		}

		// Function to get the positive feedback count for a post
		public static function get_total_feedback_count( $post_id ) {
			global $wpdb;
			$table_name = $wpdb->prefix . RIWTH_DB_NAME;

			$cache_key      = 'riwth_total_feedback_' . $post_id;
			$total_feedback = wp_cache_get( $cache_key, 'riwth_feedback' );

			if ( false === $total_feedback ) {

				$total_feedback = get_transient( $cache_key );

				if ( false === $total_feedback ) {
					$reset_date = get_post_meta( $post_id, '_riwth_reset_date', true );

					if ( $reset_date ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$total_feedback = (int) $wpdb->get_var(
							$wpdb->prepare(
								'SELECT COUNT(*) FROM %i WHERE post_id = %d AND created_at >= %s',
								array(
									$table_name,
									$post_id,
									$reset_date,
								)
							)
						);
					} else {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$total_feedback = (int) $wpdb->get_var(
							$wpdb->prepare(
								'SELECT COUNT(*) FROM %i WHERE post_id = %d',
								array(
									$table_name,
									$post_id,
								)
							)
						);
					}

					// Apply a filter to allow the Pro plugin to modify the query
					$total_feedback = apply_filters( 'riwth_get_total_feedback_filter', $total_feedback, $table_name, $post_id );

					wp_cache_set( $cache_key, $total_feedback, 'riwth_feedback', 365 * DAY_IN_SECONDS );
					set_transient( $cache_key, $total_feedback, 365 * DAY_IN_SECONDS );
				}
			}

			return $total_feedback;
		}

		/**
		 * Return a privacy-preserving, non-reversible identifier for the current
		 * visitor's IP address. The raw IP is read internally and never returned —
		 * callers that need a stable-but-anonymous per-visitor key (e.g. rate
		 * limiting, duplicate-submission checks) should use this instead of
		 * reading $_SERVER['REMOTE_ADDR'] directly, so a raw IP is never persisted.
		 *
		 * @param string $context Optional context mixed into the hash (e.g. a post ID)
		 *                        so the same visitor produces a different key per context.
		 * @return string A sha256 hash, salted with the site's secret auth salt.
		 */
		public static function get_client_ip_hash( $context = '' ) {
			$ip_raw = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
			return hash( 'sha256', $ip_raw . '|' . $context . '|' . wp_salt() );
		}

		public static function feedback_given( $post_id ) {
			$feedback_given = isset( $_COOKIE['riwth_feedback_given'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['riwth_feedback_given'] ) ) : '';
			$feedback_array = explode( ',', $feedback_given );
			$given          = in_array( (string) $post_id, $feedback_array, true );

			return (bool) apply_filters( 'riwth_feedback_given', $given, $post_id );
		}

		public static function should_display_box() {
			global $post;

			// If we don't have a post object, return false
			if ( ! $post ) {
				return false;
			}

			// Check if the box is disabled for the current post
			$disable_box = get_post_meta( $post->ID, '_riwth_disable_box', true );
			if ( '1' === $disable_box ) {
				return false;
			}

			// Check if automatic insertion is disabled for this post type (manual placement only)
			$disabled_types = get_option( 'riwth_auto_insert_disabled', array() );
			$disabled_types = is_array( $disabled_types ) ? $disabled_types : array();
			if ( in_array( get_post_type( $post->ID ), $disabled_types, true ) ) {
				return false;
			}

			if ( self::feedback_given( $post->ID ) ) {
				return false;
			}

			/**
			 * Filter whether the feedback box should be displayed for the current post.
			 *
			 * Only reached once the '_riwth_disable_box' post meta and the
			 * 'riwth_feedback_given' cookie have both allowed display — those are
			 * hard, un-overridable checks (the site owner's explicit per-post
			 * opt-out and duplicate-vote protection). This filter can only override
			 * self::could_display_box()'s result (post-type/screen gate); use
			 * 'riwth_could_display_box' for the same purpose if you need it to also
			 * run in admin-UI visibility checks (metabox, shortcode).
			 *
			 * @param bool $should_display Whether the box should be shown.
			 * @param int  $post_id        The current post ID.
			 */
			return (bool) apply_filters( 'riwth_should_display_box', self::could_display_box(), $post->ID );
		}

		public static function could_display_box() {
			global $post;

			// If we don't have a post object, return false

			if ( ! $post ) {
				return false;
			}

			$options = get_option( 'riwth_display_on', array() );
			$options = is_array( $options ) ? $options : array();

			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();

				if ( is_admin() && $screen instanceof WP_Screen && is_main_query() && in_array( $screen->base, $options, true ) && in_array( get_post_type(), $options, true ) ) {
					return (bool) apply_filters( 'riwth_could_display_box', true, $post->ID );
				}
			}

			if ( is_main_query() && is_singular() && in_array( get_post_type(), $options, true ) ) {
				return (bool) apply_filters( 'riwth_could_display_box', true, $post->ID );
			}
			return (bool) apply_filters( 'riwth_could_display_box', false, $post->ID );
		}

		/**
		 * @deprecated Use green_yellow_red() instead. Kept as a thin alias since this
		 * is a public static method a companion add-on could be calling directly.
		 */
		public static function GreenYellowRed( $number ) {
			return self::green_yellow_red( $number );
		}

		public static function green_yellow_red( $number ) {
			--$number; // working with 0-99 will be easier
			$number = max( 0, min( 99, $number ) );

			// invert color scale
			$number = 99 - $number;

			if ( $number < 50 ) {
				// green to yellow
				$r = floor( 255 * ( $number / 50 ) );
				$g = 255;

			} else {
				// yellow to red
				$r = 255;
				$g = floor( 255 * ( ( 50 - $number % 50 ) / 50 ) );
			}
			$b = 0;

			return sprintf( '#%02x%02x%02x', (int) $r, (int) $g, $b );
		}

		/**
		 * SVG allowed html for front-end display.
		 */
		public static function get_svg_allowed_html() {
			$svg_allowed_html = array(
				'svg'    => array(
					'xmlns'           => array(),
					'class'           => array(),
					'fill'            => array(),
					'viewbox'         => array(),
					'role'            => array(),
					'aria-hidden'     => array(),
					'focusable'       => array(),
					'height'          => array(),
					'width'           => array(),
					'stroke'          => array(),
					'stroke-width'    => array(),
					'stroke-linecap'  => array(),
					'stroke-linejoin' => array(),
				),
				'path'   => array(
					'd'               => array(),
					'fill'            => array(),
					'stroke'          => array(),
					'stroke-width'    => array(),
					'stroke-linecap'  => array(),
					'stroke-linejoin' => array(),
				),
				'circle' => array(
					'cx' => array(),
					'cy' => array(),
					'r'  => array(),
				),
				'line'   => array(
					'x1' => array(),
					'x2' => array(),
					'y1' => array(),
					'y2' => array(),
				),
				'rect'   => array(
					'x'      => array(),
					'y'      => array(),
					'width'  => array(),
					'height' => array(),
					'rx'     => array(),
					'ry'     => array(),
					'fill'   => array(),
				),
			);

			return $svg_allowed_html; // wp_kses( $svg, $allowed_html );
		}
	}
}
