<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../connection/SQL.php');
require_once('../config.php');
require_once('user.php');
require_once('security.php');
require_once('notification.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Content-Type: applcation/json");
    echo json_encode(array('status' => 'invalid'));
    exit;
}

if (!isset($_GET['pid'])) {
    http_response_code(404);
    $data = array('status' => 'error');
} else {
    $pid = $_GET['pid'];

    $article = cavern_query_result("SELECT * FROM `post` WHERE `pid`='%d'", array($pid));
    if ($article['num_rows'] < 1) {
        http_response_code(404);
        echo json_encode(array('status' => 'nopost', 'id' => $pid));
        exit;
    }

    $likes_query = process_like($pid, $user);

    $islike = $likes_query[0];
    $likes = $likes_query[1];
    $likers = $likes_query[2];

    if (isset($_GET['fetch'])) {
        // fetch likes
        $data = array('status' => 'fetch', 'id' => $pid, 'likes' => $likes, 'likers' => $likers);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    } else if (!$user->islogin) {
        // ask guest to login
        $data = array('status' => 'nologin', 'id' => $pid, 'likes' => $likes, 'likers' => $likers);
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    } else {
        // user like actions
        if (!validate_csrf()) { // csrf attack!
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'csrf', 'id' => $pid, 'likes' => $likes, 'likers' => $likers));
            exit;
        }

        if ($islike) {
            // unlike
            $SQL->query("DELETE FROM `like` WHERE `pid`='%d' AND `username`='%s'", array($pid, $user->username));
            $result = process_like($pid, $user);
            $likes = $result[1];
            $likers = $result[2];
            $data = array('status' => FALSE, 'id' => $pid, 'likes' => $likes, 'likers' => $likers);
        } else {
            // like
            $SQL->query("INSERT INTO `like` (`pid`, `username`) VALUES ('%d', '%s')", array($pid, $user->username));
            $result = process_like($pid, $user);
            $likes = $result[1];
            $likers = $result[2];
            $data = array('status' => TRUE, 'id' => $pid, 'likes' => $likes, 'likers' => $likers);

            /* notification */
            // notify article author
            // we should notify author this only once
            $author = $article['row']['username'];
            $notification_query = cavern_query_result("SELECT * FROM `notification` WHERE `username`='%s' AND `url`='%s' AND `type`='%s'", array($author, "post.php?pid=$pid", "like"));
            if (!($notification_query['num_rows'] > 0) && $user->username !== $author) {
                cavern_notify_user($author, "{{$user->name}}@{$user->username} 推了 [{$article['row']['title']}]", "post.php?pid=$pid", "like");
            }
        }
    }
}

function process_like($pid, $user) {
    $islike = false;
    $likers = array();
    $likes_query = cavern_query_result("SELECT * FROM `like` WHERE `pid`='%d'", array($pid));

    if ($likes_query['num_rows'] < 1){
        $likes = 0;
    } else {
        $likes = $likes_query['num_rows'];
        do {
            $likers[] = $likes_query['row']['username'];
            if ($user->username === $likes_query['row']['username']) {
                $islike = true;
            }
        } while ($likes_query['row'] = $likes_query['query']->fetch_assoc());
    }

    return array($islike, $likes, array_unique($likers));
}

header('Content-Type: application/json');
echo json_encode($data);
exit;
?>