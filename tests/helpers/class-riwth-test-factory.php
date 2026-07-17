<?php
/**
 * Static factory methods for creating posts, feedback rows, users, and
 * rate-limit state in known states for the test suite.
 *
 * @package RIWTH
 */

/**
 * Test data factory.
 */
class RIWTH_Test_Factory {

	/**
	 * @param array $args post_type/post_title/post_status overrides.
	 * @return int Post ID.
	 */
	public static function create_published_post( $args = array() ) {
		return wp_insert_post(
			array_merge(
				array(
					'post_type'    => 'post',
					'post_title'   => 'Test Post',
					'post_content' => 'Content',
					'post_status'  => 'publish',
				),
				$args
			)
		);
	}

	/**
	 * @param array $args post_type/post_title overrides.
	 * @return int Post ID.
	 */
	public static function create_draft_post( $args = array() ) {
		return self::create_published_post( array_merge( array( 'post_status' => 'draft' ), $args ) );
	}

	/**
	 * Insert a feedback row directly, bypassing RIWTH_Ajax::save_feedback(),
	 * so tests can seed exact DB states.
	 *
	 * @param int   $post_id Post ID.
	 * @param int   $helpful 1|0.
	 * @param array $args    Optional override: created_at (mysql UTC string).
	 * @return int Inserted row id.
	 */
	public static function insert_feedback_row( $post_id, $helpful, $args = array() ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . RIWTH_DB_NAME,
			array(
				'post_id'    => $post_id,
				'helpful'    => $helpful ? 1 : 0,
				'created_at' => isset( $args['created_at'] ) ? $args['created_at'] : current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * @param int $post_id        Post ID.
	 * @param int $positive_count Number of positive rows to insert.
	 * @param int $negative_count Number of negative rows to insert.
	 */
	public static function seed_feedback( $post_id, $positive_count, $negative_count ) {
		for ( $i = 0; $i < $positive_count; $i++ ) {
			self::insert_feedback_row( $post_id, 1 );
		}
		for ( $i = 0; $i < $negative_count; $i++ ) {
			self::insert_feedback_row( $post_id, 0 );
		}
	}

	/**
	 * Reproduces RIWTH_Ajax::save_feedback()'s rate-limit option name
	 * formula, so tests can directly read/backdate the claim.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $ip      IP address.
	 * @return string wp_options option_name.
	 */
	public static function rate_limit_option_name( $post_id, $ip = '127.0.0.1' ) {
		return '_riwth_rl_' . md5( $ip . '|' . $post_id );
	}

	/**
	 * Force an existing rate-limit claim to look expired, without sleeping
	 * 30 real seconds.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $ip      IP address.
	 */
	public static function expire_rate_limit_claim( $post_id, $ip = '127.0.0.1' ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->options,
			array( 'option_value' => time() - 1 ),
			array( 'option_name' => self::rate_limit_option_name( $post_id, $ip ) ),
			array( '%d' ),
			array( '%s' )
		);
	}

	/**
	 * @return int User ID.
	 */
	public static function create_admin_user() {
		return self::create_user_with_role( 'administrator' );
	}

	/**
	 * @return int User ID.
	 */
	public static function create_editor_user() {
		return self::create_user_with_role( 'editor' );
	}

	/**
	 * @return int User ID.
	 */
	public static function create_subscriber_user() {
		return self::create_user_with_role( 'subscriber' );
	}

	/**
	 * @param string $role WP role slug.
	 * @return int User ID.
	 */
	public static function create_user_with_role( $role ) {
		$suffix = $role . '-' . wp_generate_password( 8, false, false );

		return wp_insert_user(
			array(
				'user_login' => 'riwth-test-' . $suffix,
				'user_pass'  => wp_generate_password(),
				'user_email' => 'riwth-test-' . $suffix . '@example.com',
				'role'       => $role,
			)
		);
	}
}
