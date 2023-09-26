jQuery(document).ready(function ($) {
  // Attach a click listener to all buttons with class "hbm-copy-clipboard" within a div with class "hbm-clipboard"
  $(".hbm-clipboard .hbm-copy-clipboard").on("click", function () {
    event.preventDefault();
    event.stopPropagation();
    // Find the span within the clicked div and get its text content
    var copyText = $(this).closest(".hbm-clipboard").find("span").text();

    // Use jQuery to create a temporary textarea to copy the text
    var $temp = $("<textarea>");
    $("body").append($temp);
    $temp.val(copyText).select();
    document.execCommand("copy");
    $temp.remove();

    // Create a notification element using jQuery
    var $notification = $("<div>", {
      text: "Copied the URL: " + copyText,
      css: {
        position: "fixed",
        bottom: "10px",
        right: "10px",
        padding: "10px",
        backgroundColor: "#333",
        color: "#fff",
        borderRadius: "5px",
        zIndex: "10000", // Ensure it's on top of other elements
      },
    });
    $("body").append($notification);

    // Remove the notification after 2 seconds using jQuery
    setTimeout(function () {
      $notification.remove();
    }, 2000);
  });
  // Disable the reset button in PODS admin for as long the HBM plugin(s) are running
  var $inputButtons = $(
    '.pods-admin__content-container [name="pods_reset"],.pods-admin__content-container [name="pods_reset_deactivate"]'
  );

  // Wrap the input button with a div
  $inputButtons.each(function () {
    $(this).wrap('<div class="hbm-disable-wrapper"></div>');
  });
});
