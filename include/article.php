<?php
class NoPostException extends Exception {}

class Article {
    private $pid;
    private $author;
    private $name;
    private $title;
    private $content;
    private $time;
    private $likes_count;
    private $comments_count;
    private $islike = false;

    public function __construct($data) {
        if (is_int($data)) {
            $this->pid = $data;
            $query = cavern_query_result("SELECT `post`.*, `user`.name FROM `post` INNER JOIN `user` ON `post`.username = `user`.username WHERE `pid`=%d", array($this->pid));
            if ($query['num_rows'] > 0) {
                $result = $query['row'];
            } else {
                // post doesn't exist
                throw new NoPostException('There is no post with pid '.$this->pid);
            }
        } else if (is_array($data)) {
            /* pass the sql result directly */
            $result = $data;
            $this->pid = $result['pid'];
        }

        if (isset($result['name'])) $this->name = $result['name']; else $this->name = "";
        $this->author = $result['username'];
        $this->title = $result['title'];
        $this->content = $result['content'];
        $this->time = $result['time'];
        $this->likes_count = $result['like'];
        $this->comments_count = $result['comment'];
    }

    public function __get($name) {
        return $this->$name;
    }

    public function is_like(User $user) {
        if ($this->likes_count > 0 && $user->islogin) {
            $like_query = cavern_query_result("SELECT * FROM `like` WHERE `pid`='%d' AND `username`='%s'", array($this->pid, $_SESSION['cavern_username']));
            if ($like_query['num_rows'] > 0) {
                $this->islike = true;
            }
        }

        return $this->islike;
    }

    public function modify(User $user, $name, $value) {
        // article author and admin can edit post
        if ($user->islogin && ($user->username === $this->author || $user->level >= 8)) {
            $this->$name = $value;
        } else {
            return false;
        }
    }

    public function save() {
        global $SQL;
        $SQL->query("UPDATE `post` SET `title`='%s', `content`='%s' WHERE `pid`='%d' AND `username`='%s'", array(htmlspecialchars($_POST['title']), htmlspecialchars($_POST['content']), $this->pid, $this->author));
    }
}

function article_list($query_result) {
    $article_list = array();

    if ($query_result['num_rows'] > 0) {
        do {
            $article_list[] = new Article($query_result['row']);
        } while ($query_result['row'] = $query_result['query']->fetch_assoc());
    }

    return $article_list;
}