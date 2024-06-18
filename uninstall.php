<?php

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'ri_wth_load_styles' );
delete_option( 'ri_wth_load_scripts' );

// Delete custom table
global $wpdb;
$table_name = $wpdb->prefix . 'ri_helpful_feedback';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
