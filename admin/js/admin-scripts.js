jQuery(document).ready(function ($) {
  // Attach a click listener to all buttons with class "hbm-copy-clipboard" within a div with class "hbm-clipboard"
  $(".hbm-clipboard .hbm-copy-clipboard").on("click", function () {
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
});
