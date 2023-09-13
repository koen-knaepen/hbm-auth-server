// deactivate-script.js

jQuery(document).ready(function ($) {
  $("#deactivate-hbm-entra-auth").on("click", function (e) {
    if (hbmData.shouldConfirm) {
      var userConfirmed = confirm(
        'The settings for deactivation will delete all data from the database for this plugin. If you want this press "OK", otherwise press "Cancel" and change first the settings.'
      );
      if (!userConfirmed) {
        e.preventDefault(); // Prevent the deactivation if the user does not confirm
      }
    }
  });
});
