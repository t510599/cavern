<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../connection/SQL.php');
require_once('../config.php');
require_once('view.php');
require_once('security.php');
require_once('user.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Location: ../index.php?err=account");
    exit;
}

if (!$user->islogin) {
    http_response_code(401);
    header('Location: ../login.php?next=admin');
    exit;
} else if ($user->level < 8) {
    http_response_code(403);
    header('Location: ../index.php?err=permission');
    exit;
}

$view = new View('./theme/dashboard.html', 'theme/avatar.php', '', $blog['name'], "管理介面");
$view->render();