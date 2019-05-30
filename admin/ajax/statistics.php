<?php
set_include_path('../../include/');
$includepath = TRUE;
require_once('../../config.php');
require_once('../../connection/SQL.php');
require_once('user.php');

$user = validate_user();
if (!$user->valid) {
    send_error(403, "novalid");
} else if (!($user->level >= 8)) {
    send_error(403, "nopermission");
}

$post_count = intval(cavern_query_result("SELECT COUNT(*) AS `count` FROM `post`")['row']['count']);
$user_count = intval(cavern_query_result("SELECT COUNT(*) AS `count` FROM `user`")['row']['count']);
$comment_count = intval(cavern_query_result("SELECT COUNT(*) AS `count` FROM `comment`")['row']['count']);

header('Content-Type: application/json');
echo json_encode(array("fetch" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000), "name" => $blog['name'], "post" => $post_count, "user" => $user_count, "comment" => $comment_count));