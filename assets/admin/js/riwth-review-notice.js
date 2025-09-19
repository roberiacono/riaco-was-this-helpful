jQuery(document).ready(function ($) {
  $(".riwth-review-action").on("click", function (e) {
    e.preventDefault();

    var $btn = $(this),
      action = $btn.data("action");

    $.post(
      RIWTH_Review.ajax_url,
      {
        action: "riwth_review_action",
        action_type: action,
        nonce: RIWTH_Review.nonce,
      },
      function (response) {
        if (response.success) {
          // If Leave Review, redirect to review page
          if (response.data.action === "review") {
            window.open(RIWTH_Review.review_url, "_blank");
          }

          $btn.closest(".riwth-review-notice").fadeOut();
        }
      }
    );
  });
});
