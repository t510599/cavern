<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../connection/SQL.php');
require_once('../config.php');
require_once('security.php');
require_once('user.php');
require_once('article.php');
require_once('notification.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Content-Type: applcation/json");
    echo json_encode(array('status' => 'invalid'));
    exit;
}

if (!isset($_GET['pid']) && !isset($_GET['del']) && !isset($_POST['pid']) && !isset($_POST['edit'])) {
    send_error(404, "error");
} else {
    if (isset($_GET['pid']) && trim($_GET['pid']) != "") {
        if (isset($_SESSION['cavern_comment_time']) && $_SERVER['REQUEST_TIME'] - $_SESSION['cavern_comment_time'] > 10) {
            // after 10 seconds
            $_SESSION['cavern_comment_time'] = NULL;
            unset($_SESSION['cavern_comment_time']);
        }
        $data = process_comments($_GET['pid']);
    } else {
        if (!$user->islogin) { // guest
            send_error(401, "nologin");
        }
        if (!validate_csrf()) {
            send_error(403, "csrf");
        }

        if (isset($_GET['del']) && trim($_GET['del']) != "") {
            // delete comment
            $result = cavern_query_result("SELECT * FROM `comment` WHERE `id`='%d'", array($_GET['del']));
            if ($result['num_rows'] < 1) {
                send_error(404, "error");
            }

            $author = $result['row']['username'];

            if ($author !== $user->username) {
                send_error(403, false);
            }
            
            $SQL->query("DELETE FROM `comment` WHERE `id`='%d' AND `username`='%s'", array($_GET['del'], $user->username));
            $data = array(
                "status" => TRUE,
                "time" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)
            );
        } else if (isset($_POST['content'])) {
            if (isset($_POST['pid']) && trim($_POST['pid']) && isset($_SESSION['cavern_comment_time']) && $_SERVER['REQUEST_TIME'] - $_SESSION['cavern_comment_time'] < 10) {
                // user can create one comment per 10 seconds
                $remain_second = 10 - ($_SERVER['REQUEST_TIME'] - $_SESSION['cavern_comment_time']);
                header('Retry-After: ' . $remain_second);
                send_error(429, "ratelimit");
            }
            
            if ($user->muted) {
                send_error(403, "muted");
            }

            if (trim($_POST['content']) != "") {
                if (isset($_POST['pid']) && trim($_POST['pid']) != "") {
                    // new comment
                    try {
                        $article = new Article(intval($_POST['pid']));
                    } catch (NoPostException $e) {
                        send_error(404, "error");
                    }

                    http_response_code(201); // 201 Created
                    $time = date('Y-m-d H:i:s');
                    $SQL->query("INSERT INTO `comment` (`pid`, `username`, `time`, `content`) VALUES ('%d', '%s', '%s', '%s')", array($_POST['pid'], $user->username, $time, htmlspecialchars($_POST['content'])));
                    $comment_id = $SQL->insert_id();
                    $data = array(
                        "status" => TRUE,
                        "comment_id" => $comment_id,
                        "time" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)
                    );

                    /* notification */

                    // notify tagged user
                    // the user who tag himself is unnecessary to notify
                    $username_list = parse_user_tag($_POST['content']);
                    foreach ($username_list as $key => $id) {
                        if ($id == $user->username) continue;
                        cavern_notify_user($id, "{{$user->name}}@{$user->username} 在 [{$article->title}] 的留言中提到了你", "post.php?pid={$article->pid}#comment-$comment_id", "comment");
                    }

                    // notify commenters
                    $commenters = cavern_query_result("SELECT `username` FROM `comment` WHERE `pid` = '%d'", array($_POST['pid']));
                    if ($commenters['num_rows'] > 0) {
                        do {
                            $u = $commenters['row']['username'];
                            if (!in_array($u, $username_list) && $u != $article->author && $u != $user->username) {
                                cavern_notify_user($u, "在你回應的文章 [{$article->title}] 中有了新的回應", "post.php?pid={$article->pid}#comment-$comment_id", "comment");
                            }
                        } while ($commenters['row'] = $commenters['query']->fetch_assoc());
                    }

                    // notify liked user
                    /* we won't inform the author for his like on his own post
                       and no notice for his own comment */
                    $likers = cavern_query_result("SELECT `username` FROM `like` WHERE `pid` = '%d'", array($_POST['pid']));
                    if ($likers['num_rows'] > 0) {
                        do {
                            $u = $likers['row']['username'];
                            if (!in_array($u, $username_list) && $u != $article->author && $u != $user->username) {
                                cavern_notify_user($u, "在你喜歡的文章 [{$article->title}] 中有了新的回應", "post.php?pid={$article->pid}#comment-$comment_id", "comment");
                            }
                        } while ($likers['row'] = $likers['query']->fetch_assoc());
                    }
                    
                    // notify post author
                    /* we won't inform the author if he has been notified for being tagged
                       also, we won't notify the author for his own comment */
                    if (!in_array($article->author, $username_list) && $article->author != $user->username) {
                        cavern_notify_user($article->author, "{{$user->name}}@{$user->username} 回應了 [{$article->title}]", "post.php?pid={$article->pid}#comment-$comment_id", "comment");
                    }

                    // only new comment should be limited
                    $_SESSION['cavern_comment_time'] = $_SERVER['REQUEST_TIME'];
                } else if (isset($_POST['edit']) && trim($_POST['edit']) != "") {
                    // edit comment
                    $query = cavern_query_result("SELECT * FROM `comment` WHERE `id` = '%d'", array($_POST['edit']));
                    if ($query['num_rows'] < 1) {
                        send_error(404, "error");
                    }
                    if ($query['row']['username'] !== $user->username) {
                        send_error(403, "author");
                    }

                    $time = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
                    $SQL->query("UPDATE `comment` SET `content`='%s', `modified`='%s' WHERE `id`='%d' AND `username`='%s'", array(htmlspecialchars($_POST['content']), $time, $_POST['edit'], $user->username));
                    $data = array(
                        "status" => TRUE,
                        "comment_id" => $_POST['edit'],
                        "time" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)
                    );
                } else {
                    send_error(400, "empty");
                }
            } else {
                send_error(400, "empty");
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($data);
exit;

function process_comments($pid) {
    if (isset($_SESSION["cavern_username"])) {
        $user = new User($_SESSION["cavern_username"]);
    } else {
        $user = new User(""); // guest
    }

    if (cavern_query_result("SELECT * FROM `post` WHERE `pid`=%d", array($pid))['num_rows'] < 1) {
        http_response_code(404);
        $json = array('status' => 'error', 'fetch' => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)); // to fit javascript unit
        return $json;
    }

    if (isset($_COOKIE['cavern_commentLastFetch'])) {
        $last_fetch_time = $_COOKIE['cavern_commentLastFetch'];
    }

    $email_hash = array();
    $names = array();
    $id_list = array();
    $modified = array();
    $comments = array();
    $result = cavern_query_result("SELECT * FROM `comment` WHERE `pid`='%d'", array($pid));
    $json = array('status' => TRUE, 'fetch' => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000)); // to fit javascript unit

    if ($result['num_rows'] > 0) {
        do {
            $username = $result['row']['username'];
            if (!isset($names[$username])) {
                $target_user = new User($username);
                $name = $target_user->name;
                $email = $target_user->email;

                $names[$username] = $name;
                $email_hash[$username] = md5(strtolower($email));
            }

            $comment = array(
                "id" => $result['row']['id'],
                "username" => $username,
                "markdown" => $result['row']['content'],
                "time" => $result['row']['time'],
                "modified" => (is_null($result['row']['modified']) ? FALSE : $result['row']['modified'])
                // if the comment has been modified, set this value as modified time; otherwise, set to FALSE
            );

            if ($user->islogin && $user->username === $username) {
                $comment['actions'] = array("reply", "edit", "del");
            } else if ($user->islogin) {
                $comment['actions'] = array("reply");
            } else {
                $comment['actions'] = array();
            }
            $id_list[] = $comment['id']; // append id
            $comments[] = $comment; // append comment

            if (!is_null($result['row']['modified']) && isset($last_fetch_time)) {
                if (strtotime($result['row']['modified']) - $last_fetch_time > 0) {
                    $modified[] = $comment["id"];
                }
            }
        } while ($result['row'] = $result['query']->fetch_assoc());
    }
    $json['idList'] = $id_list;
    $json['modified'] = $modified;
    $json['comments'] = $comments;
    $json['names'] = $names;
    $json['hash'] = $email_hash;

    return $json;
}

function send_error($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array('status' => $message, 'fetch' => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000))); // to fit javascript timestamp
    exit;
}
?>