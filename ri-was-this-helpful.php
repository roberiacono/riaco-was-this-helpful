<?php
/*
Plugin Name: RI Was This Helpful
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 1.2.1
Author: Roberto Iacono
Text Domain: ri-was-this-helpful
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-columns.php';
require_once plugin_dir_path(__FILE__) . 'includes/wth-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-functions.php';



function ri_wth_load_textdomain() {
    load_plugin_textdomain( 'ri-was-this-helpful', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ri_wth_load_textdomain' );



function ri_wth_create_feedback_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ri_helpful_feedback';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        helpful tinyint(1) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'ri_wth_create_feedback_table');


function ri_wth_activate_plugin() {
    if (false === get_option('ri_wth_load_styles')) {
        add_option('ri_wth_load_styles', 1);
    }
    if (false === get_option('ri_wth_load_scripts')) {
        add_option('ri_wth_load_scripts', 1);
    }
}
register_activation_hook(__FILE__, 'ri_wth_activate_plugin');


function ri_wth_enqueue_scripts() {
    wp_enqueue_style('ri-wth-style', plugin_dir_url(__FILE__) . 'css/ri-wth-style.css');
    wp_enqueue_script('ri-wth-script', plugin_dir_url(__FILE__) . 'js/ri-wth-script.js', array(), false, true);
    wp_localize_script('ri-wth-script', 'ri_wth_scripts', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'thank_you' => __('Thank you for your feedback!', 'ri-was-this-helpful'),
        'submitting' => __('Submitting...', 'ri-was-this-helpful')
    ));
}
add_action('wp_enqueue_scripts', 'ri_wth_enqueue_scripts');


function ri_wth_maybe_enqueue_scripts() {
    if ( is_single() && is_main_query() ) {
        if (get_option('ri_wth_load_styles')) {
            wp_enqueue_style('ri-wth-style');
        }
        if (get_option('ri_wth_load_scripts')) {
            wp_enqueue_script('ri-wth-script');
        }
    }
}
add_action('wp_enqueue_scripts', 'ri_wth_maybe_enqueue_scripts', 20);
?>
