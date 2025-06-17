jQuery(document).ready(function ($) {
  var feedbackGiven = getCookie("riwth_feedback_given");
  var feedbackArray = feedbackGiven ? feedbackGiven.split(",") : [];

  console.log("riwth_scripts", riwth_scripts);

  $(".riwth-helpful-feedback button").on("click", function () {
    var button = $(this);
    var helpful = button.hasClass("riwth-helpful-yes") ? 1 : 0;
    var nonce = button.data("nonce");

    $(document).trigger("showSubmitting");

    const data = {
      action: "riwth_save_feedback",
      post_id: riwth_scripts.postId,
      helpful: helpful,
      nonce: nonce,
    };

    $.ajax({
      type: "POST",
      url: riwth_scripts.ajax_url,
      data: data,
      success: function (response) {
        console.log("response", response);
        if (response["feedbackId"] !== "undefined" && response["feedbackId"]) {
          $(document).trigger(response["trigger"], {
            feedbackId: response["feedbackId"],
            content: response["content"],
          });
        } else {
          $(document).trigger(response["trigger"]);
        }
        feedbackArray.push(riwth_scripts.postId);
        setCookie("riwth_feedback_given", feedbackArray.join(","), 365);
      },
    });
  });

  $(document).on("showSubmitting", function () {
    $(".riwth-helpful-feedback").html(
      '<div class="riwth-loader">' + riwth_scripts.submitting + "</div>"
    );
    $(".riwth-helpful-feedback-block").html(
      '<div class="riwth-loader">' + riwth_scripts.submitting + "</div>"
    );
  });
  $(document).on("showThankYou", function (event, params) {
    $(".riwth-helpful-feedback").html(params.content);
    $(".riwth-helpful-feedback-block").html(params.content);
  });

  function setCookie(name, value, days) {
    var expires = "";
    if (days) {
      var date = new Date();
      date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
      expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
  }

  function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == " ") c = c.substring(1, c.length);
      if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
  }
});
