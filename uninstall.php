<?php

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'ri_wth_display_on' );
delete_option( 'ri_wth_load_styles' );
delete_option( 'ri_wth_load_scripts' );

// Delete custom table

global $wpdb;
$table_name = $wpdb->prefix . RI_WTH_DB_NAME;
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
