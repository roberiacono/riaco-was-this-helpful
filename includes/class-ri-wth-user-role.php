<?php

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'RI_WTH_User_Role' ) ) {
	class RI_WTH_User_Role {
		public function __construct() {
		}
		public function can_user_see_stats() {
			$user          = wp_get_current_user();
			$allowed_roles = array( 'editor', 'administrator' );
			if ( array_intersect( $allowed_roles, $user->roles ) ) {
				return true;
			}
			return false;
		}
	}
}
