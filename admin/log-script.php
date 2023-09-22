<?php

// Define the log directory and file
define('HBM_PLUGIN_LOG_DIR', HBM_PLUGIN_PATH . '.logs');
define('HBM_PLUGIN_SERVER_LOG', HBM_PLUGIN_LOG_DIR . '/server.log');
define('HBM_PLUGIN_ACCESS_LOG', HBM_PLUGIN_LOG_DIR . '/access.log');

// Ensure the .logs directory exists
if (!file_exists(HBM_PLUGIN_LOG_DIR)) {
    wp_mkdir_p(HBM_PLUGIN_LOG_DIR);
}

function hbm_error_log($message)
{
    // Check if it's an AJAX request
    if (defined('DOING_AJAX') && DOING_AJAX) {
        // If it's a Heartbeat API request, return early without logging
        if (isset($_POST['action']) && $_POST['action'] == 'heartbeat') {
            return;
        }
    }

    // If it's not a Heartbeat API request, log the message
    error_log($message);
}

/**
 * * Summary of extract_payload
 * @param mixed $state is a JWT token
 * @return stdClass returns the payload of the JWT token (NON verified 
 */
function extract_payload($state)
{
    list($header, $payload, $signature) = explode(".", $state);
    $decoded_payload = base64_decode($payload, false);
    return json_decode($decoded_payload);
}

function hbm_set_headers()
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
}

// @todo rewrite this so that the scripts are sent with a regiter instead of action
function echo_modal($redirect_url, $extra_content = '')
{
    hbm_set_headers();
    if (isset($redirect_url)) {
        $end_command = "window.location.href = '{$redirect_url}'";
    } else {
        $end_command = "window.close()";
    }
    $header_content = '<style>
            .custom-alert {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.1);
            z-index: 1000;
}
            .custom-alert-content {
                position: absolute;
                bottom: -9%; /* Adjust this to change vertical position */
                left: 95%;
                transform: translate(-50%, -50%);
                padding: 20px;
                background-color: #00adef;
                color: white;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                cursor: pointer;
            }
        </style>';
    $header_content .= "<script>
            var isCustomAlertDisplayed = false;
            function showAlert() {
                document.getElementById('customAlert').style.display = 'block';
                isCustomAlertDisplayed = true;
            }
            function closeAlert() {
                document.getElementById('customAlert').style.display = 'none';
                isCustomAlertDisplayed = false;
                {$end_command}
            }
            </script>";
    $script_content = '<script>showAlert();</script>';
    $extra_content .= '<div id="customAlert" class="custom-alert">
                    <div class="custom-alert-content" onclick="closeAlert()">
                    <p>Click to advance</p>
                    </div>
                    </div>';
    echo $header_content;
    echo $extra_content;
    echo $script_content;
}

function hbm_get_current_url()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $current_url;
}

function hbm_get_current_domain()
{
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $current_domain = $protocol . $_SERVER['HTTP_HOST'];
    return $current_domain;
}
