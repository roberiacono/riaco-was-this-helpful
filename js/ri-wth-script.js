document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("ri-wth-helpful-yes")
    .addEventListener("click", function () {
      ri_wth_send_feedback(this.dataset.post_id, 1);
    });
  document
    .getElementById("ri-wth-helpful-no")
    .addEventListener("click", function () {
      ri_wth_send_feedback(this.dataset.post_id, 0);
    });
});

function ri_wth_send_feedback(post_id, helpful) {
  var feedbackDiv = document.getElementById("ri-wth-helpful-feedback");
  feedbackDiv.innerHTML =
    "<p class='ri-wth-loader'>⏳ " + ri_wth_scripts.submitting + "</p>";

  var xhr = new XMLHttpRequest();
  xhr.open("POST", ri_wth_scripts.ajax_url, true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onreadystatechange = function () {
    if (xhr.readyState === 4 && xhr.status === 200) {
      setTimeout(function () {
        feedbackDiv.innerHTML =
          "<p class='ri-wth-thank-you'>✅ " + ri_wth_scripts.thank_you + "</p>";
      }, 500);
    }
  };
  xhr.send(
    "action=ri_wth_save_feedback&post_id=" + post_id + "&helpful=" + helpful
  );
}
