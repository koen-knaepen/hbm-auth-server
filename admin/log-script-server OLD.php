<?php

namespace HBM\auth_server;


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
