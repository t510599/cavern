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

if ($_SERVER["REQUEST_METHOD"] == "PATCH" || $_SERVER["REQUEST_METHOD"] == "POST") {
    // patch: modify; post: create
    if (!validate_csrf()) {
        send_error(403, "csrf");
    }
    if ($_SERVER["REQUEST_METHOD"] == "PATCH") {
        parse_str(file_get_contents('php://input'), $_POST);
        // hack
    }

    if (isset($_POST['username']) && (isset($_POST['name']) || isset($_POST['password']))) {
        // modify account data
        $username = trim($_POST['username']);

        try {
            $target_user = new User($username);
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // create new user, but user exists
                send_error(409, "userexists");
            }
            // you cannot modify data of those with higher permission than you
            if ($target_user->level > $user->level) {
            	send_error(403, "nopermission");
            }
        } catch (NoUserException $e) {
            if ($_SERVER["REQUEST_METHOD"] == "PATCH") {
                // modify one that not exist -> error
                send_error(404, "nouser");
            } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // create new user
                $SQL->query("INSERT INTO `user` (`username`) VALUES ('%s')", array($username));
            }
        }

        if (trim($_POST['password']) != '') {
            $password = cavern_password_hash($_POST['password'], $username);
            $SQL->query("UPDATE `user` SET `pwd`='%s' WHERE `username`='%s'", array($password, $username));
        }
        if (trim($_POST['name']) != '' && strlen($_POST['name']) <= 40) {
            $SQL->query("UPDATE `user` SET `name`='%s' WHERE `username`='%s'", array(htmlspecialchars($_POST['name']), $username));
        } else {
            send_error(400, "noname");
        }
        if (trim($_POST['email']) != '' && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $emailExist = cavern_query_result("SELECT * FROM `user` WHERE NOT `username`='%s' AND `email`='%s'", array($username, $_POST["email"]));
            if ($emailExist['num_rows'] == 0) {
                $SQL->query("UPDATE `user` SET `email`='%s' WHERE `username`='%s'", array($_POST['email'], $username));
            } else {
                send_error(400, "emailused");
            }
        } else {
            send_error(400, "noemail");
        }

        if (isset($_POST["muted"])) {
            $muted = 1;
        } else {
            $muted = 0;
        }

        $level = intval($_POST['role']);
        if ($level > 9) {
            $level = 9;
        } else if ($level < 0) {
            $level = 0;
        }
        // you cannot promote user to level higher than youself
        if ($level > $user->level) {
            send_error(403, "lowlevel");
        }

        $SQL->query("UPDATE `user` SET `muted`='%d', `level`='%d' WHERE `username`='%s'", array($muted, $level, $username));

        header("Content-Type: application/json");
        echo json_encode(array("status" => TRUE, "modified" => $username));
        exit;
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET") {
    // fetch user list (we can fetch single user data from ajax)
    $user_list = array();

    $user_query = cavern_query_result("SELECT * FROM `user`", array());
    if ($user_query['num_rows'] > 0) {
        do {
            $data = $user_query['row'];

            $user_list[] = array(
                "id" => intval($data['id']),
                "username" => $data['username'],
                "name" => $data['name'],
                "email" => $data['email'],
                "level" => intval($data['level']),
                "role" => cavern_level_to_role($data['level']),
                "muted" => (($data["muted"] == 1) ? TRUE : FALSE)
            );
        } while ($user_query['row'] = $user_query['query']->fetch_assoc());
    }
    header('Content-Type: application/json');
    echo json_encode(array('status' => TRUE, "time" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000), "list" => $user_list));
    exit;
} else if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    // delete user account
    $username = trim($_GET['username']);

    try {
        $target_user = new User($username);
    } catch (NoUserException $e) {
        send_error(404, "nouser");
    }

    // you cannot delete site owner
    if ($target_user->level === 9) {
        send_error(403, "deleteowner");
    }

    /* cleanup user data */
    // Although we set foreign key, in fact `ON CASCADE` cannot fire trigger
    // like cleanup
    $SQL->query("DELETE FROM `like` WHERE `username`='%s'", array($target_user->username));
    // comment cleanup
    $SQL->query("DELETE FROM `comment` WHERE `username`='%s'", array($target_user->username));

    // now we can delete the user data
    $SQL->query("DELETE FROM `user` WHERE `username`='%s'", array($target_user->username));

    header('Content-Type: application/json');
    echo json_encode(array('status' => TRUE, "time" => round($_SERVER["REQUEST_TIME_FLOAT"] * 1000), "deleted" => $username));
    exit;
}

function send_error($code, $message) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array('status' => $message));
    exit;
}
