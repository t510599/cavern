<?php
set_include_path('../../include/');
$includepath = TRUE;
require_once('../../config.php');
require_once('../../connection/SQL.php');
require_once('user.php');
require_once('security.php');

$user = validate_user();
if (!$user->valid) {
    send_error(403, "novalid");
} else if (!($user->level >= 8)) {
    send_error(403, "nopermission");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // modify blog settings
    if (!validate_csrf()) {
        send_error(403, "csrf");
    }

    $config_filename = "../../config.php";
    $template_filename = "../../config.template";

    if (!is_writable($config_filename)) {
        send_error(500, "notwritable");
    }

    try {
        $limit = abs(intval(@$_POST["limit"]));
        $content = file_get_contents($template_filename);
        $new_content = strtr($content, array(
            "{blog_name}" => addslashes(@htmlspecialchars($_POST["name"])),
            "{limit}" => ($limit != 0 ? $limit : 10),
            "{register}" => (@$_POST["register"] === "true" ? "true" : "false")
        ));

        file_put_contents($config_filename, $new_content);
        $result = json_encode(array('status' => TRUE, "time" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000)));
    } catch (Exception $e) {
        http_response_code(500);
        $result = json_encode(array('status' => $e->getMessage(), "time" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000)));
    }

    header('Content-Type: application/json');
    echo $result;
    exit;
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // fetch settings
    header('Content-Type: application/json');
    echo json_encode(array_merge(array('status' => TRUE, "time" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000)), $blog));
    exit;
}

function send_error($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array('status' => $message));
    exit;
}
