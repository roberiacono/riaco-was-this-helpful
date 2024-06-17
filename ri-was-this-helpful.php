<?php
/*
Plugin Name: Helpful Feedback
Description: Adds a "Was this helpful?" box at the end of posts with thumb-up/thumb-down buttons for feedback.
Version: 1.2
Author: Your Name
Text Domain: ri-wth-feedback
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

// Includi il file delle impostazioni
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';

// Caricamento dei file di localizzazione
function ri_wth_load_textdomain() {
    load_plugin_textdomain( 'ri-wth-feedback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ri_wth_load_textdomain' );

// Aggiungere il riquadro "Was this helpful?" ai post
function ri_wth_add_feedback_box($content) {
    if (is_single() && is_main_query()) {
        $content .= '
            <div id="ri-wth-helpful-feedback">
                <p>' . __('Was this helpful?', 'ri-wth-feedback') . '</p>
                <button id="ri-wth-helpful-yes" class="helpful-yes" data-post_id="' . get_the_ID() . '">👍</button>
                <button id="ri-wth-helpful-no" class"helpful-no" data-post_id="' . get_the_ID() . '">👎</button>
            </div>
        ';
    }
    return $content;
}
add_filter('the_content', 'ri_wth_add_feedback_box');

// Gestione della richiesta AJAX per il salvataggio del feedback
function ri_wth_save_feedback() {
    global $wpdb;
    $post_id = intval($_POST['post_id']);
    $helpful = intval($_POST['helpful']);
    
    $table_name = $wpdb->prefix . 'helpful_feedback';
    $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'helpful' => $helpful
        )
    );
    
    wp_die();
}
add_action('wp_ajax_ri_wth_save_feedback', 'ri_wth_save_feedback');
add_action('wp_ajax_nopriv_ri_wth_save_feedback', 'ri_wth_save_feedback');

// Creare la tabella nel database per salvare i feedback
function ri_wth_create_feedback_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'helpful_feedback';
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

// Funzione di attivazione per impostare le opzioni di caricamento su true
function ri_wth_activate_plugin() {
    if (false === get_option('ri_wth_load_styles')) {
        add_option('ri_wth_load_styles', 1);
    }
    if (false === get_option('ri_wth_load_scripts')) {
        add_option('ri_wth_load_scripts', 1);
    }
}
register_activation_hook(__FILE__, 'ri_wth_activate_plugin');

// Aggiungere la colonna nella schermata admin dei post
function ri_wth_add_feedback_column($columns) {
    $columns['helpful_feedback'] = __('Was this helpful?', 'ri-wth-feedback');
    return $columns;
}
add_filter('manage_posts_columns', 'ri_wth_add_feedback_column');

function ri_wth_display_feedback_column($column, $post_id) {
    if ($column == 'helpful_feedback') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'helpful_feedback';

        $total_feedback = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d", $post_id));
        $positive_feedback = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND helpful = 1", $post_id));

        if ($total_feedback > 0) {
            $percentage = ($positive_feedback / $total_feedback) * 100;
            echo round($percentage, 2) . '% ' . __('positive', 'ri-wth-feedback') . ' ('. $positive_feedback .'/' . $total_feedback . ')';
        } else {
            echo __('No feedback yet', 'ri-wth-feedback');
        }
    }
}
add_action('manage_posts_custom_column', 'ri_wth_display_feedback_column', 10, 2);

// Enqueue scripts e stili
function ri_wth_enqueue_scripts() {
    wp_enqueue_style('ri-wth-style', plugin_dir_url(__FILE__) . 'css/ri-wth-style.css');
    wp_enqueue_script('ri-wth-script', plugin_dir_url(__FILE__) . 'js/ri-wth-script.js', array(), false, true);
    wp_localize_script('ri-wth-script', 'ri_wth_scripts', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'thank_you' => __('Thank you for your feedback!', 'ri-wth-feedback'),
        'submitting' => __('Submitting...', 'ri-wth-feedback')
    ));
}
add_action('wp_enqueue_scripts', 'ri_wth_enqueue_scripts');


function ri_wth_maybe_enqueue_scripts() {
    if (get_option('ri_wth_load_styles')) {
        wp_enqueue_style('ri-wth-style');
    }
    if (get_option('ri_wth_load_scripts')) {
        wp_enqueue_script('ri-wth-script');
    }
}
add_action('wp_enqueue_scripts', 'ri_wth_maybe_enqueue_scripts', 20);
?>
