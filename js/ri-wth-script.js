jQuery(document).ready(function ($) {
  $("#ri-wth-helpful-feedback button").on("click", function () {
    var button = $(this);
    var post_id = button.data("post_id");
    var helpful = button.attr("id") === "ri-wth-helpful-yes" ? 1 : 0;
    var nonce = button.data("nonce");

    $("#ri-wth-helpful-feedback").html(
      '<div class="ri-wth-loader">⏳ ' + ri_wth_scripts.submitting + "</div>"
    );

    const data = {
      action: "ri_wth_save_feedback",
      post_id: post_id,
      helpful: helpful,
      nonce: nonce,
    };

    $.ajax({
      type: "POST",
      url: ri_wth_scripts.ajax_url,
      data: data,
      success: function (response) {
        $("#ri-wth-helpful-feedback").html(
          '<div class="ri-wth-thank-you">✅ ' +
            ri_wth_scripts.thank_you +
            "</div>"
        );
      },
    });
  });
});
