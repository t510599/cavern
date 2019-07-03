<?php
function cavern_notify_user($username, $message="你有新的通知!", $url="", $type="") {
    global $SQL;
    try {
        // do not notify the user that doesn't exist
        $user = new User($username);
    } catch (NoUserException $e) {
        return $e;
    }
    $time = date('Y-m-d H:i:s');
    $SQL->query("INSERT INTO `notification` (`username`, `message`, `url`, `type`, `time`) VALUES ('%s', '%s', '%s', '%s', '%s')", array($username, $message, $url, $type, $time));
}

function parse_user_tag($markdown) {
    $regex = array(
        "code_block" => "/(`{1,3}[^`]*`{1,3})/",
        "email" => "/[^@\s]*@[^@\s]*\.[^@\s]*/",
        "url" => "/https?\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]+(\/\S*)/",
        "username" => "/@(\w+)/"
    );

    $tmp = preg_replace($regex["code_block"], " ", $markdown);
    $tmp = preg_replace($regex['url'], " ", $tmp);
    $tmp = preg_replace($regex["email"], " ", $tmp);

    preg_match_all($regex["username"], $tmp, $username_list);

    return array_unique($username_list[1]);
}