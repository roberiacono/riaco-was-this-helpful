<?php 
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