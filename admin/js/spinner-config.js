jQuery(document).ready(function ($) {
  for (const [key, value] of Object.entries(spinnerConfig)) {
    $(`.hbm-${key}-message`).hide();
    $(`.hbm-${key}-spinner`).hide();
    $(`#hbm-${key}`).on("click", function () {
      // Show the spinner
      $(`.hbm-${key}-spinner`).show();
      $(`.hbm-${key}-wrapper`).hide();

      $.ajax({
        url: ajaxurl, // WordPress AJAX URL
        type: "POST",
        data: {
          action: `${value}`,
          // Pass any necessary data, e.g., client ID, tenant ID, etc.
        },
        success: function (response) {
          // Hide the spinner
          $(`.hbm-${key}-spinner`).hide();

          if (response.success) {
            $(`.hbm-${key}-message`).show();
            setTimeout(function () {
              $(`.hbm-${key}-wrapper`).show();
              $(`.hbm-${key}-message`).hide();
            }, 3000); // 1000 milliseconds = 1 second
          } else {
            alert("Error: " + response.data.message);
          }
        },
      });
    });
  }
});
