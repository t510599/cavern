<?php
class NoUserException extends Exception {}

class User {
    private $valid = false;
    private $islogin = false;

    private $username;
    private $name;
    private $level;
    private $muted;
    private $email;

    public function __construct($username="") {
        // if $username is empty indicates that user is not logged in
        if ($username !== "") {
            // the user might be banned or removed, so we validate him here
            $query = cavern_query_result("SELECT * FROM `user` WHERE `username` = '%s'", array($username));

            if ($query['num_rows'] > 0){
                $this->valid = true;
                
                $data = $query['row'];
                $this->username = $data["username"];
                $this->name = $data['name'];
                $this->level = $data['level'];
                $this->muted = ($data['muted'] == 1 ? true : false);
                $this->email = $data['email'];
            } else if ($username != ""){
                throw new NoUserException($username);
            }

            if ($this->username === @$_SESSION["cavern_username"]) {
                $this->islogin = true;
            }
        } else {
            // even though the user hasn't logged in, he is still a valid user
            $this->username = "";
            $this->valid = true;
        }
    }

    public function __get($name) {
        return $this->$name;
    }
}

function validate_user() {
    if (isset($_SESSION['cavern_username'])) {
        $username = $_SESSION['cavern_username'];
    } else {
        $username = "";
    }

    try {
        $user = new User($username);
    } catch (NoUserException $e) {}

    if (!$user->valid) {
        session_destroy();
    }
    return $user;
}

?>