/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./src/helpful-box/view.js ***!
  \*********************************/
/**
 * Use this file for JavaScript code that you want to run in the front-end
 * on posts/pages that contain this block.
 *
 * When this file is defined as the value of the `viewScript` property
 * in `block.json` it will be enqueued on the front end of the site.
 *
 * Example:
 *
 * ```js
 * {
 *   "viewScript": "file:./view.js"
 * }
 * ```
 *
 * If you're not making any changes to this file because your project doesn't need any
 * JavaScript running in the front-end, then you should delete this file and remove
 * the `viewScript` property from `block.json`.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#view-script
 */

/* eslint-disable no-console */
console.log("Hello World! (from create-block-helpful-box-block block)");
/* eslint-enable no-console */

jQuery(document).ready(function ($) {
  $(document).on("click", ".riwth-helpful-feedback-block .wp-block-button", function (e) {
    e.preventDefault();
    const button = $(this);
    const helpful = button.hasClass("riwth-helpful-yes") ? 1 : 0;
    $(document).trigger("showSubmitting");
    $.ajax({
      type: "POST",
      url: riwth_scripts.ajax_url,
      data: {
        action: "riwth_save_feedback",
        post_id: riwth_scripts.postId,
        helpful: helpful,
        nonce: riwth_scripts.nonce
      },
      success: function (response) {
        console.log("response", response);
        if (response["feedbackId"] && response["trigger"]) {
          $(document).trigger(response["trigger"], {
            feedbackId: response["feedbackId"],
            content: response["content"]
          });
        } else if (response["trigger"]) {
          $(document).trigger(response["trigger"]);
        }
        if (typeof feedbackArray !== "undefined") {
          feedbackArray.push(riwth_scripts.postId);
          setCookie("riwth_feedback_given", feedbackArray.join(","), 365);
        }
      }
    });
  });
});
/******/ })()
;
//# sourceMappingURL=view.js.map