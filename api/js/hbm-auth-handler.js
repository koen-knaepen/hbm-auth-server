// hbm-auth-handler.js

var currentNonce = hbmApiSettings.nonce; // Store the current login nonce
var intervalID;

function renewNonce($) {
  // AJAX call to fetch a new nonce
  $.ajax({
    url: hbmApiSettings.ajax_url,
    method: "POST",
    data: {
      action: "hbm_renew_nonce",
    },
  })
    .done(function (newNonce) {
      currentNonce = newNonce; // Update the nonce dynamically
    })
    .fail(function (error) {
      console.log("Error renewing nonce:", error);
    });
}

jQuery(document).ready(function ($) {
  // Attach click listener to all elements with data-hbm-auth attribute
  let new_window, new_tab, modal, hidden_iframe, display;
  var parsedData = {};
  $("[data-hbm-auth]").on("click", function hbmEndpoint(e) {
    e.preventDefault(); // Prevent default action

    // Parse the data attribute value
    let dataValue = $(this).attr("data-hbm-auth");
    console.log("dataValue:", dataValue);
    try {
      parsedData = JSON.parse(dataValue);
    } catch (error) {
      console.error("Invalid data-hbm-auth value:", error);
      return;
    }
    console.log("parsedData:", parsedData);
    const { mode, action, display_logout, display_login } = parsedData;

    if (mode === "live") {
      display = action === "logout" ? display_logout : display_login;
    } else {
      display = "new-window";
    }

    // Check if both mode and action are present
    if (!parsedData || !mode || !action || !display) {
      console.error("Both mode and action are required in data-hbm-auth value");
      return;
    }

    // Make AJAX request to generate the endpoint
    $.ajax({
      url: hbmApiSettings.endpointURL,
      method: "POST",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", currentNonce);
      },
      data: parsedData,
    })
      .done(function (response) {
        // start listening for the auth cookie to change
        intervalID = setInterval(checkSpecificCookieChange, 1000); // Check every second

        // Redirect based on the display mode
        switch (display) {
          case "new-window":
            // Your existing code for opening in a new window
            let popupWidth = 800; // Width of the popup window
            let popupHeight = 600; // Height of the popup window
            let left = window.innerWidth / 2 - popupWidth / 2;
            let top = window.innerHeight / 2 - popupHeight / 2;
            new_window = window.open(
              response.endpoint,
              response.identifier,
              `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=${popupWidth}, height=${popupHeight}, top=${top}, left=${left}`
            );
            break;
          case "new-tab":
            new_tab = window.open(response.endpoint, "_blank");
            break;
          case "modal":
            // Create a modal with an iframe
            $("body").append(
              '<div id="hbmModal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);"><iframe src="' +
                response.endpoint +
                '" style="width:80%;height:80%;border:none;margin:10% auto;display:block;"></iframe></div>'
            );
            break;
          case "hidden-iframe":
            $("body").append(
              '<iframe id="hbm-iframe" src="' +
                response.endpoint +
                '"></iframe>'
            );
            break;
          default:
            console.error("Invalid display mode:", display);
        }
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Failed to generate endpoint:", textStatus, errorThrown);
      });
  });

  let specificCookieName = "hbm_new_auth_action";
  let lastCookieValue = getCookie(specificCookieName);
  let intervalID;

  function getCookie(name) {
    let value = "; " + document.cookie;
    let parts = value.split("; " + name + "=");
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  function checkSpecificCookieChange() {
    let currentCookieValue = getCookie(specificCookieName);
    if (currentCookieValue && currentCookieValue !== lastCookieValue) {
      var event = jQuery.Event("newHBMAuth");
      event.cookieName = specificCookieName;
      event.cookieValue = JSON.parse(decodeURIComponent(currentCookieValue));
      lastCookieValue = currentCookieValue;
      // Trigger a custom event
      $(document).trigger(event);
    }
  }

  $(document).on("newHBMAuth", function (event) {
    console.log("New HBM Auth event:", event.cookieValue);
    const {
      text,
      action,
      mode,
      classList,
      redirectUrl,
      display_login,
      display_logout,
    } = event.cookieValue;
    $("[data-hbm-auth]").each(function () {
      $(this).text(text);
      $(this).attr(
        "data-hbm-auth",
        JSON.stringify({ mode, action, display_login, display_logout })
      );
      $(this).attr("class", classList);
    });

    switch (display) {
      case "new-window":
        new_window.close();
        break;
      case "new-tab":
        new_tab.close();
        break;
      case "modal":
        $("#hbmModal").remove();
        break;
      case "hidden-iframe":
        $("#hbm-iframe").remove();
        break;
      default:
        console.error("Invalid display mode:", display);
    }
    // Renew the nonce
    renewNonce($);

    clearInterval(intervalID);
    if (redirectUrl) {
      window.location.href = redirectUrl;
    }
  });
});
