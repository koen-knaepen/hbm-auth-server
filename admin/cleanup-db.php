<?php
function hbm_cleanup_database()
{
    // Delete options created by Carbon Fields or any other data related to your plugin.
    global $wpdb;

    // SQL query to select all option names that start with 'hbm_'
    $query = "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_hbm-auth-%'";

    // Get the results
    $results = $wpdb->get_col($query);

    hbm_log("Deleting all options that start with 'hbm_'" . PHP_EOL . "Results: " . print_r($results, true));
    // Loop through each result and delete the option
    foreach ($results as $option_name) {
        delete_option($option_name);
    }

}