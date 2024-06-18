<?php

function ri_wth_add_feedback_box($content) {
    if (is_single() && is_main_query()) {
        $nonce = wp_create_nonce('ri_was_this_helpful_nonce');
        $content .= '
            <div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback flex gap-4 items-center justify-center">
                <div class="ri-wth-text">' . __('Was this helpful?', 'ri-was-this-helpful') . '</div>
                <div class="ri-wth-buttons-container flex gap-2">
                <button id="ri-wth-helpful-yes" class="helpful-yes" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">👍</button>
                <button id="ri-wth-helpful-no" class="helpful-no" data-post_id="' . get_the_ID() . '" data-nonce="' . $nonce . '">👎</button>
                </div>
            </div>
        ';
    }
    return $content;
}
add_filter('the_content', 'ri_wth_add_feedback_box');