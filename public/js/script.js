jQuery(document).ready(function ($) {
  var feedbackGiven = getCookie("feedback_given");
  var feedbackArray = feedbackGiven ? feedbackGiven.split(",") : [];

  $(".ri-wth-helpful-feedback button").on("click", function () {
    var button = $(this);
    var helpful = button.hasClass("ri-wth-helpful-yes") ? 1 : 0;
    var nonce = button.data("nonce");

    $(".ri-wth-helpful-feedback").html(
      '<div class="ri-wth-loader">' + ri_wth_scripts.submitting + "</div>"
    );

    const data = {
      action: "ri_wth_save_feedback",
      post_id: ri_wth_scripts.postId,
      helpful: helpful,
      nonce: nonce,
    };

    $.ajax({
      type: "POST",
      url: ri_wth_scripts.ajax_url,
      data: data,
      success: function (response) {
        $(".ri-wth-helpful-feedback").html(
          '<div class="ri-wth-thank-you">' + ri_wth_scripts.thank_you + "</div>"
        );
        feedbackArray.push(ri_wth_scripts.postId);
        setCookie("feedback_given", feedbackArray.join(","), 365);
      },
    });
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
