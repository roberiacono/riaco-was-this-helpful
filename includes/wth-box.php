<?php

// Aggiungere il riquadro "Was this helpful?" ai post
function ri_wth_add_feedback_box($content) {
    if (is_single() && is_main_query()) {
        $content .= '
            <div id="ri-wth-helpful-feedback" class="ri-wth-helpful-feedback flex gap-4 items-center">
                <div class="ri-wth-text">' . __('Was this helpful?', 'ri-wth-feedback') . '</div>
                <div class="ri-wth-buttons-container flex gap-4">
                <button id="ri-wth-helpful-yes" class="helpful-yes" data-post_id="' . get_the_ID() . '">👍</button>
                <button id="ri-wth-helpful-no" class"helpful-no" data-post_id="' . get_the_ID() . '">👎</button>
                </div>
            </div>
        ';
    }
    return $content;
}
add_filter('the_content', 'ri_wth_add_feedback_box');