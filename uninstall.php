<?php

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Don't delete any data if the PRO version is already active.
if ( class_exists( 'RI_Was_This_Helpful_Pro' ) ) {
	return;
}

// Don't delete any data if user doesn't want.
if ( ! get_option( 'ri_wth_uninstall_remove_data' ) ) {
	return;
}

// Delete plugin options
delete_option( 'ri_wth_display_on' );
delete_option( 'ri_wth_display_by_user_role' );
delete_option( 'ri_wth_load_styles' );
delete_option( 'ri_wth_load_scripts' );
delete_option( 'ri_wth_show_admin_bar_content' );
delete_option( 'ri_wth_feedback_box_template' );
delete_option( 'ri_wth_feedback_box_text' );
delete_option( 'ri_wth_feedback_box_positive_button_text' );
delete_option( 'ri_wth_feedback_box_positive_button_icon' );
delete_option( 'ri_wth_feedback_box_negative_button_text' );
delete_option( 'ri_wth_feedback_box_negative_button_icon' );
delete_option( 'ri_wth_feedback_box_color_background' );
delete_option( 'ri_wth_feedback_box_color_positive_button' );
delete_option( 'ri_wth_feedback_box_color_positive_text' );
delete_option( 'ri_wth_feedback_box_color_negative_button' );
delete_option( 'ri_wth_feedback_box_color_negative_text' );
delete_option( 'ri_wth_feedback_box_border_button_rounded' );
delete_option( 'ri_wth_uninstall_remove_data' );

global $wpdb;
$table_name = $wpdb->prefix . 'ri_wth_helpful_feedback';
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
