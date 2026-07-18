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

          var extraParams = {};
          document.dispatchEvent(
            new CustomEvent("riwthBeforeSubmit", {
              detail: {
                postId: riwth_scripts.postId,
                helpful: helpful,
                extraParams: extraParams,
              },
            })
          );

          var body = new URLSearchParams(
            Object.assign(
              {
                action: "riwth_save_feedback",
                post_id: riwth_scripts.postId,
                helpful: helpful,
                nonce: nonce,
              },
              extraParams
            )
          );

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
      // params.content is server-escaped (esc_html/esc_url) in RIWTH_Ajax::save_feedback()
      // before it ever reaches the browser — this client does no sanitization of its own,
      // so that escaping must be preserved by any future change there or by a consumer of
      // the riwth_ajax_feedback_sent_return filter.
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
    // Secure is only added when actually on HTTPS: browsers silently drop
    // Secure cookies set over a plain HTTP connection, so it can't be unconditional.
    var secure = location.protocol === "https:" ? "; Secure" : "";
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax" + secure;
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
