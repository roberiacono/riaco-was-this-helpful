<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RIWTH_User_Role' ) ) {
	class RIWTH_User_Role {

		public function can_user_see_stats() {
			$user          = wp_get_current_user();
			$allowed_roles = get_option( 'riwth_display_by_user_role', array() );
			$allowed_roles = is_array( $allowed_roles ) ? $allowed_roles : array();
			$can_see       = (bool) array_intersect( $allowed_roles, $user->roles );

			return (bool) apply_filters( 'riwth_can_user_see_stats', $can_see, $user );
		}
	}
}
