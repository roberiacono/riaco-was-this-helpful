<?php

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'ri_wth_display_on' );
delete_option( 'ri_wth_display_by_user_role' );
delete_option( 'ri_wth_load_styles' );
delete_option( 'ri_wth_load_scripts' );
delete_option( 'ri_wth_feedback_box_text' );
delete_option( 'ri_wth_feedback_box_positive_button_text' );
delete_option( 'ri_wth_feedback_box_negative_button_text' );

// Delete custom table

/*
global $wpdb;
$table_name = $wpdb->prefix . RI_WTH_DB_NAME;
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) ); */
