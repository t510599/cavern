<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../include/security.php');
require_once('../include/user.php');
require_once('../connection/SQL.php');
require_once('../config.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Content-Type: applcation/json");
    echo json_encode(array('status' => 'invalid'));
    exit;
}

if (!$user->islogin) {
    send_error(401, "nologin");
}

if (isset($_GET['fetch']) || isset($_GET['count'])) {
    if (isset($_GET['fetch'])) {
        $data = process_notifications(20); // fetch 20 comments
        $SQL->query("UPDATE `notification` SET `read` = 1 WHERE `read` = 0 AND `username` = '%s'", array($user->username)); // read all comments
    } else if (isset($_GET['count'])) {
        $query = cavern_query_result("SELECT COUNT(*) AS `count` FROM `notification` WHERE `username` = '%s' AND `read` = 0", array($user->username));
        $count = $query['row']['count'];
        $data = array("status" => TRUE, "fetch" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000), "unread_count" => $count);
    }
} else {
    send_error(404, "error");
}

header('Content-Type: application/json');
echo json_encode($data);
exit;

function process_notifications($limit) {
    global $user;
    $result = cavern_query_result("SELECT * FROM `notification` WHERE `username` = '%s' ORDER BY `time` DESC LIMIT %d" ,array($user->username, $limit));
    $json = array('status' => TRUE, 'fetch' => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)); // to fit javascript unit

    $feeds = array();
    
    if ($result['num_rows'] > 0) {
        do {
            $feeds[] = $result['row'];
        } while ($result['row'] = $result['query']->fetch_assoc());
    }

    $json['feeds'] = $feeds;
    return $json;
}

function send_error($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array('status' => $message, 'fetch' => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000))); // to fit javascript timestamp
    exit;
}
?>