<?php
set_include_path('../include/');
$includepath = TRUE;
require_once('../connection/SQL.php');
require_once('../config.php');
require_once('user.php');
require_once('article.php');

$data = array("fetch" => round($_SERVER['REQUEST_TIME_FLOAT'] * 1000));

$user = validate_user();
if (!$user->valid) {
    $data["status"] = "invalid";
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_GET['pid'])) {
    // get data of single post
    $pid = abs($_GET['pid']);
    try {
        $article = new Article($pid);
    } catch (NoPostException $e) {
        // post not found
        http_response_code(404);
        $data['message'] = $e->getMessage();
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    $post = array(
        'author' => $article->author,
        'name' => $article->name,
        'title' => $article->title,
        'content' => $article->content,
        'time' => $article->time,
        'likes_count' => $article->likes_count,
        'comments_count' => $article->comments_count,
        'islike' => $article->is_like($user)
    );

    $data['post'] = $post;
} else {
    // get posts list
    if (isset($_GET['limit']) && trim($_GET['limit']) != ""){
        $limit = intval($_GET['limit']);
    } else {
        $limit = intval($blog['limit']);
    }

    if (isset($_GET['page']) && trim($_GET['page']) != "") {
        $page = intval($_GET['page']);
        $limit_start = abs(($page - 1) * $limit);
    } else if (isset($_GET['username']) && trim($_GET['username']) != "") {
        $mode = "username";
    } else {
        $page = 1;
        $limit_start = 0;
    }

    if (isset($mode) && $mode == "username") {
        $post_list = article_list(cavern_query_result(
            "SELECT `post`.*, `user`.name FROM `post` INNER JOIN `user` ON `post`.username = `user`.username WHERE `post`.username = '%s' ORDER BY `time`",
            array($_GET['username'])
        ));
        $all_posts_count = sizeOf($post_list);
    } else {
        $post_list = article_list(cavern_query_result(
            "SELECT `post`.*, `user`.name FROM `post` INNER JOIN `user` ON `post`.username = `user`.username ORDER BY `time` DESC LIMIT %d,%d",
            array($limit_start, $limit)
        ));
        $all_posts_count = cavern_query_result("SELECT COUNT(*) AS `count` FROM `post`")['row']['count'];
        
        $data['page_limit'] = $limit;
        $data['page'] = $page;
    }
    
    $data['all_posts_count'] = intval($all_posts_count);
    
    $posts = array();

    foreach ($post_list as $_key => $article) {
        $post = array(
            'author' => $article->author,
            'name' => $article->name,
            'pid' => intval($article->pid),
            'title' => $article->title,
            'time' => $article->time,
            'likes_count' => $article->likes_count,
            'comments_count' => $article->comments_count,
            'islike' => $article->is_like($user)
        );

        $posts[] = $post; // append post
    }

    $data["posts"] = $posts;
}

header('Content-Type: application/json');
echo json_encode($data);
exit;
?>