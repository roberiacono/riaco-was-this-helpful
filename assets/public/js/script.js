(function () {
  function init() {
    var feedbackGiven = getCookie("riwth_feedback_given");
    var feedbackArray = feedbackGiven ? feedbackGiven.split(",") : [];

    document
      .querySelectorAll(".riwth-helpful-feedback button")
      .forEach(function (button) {
        button.addEventListener("click", function () {
          var helpful = button.classList.contains("riwth-helpful-yes") ? 1 : 0;
          var nonce = button.dataset.nonce;

          document.dispatchEvent(new CustomEvent("showSubmitting"));

          var body = new URLSearchParams({
            action: "riwth_save_feedback",
            post_id: riwth_scripts.postId,
            helpful: helpful,
            nonce: nonce,
          });

          fetch(riwth_scripts.ajax_url, { method: "POST", body: body })
            .then(function (response) {
              return response.json();
            })
            .then(function (response) {
              if (!response.success && response.success !== undefined) {
                // Rate-limited or other server error — restore original box content.
                document.dispatchEvent(new CustomEvent("showError"));
                return;
              }
              var data = response.data || response;
              if (data.feedbackId) {
                document.dispatchEvent(
                  new CustomEvent(data.trigger, {
                    detail: {
                      feedbackId: data.feedbackId,
                      content: data.content,
                    },
                  })
                );
              } else {
                document.dispatchEvent(new CustomEvent(data.trigger));
              }
              feedbackArray.push(riwth_scripts.postId);
              setCookie("riwth_feedback_given", feedbackArray.join(","), 365);
            })
            .catch(function () {
              document.dispatchEvent(new CustomEvent("showError"));
            });
        });
      });

    document.addEventListener("showSubmitting", function () {
      document
        .querySelectorAll(".riwth-helpful-feedback")
        .forEach(function (el) {
          el.innerHTML =
            '<div class="riwth-loader">' + riwth_scripts.submitting + "</div>";
        });
    });

    document.addEventListener("showThankYou", function (event) {
      var params = event.detail || {};
      document
        .querySelectorAll(".riwth-helpful-feedback")
        .forEach(function (el) {
          el.innerHTML = params.content || "";
        });
    });
  }

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
      while (c.charAt(0) === " ") {
        c = c.substring(1, c.length);
      }
      if (c.indexOf(nameEQ) === 0) {
        return c.substring(nameEQ.length, c.length);
      }
    }
    return null;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
