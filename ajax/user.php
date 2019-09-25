<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../connection/SQL.php');
require_once('../config.php');
require_once('user.php');

$user = validate_user();
if (!$user->valid) {
    send_error(403, "invalid", $user->islogin);
}

if (isset($_GET['username']) && trim($_GET['username']) != "") {
    // query other user's profile
    $username = trim($_GET['username']);
} else if ($user->islogin) {
    // query the profile of the user himself
    $username = $user->username;
} else {
    // username isn't provided
    send_error(404, "error", $user->islogin);
}

try {
    $target_user = new User($username);
} catch (NoUserException $_e) {
    send_error(404, "nouser", $user->islogin);
}

$posts = cavern_query_result("SELECT * FROM `post` WHERE `username`='%s'", array($username));
$posts_count = ($posts['num_rows'] > 0 ? $posts['num_rows'] : 0);

$data = array(
    "username" => $target_user->username,
    "name" => $target_user->name,
    "level" => $target_user->level,
    "role" => cavern_level_to_role($target_user->level),
    "hash" => md5(strtolower($target_user->email)),
    "muted" => $target_user->muted,
    "posts_count" => $posts_count
);

// user himself and admin can see user's email
if ($user->username === $target_user->username || $user->level >= 8) {
    $data["email"] = $target_user->email;
}

$data["login"] = $user->islogin;
$data["fetch"] = round($_SERVER['REQUEST_TIME_FLOAT'] * 1000); // fit javascript timestamp

header('Content-Type: application/json');
echo json_encode($data);
exit;

function send_error($code, $message, $islogin) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array("status" => $message, "login" => $islogin, "fetch" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000))); // to fit javascript timestamp
    exit;
}