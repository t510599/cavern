<?php
require_once('connection/SQL.php');
require_once('config.php');
require_once('include/view.php');
require_once('include/user.php');
require_once('include/article.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Location: index.php?err=account");
    exit;
}

$all_posts_count = cavern_query_result("SELECT COUNT(*) AS `count` FROM `post`")['row']['count'];

if (isset($_GET['page']) && trim($_GET['page']) != "") {
    $limit_start = abs((intval($_GET['page']) - 1) * $blog['limit']);
    if ($limit_start > $all_posts_count) { // we don't have that much posts
        header('Location: index.php');
        exit;
    }
} else {
    $limit_start = 0;
}

$post_list = article_list(cavern_query_result(
    "SELECT `post`.*, `user`.name FROM `post` INNER JOIN `user` ON `post`.username = `user`.username ORDER BY `time` DESC LIMIT %d,%d",
    array($limit_start, $blog['limit']))
);

if ($user->islogin) {
    $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], "首頁");
    $view->add_script_source("ts('.ts.dropdown:not(.basic)').dropdown();");
} else {
    $view = new View('theme/default.html', 'theme/nav/default.html', 'theme/sidebar.php', $blog['name'], "首頁");
}

$view->add_script("https://unpkg.com/load-js@1.2.0");
$view->add_script("./include/js/lib/editormd.js");
$view->add_script("./include/js/security.js");
$view->add_script('./include/js/markdown.js');
$view->add_script('./include/js/cards.js');
$view->add_script('./include/js/like.js');

// ok message
if (isset($_GET['ok'])) {
    switch ($_GET['ok']) {
        case 'login':
            if ($user->islogin) {
                // only show welcome message if user is logged in
                $greeting = cavern_greeting();
                $view->show_message('inverted positive', "{$greeting}!我的朋友!");
            }
            break;
        case 'reg':
            $view->show_message('inverted primary', '註冊成功');
            break;
        case 'logout':
            if (!$user->islogin) {
                // only show message if user is logged out
                $view->show_message('inverted info', '已登出');
            }
            break;
    }
}

// error message
if (isset($_GET['err'])) {
    switch ($_GET['err']) {
        case 'account':
            $view->show_message('inverted negative', '帳號不存在');
            break;
        case 'login':
            $view->show_message('inverted negative', '帳號或密碼錯誤');
            break;
        case 'permission':
            $view->show_message('warning', '帳號權限不足');
            break;
        case 'post':
            $view->show_message('negative', '找不到文章');
            break;
        case 'nologin':
            $view->show_message('warning', '請先登入');
            break;
    }
}

if (sizeOf($post_list) > 0) { ?>
<div class="ts active big text loader">載入中</div>
<div class="ts loading flatted borderless centered segment" id="cards">
    <?php
    foreach ($post_list as $_key => $article) {
?>

<div class="ts card" data-id="<?= $article->pid ?>">
    <div class="content">
        <div class="actions">
            <div class="ts secondary buttons">
                <button class="ts icon like button" data-id="<?= $article->pid ?>">
                    <i class="thumbs <?php if (!$article->is_like($user)) { echo "outline"; }?> up icon"></i> <?= $article->likes_count ?>
                </button>
                <a class="ts icon button" href="post.php?pid=<?= $article->pid ?>">
                    Read <i class="right arrow icon"></i>
                </a>
            </div>
        </div>
        <div class="header"><?= $article->title ?></div>
            <div class="middoted meta">
                <a href="user.php?username=<?= $article->author ?>"><?= $article->name ?></a>
                <span><?= date('Y-m-d', strtotime($article->time)) ?></span>
            </div>
        <div class="description" id="markdown-post-<?= $article->pid ?>">
            <div class="markdown">
<?= sumarize($article->content, 5) ?>
            </div>
        </div>
    </div>
    <div class="secondary right aligned extra content">
        <i class="discussions icon"></i> <?= $article->comments_count ?> 則留言
    </div>
</div>

<?php } ?>
</div>
    <?php echo cavern_pages(@$_GET['page'], $all_posts_count, $blog['limit']);
} else {
    $view->show_message('inverted info', '沒有文章，趕快去新增一個吧!');
}

$view->render();
?>
