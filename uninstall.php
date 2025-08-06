<?php

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! current_user_can( 'delete_plugins' ) ) {
	exit;
}

// Don't delete any data if the PRO version is already active.
if ( class_exists( 'RIWTH_Was_This_Helpful_Pro' ) ) {
	return;
}

// Don't delete any data if user doesn't want.
if ( ! get_option( 'riwth_uninstall_remove_data' ) ) {
	return;
}

// Delete plugin options
delete_option( 'riwth_display_on' );
delete_option( 'riwth_display_by_user_role' );
delete_option( 'riwth_load_styles' );
delete_option( 'riwth_load_scripts' );
delete_option( 'riwth_show_admin_bar_content' );
delete_option( 'riwth_feedback_box_template' );
delete_option( 'riwth_feedback_box_text' );
delete_option( 'riwth_feedback_box_positive_button_text' );
delete_option( 'riwth_feedback_box_positive_button_icon' );
delete_option( 'riwth_feedback_box_negative_button_text' );
delete_option( 'riwth_feedback_box_negative_button_icon' );
delete_option( 'riwth_feedback_box_color_background' );
delete_option( 'riwth_feedback_box_color_positive_button' );
delete_option( 'riwth_feedback_box_color_positive_text' );
delete_option( 'riwth_feedback_box_color_negative_button' );
delete_option( 'riwth_feedback_box_color_negative_text' );
delete_option( 'riwth_feedback_box_border_button_rounded' );
delete_option( 'riwth_uninstall_remove_data' );

// delete transient
delete_transient( 'riwth_feedback_box' );


global $wpdb;

// delete transient
$transient_pattern = '_transient_riwth_%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
$transients        = $wpdb->get_col(
	$wpdb->prepare(
		'SELECT option_name FROM %i WHERE option_name LIKE %s',
		array(
			$wpdb->options,
			$transient_pattern,
		)
	)
);

if ( ! empty( $transients ) ) {
	foreach ( $transients as $transient ) {
		$key = str_replace( '_transient_', '', $transient );
		if ( ! empty( $key ) ) {
			delete_transient( $key );
		}
	}
}

// delete cache
wp_cache_flush();

// delete table section
$table_name = $wpdb->prefix . 'riwth_helpful_feedback';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query(
	$wpdb->prepare(
		'DROP TABLE IF EXISTS %i',
		$table_name
	)
);
