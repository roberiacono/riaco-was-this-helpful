jQuery(document).ready(function ($) {
  $("#riwth-reset-button").on("click", function () {
    if (!confirm(riwthReset.confirm)) {
      return;
    }

    const $button = $(this);
    const $message = $(".riwth-reset-message");

    const postId = $("#post_ID").val(); // safe because WP sets this hidden input
    $.post(
      riwthReset.ajax_url,
      {
        action: "riwth_reset_stats",
        post_id: postId,
        nonce: riwthReset.nonce,
      },
      function (response) {
        if (response.success) {
          // Update the reset date dynamically
          const now = new Date();
          const formattedDate = now
            .toISOString()
            .slice(0, 19)
            .replace("T", " ");
          $(".riwth-reset-description--date").text(formattedDate);

          // Show success message
          $message
            .text(response.data || riwthReset.success)
            .css("color", "green")
            .fadeIn();

          setTimeout(function () {
            $message.fadeOut();
          }, 3000);
        } else {
          // Show error message
          $message
            .text(response.data || "Failed to reset statistics.")
            .css("color", "red")
            .fadeIn();
        }
      }
    );
  });
});
