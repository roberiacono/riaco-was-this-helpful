<?php 

function ri_wth_add_feedback_column($columns) {
    $columns['helpful_feedback'] = __('Was this helpful?', 'ri-was-this-helpful');
    return $columns;
}
add_filter('manage_posts_columns', 'ri_wth_add_feedback_column');

function ri_wth_display_feedback_column($column, $post_id) {
    if ($column == 'helpful_feedback') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ri_helpful_feedback';

        $total_feedback = wp_cache_get( 'ri_wth_total_feedback_'. $post_id );
        if ( false === $total_feedback) {
            $total_feedback = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE post_id = %d", array( $post_id)));
            wp_cache_set( 'ri_wth_total_feedback_'. $post_id, $total_feedback , '', 24 * 60 * 60 );
        }

        $positive_feedback = wp_cache_get( 'ri_wth_positive_feedback_'. $post_id );
        if ( false === $positive_feedback) {
            $positive_feedback = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$table_name` WHERE post_id = %d AND helpful = 1", array( $post_id)));
            wp_cache_set( 'ri_wth_positive_feedback_'.$post_id, $positive_feedback, '', 24 * 60 * 60 );
        }

        if ($total_feedback > 0) {
            $percentage = ($positive_feedback / $total_feedback) * 100;
            echo esc_html(round($percentage, 2) . '% ' . __('positive', 'ri-was-this-helpful') . ' ('. $positive_feedback .'/' . $total_feedback . ')');
        } else {
            echo esc_html(__('No feedback yet', 'ri-was-this-helpful'));
        }
    }
}
add_action('manage_posts_custom_column', 'ri_wth_display_feedback_column', 10, 2);