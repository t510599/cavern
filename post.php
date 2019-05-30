<?php
require_once('connection/SQL.php');
require_once('config.php');

set_include_path('include/');
require_once('view.php');
require_once('user.php');
require_once('article.php');
require_once('security.php');
require_once('notification.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Location: ../index.php?err=account");
    exit;
}

if ($user->islogin && isset($_POST['pid']) && isset($_POST['title']) && isset($_POST['content'])) {
    if ($user->level < 1 || $user->muted == 1) {
        http_response_code(403);
        header('axios-location: post.php?err=level');
        exit;
    }

    if (!validate_csrf()) {
        http_response_code(403);
        header('axios-location: post.php?err=level');
    }

    if ($_POST['pid'] == "-1") {
        // new post
        if (trim($_POST['content']) == "") {
            http_response_code(400);
            header('axios-location: post.php?err=empty');
            exit;
        }

        if (trim($_POST['title']) == "") {
            $_POST['title'] = "(無標題)";
        }
        
        $current = date('Y-m-d H:i:s');
        $SQL->query("INSERT INTO `post` (`title`, `content`, `time`, `username`) VALUES ('%s', '%s', '%s', '%s')", array(htmlspecialchars($_POST['title']), htmlspecialchars($_POST['content']), $current, $user->username));
        $pid = $SQL->insert_id();

        // notify tagged user
        // the user who tag himself is unnecessary to notify
        $username_list = parse_user_tag($_POST['content']);
        foreach ($username_list as $key => $id) {
            if ($id == $user->username) continue;
            cavern_notify_user($id, "{{$user->name}}@{$user->username} 在 [{$_POST['title']}] 中提到了你", "post.php?pid=$pid");
        }

        http_response_code(201); // 201 Created
        header('axios-location: post.php?pid='.$pid);
        exit;
    } else {
        // edit old post
        $pid = abs($_POST['pid']);

        try {
            $post = new Article($pid);
        } catch (NoPostException $e) {
            // post not found
            http_response_code(404);
            header("axios-location: index.php?err=post");
            exit;
        }

        if ($post->author !== $user->username && $user->level < 8) {
            http_response_code(403);
            header('axios-location: post.php?err=edit');
            exit;
        }

        if (trim($_POST['content']) == "") {
            http_response_code(400);
            header('axios-location: post.php?err=empty');
            exit;
        }

        if (trim($_POST['title']) == "") {
            $_POST['title'] = "(無標題)";
        }
        
        $post->modify($user, "title", htmlspecialchars($_POST['title']));
        $post->modify($user, "content", htmlspecialchars($_POST['content']));

        $post->save();
        header('axios-location: post.php?pid='.$_POST['pid']);
        exit;
    }
}

if ($user->islogin && isset($_GET['del']) && trim($_GET['del']) != '') {
    if (!validate_csrf()) {
        http_response_code(403);
        header('axios-location: post.php?err=level');
        exit;
    }

    try {
        $post = new Article(intval($_GET['del']));
    } catch (NoPostException $e) {
        http_response_code(404);
        header('axios-location: index.php?err=post');
        exit;
    }

    if ($post->author !== $user->username && $user->level < 8) {
        http_response_code(403);
        header('axios-location: post.php?err=del');
        exit;
    } else {
        $SQL->query("DELETE FROM `post` WHERE `pid`='%d' AND `username` = '%s'", array($_GET['del'], $_SESSION['cavern_username']));
        http_response_code(204);
        header('axios-location: post.php?ok=del');
        exit;
    }
} else if (!$user->islogin && isset($_GET['del'])) {
    http_response_code(204);
    header('axios-location: index.php?err=nologin');
    exit;
}

// View
if (isset($_GET['pid'])) {
    $pid = abs($_GET['pid']);

    try {
        $post = new Article($pid);
    } catch (NoPostException $e) {
        http_response_code(404);
        header('Location: index.php?err=post');
        exit;
    }

    if ($user->islogin) {
        $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], $post->title);
        $view->add_script_source("ts('.ts.dropdown:not(.basic)').dropdown();");
        $owner_view = ($post->author === $user->username);
    } else {
        $view = new View('theme/default.html', 'theme/nav/default.html', 'theme/sidebar.php', $blog['name'], $post->title);
        $owner_view = FALSE;
    }

    $view->add_script("https://unpkg.com/load-js@1.2.0");
    $view->add_script("./include/js/lib/editormd.js");
    $view->add_script("./include/js/lib/css.min.js");
    $view->add_script("./include/js/security.js");
    $view->add_script("./include/js/markdown.js");
    $view->add_script("./include/js/comment.js");
    $view->add_script("./include/js/post.js");
    $view->add_script("./include/js/like.js");
    $view->add_script_source("ts('.ts.tabbed.menu .item').tab();");

    if (isset($_GET['ok'])) {
        if ($_GET['ok'] == "login" && $user->islogin) {
            $greeting = cavern_greeting();
            $view->show_message("inverted positive", "{$greeting}!我的朋友!");
        }
    }
    
    ?>
    <div class="ts<?php echo ($owner_view ? " stackable " : " "); ?>grid">
        <div class="stretched column" id="header">
            <h2 class="ts header">
            <?= $post->title ?>
                <div class="sub header"><a href="user.php?username=<?= $post->author ?>"><?= $post->name ?></a></div>
            </h2>
        </div>
        <div class="action column">
            <div class="ts secondary icon buttons">
                <button class="ts secondary icon like button" data-id="<?= $pid ?>">
                    <i class="thumbs <?php if (!$post->is_like($user)) {echo "outline";}?> up icon"></i> <?= $post->likes_count ?>
                </button>
                <?php
                if ($owner_view) { ?>
                <a class="ts secondary icon button" href="post.php?edit=<?= $pid ?>">
                    <i class="edit icon"></i>
                </a>
                <a class="ts secondary icon delete button" href="post.php?del=<?= $pid ?>">
                    <i class="trash icon"></i>
                </a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="ts segments">
        <div class="ts flatted segment" id="post" data-id="<?= $pid ?>">
            <div class="markdown"><?= $post->content ?></div>
        </div>
        <div class="ts right aligned tertiary segment">
            <i class="clock icon"></i><?= $post->time ?>
        </div>
    </div>
    <div class="comment header">
        <div class="ts grid">
            <div class="stretched header column">
                <div class="ts big header">留言</div>
            </div>
            <div class="column">
                <span class="fetch time">Last fetch: --:--</span>&nbsp;
                <div class="ts active inline loader"></div>
                <button class="ts fetch icon button">
                    <i class="refresh icon"></i>
                </button>
            </div>
        </div>
        <div class="ts comment divider"></div>
    </div>
    <div class="ts comments">
        <div class="ts borderless flatted no-comment segment">現在還沒有留言！</div>
    </div>
    <div class="ts segments" id="comment">
        <div class="ts fitted secondary segment">
            <div class="ts tabbed menu">
                <a class="active item" data-tab="textarea">Write</a>
                <a class="item" data-tab="preview">Preview</a>
            </div>
        </div>
        <div class="ts clearing active tab segment" data-tab="textarea">
        <?php if ($user->islogin) {
                if ($user->muted) {
                    $disabled = " disabled";
                    $placeholder = "你被禁言了。";
                    $button_text = "你被禁言了";
                } else {
                    $disabled = "";
                    $placeholder = "留言，然後開戰。";
                    $button_text = "留言";
                }
            } else {
                $disabled = " disabled";
                $placeholder = "請先登入";
                $button_text = "留言";
            } ?>
            <div class="ts<?= $disabled ?> fluid input">
                <textarea placeholder="<?= $placeholder ?>" rows="5" autocomplete="off"<?= $disabled ?>></textarea>
            </div>
            <div class="ts<?= $disabled ?> right floated separated action buttons">
                <button class="ts positive submit button"><?= $button_text ?></button>
            </div>
        </div>
        <div class="ts tab segment" id="preview" data-tab="preview"></div>
    </div>
    <?php $view->render();

} else if (isset($_GET['new']) || isset($_GET['edit'])) {
    // New or Edit
    if (!$user->islogin) {
        header('Location: index.php?err=nologin');
        exit;
    }

    if ($user->level < 1 || $user->muted) {
        header('Location: post.php?err=level');
        exit;
    }

    if (isset($_GET['new'])) {
        $mode = "new";

        $pid = -1;
        $title = "";
        $content = "";
    } else if (isset($_GET['edit'])) {
        $mode = "edit";

        $pid = abs($_GET['edit']);
        try {
            $post = new Article($pid);
        } catch (NoPostException $e) {
            http_response_code(404);
            header('Location: index.php?err=post');
            exit;
        }

        if ($post->author != $user->username) {
            http_response_code(403);
            header('Location: post.php?err=edit');
            exit;
        }
    }

    $title = $post->title;
    $content = $post->content;

    $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], ($title == "" ? "文章" : $title));

    $view->add_script_source("ts('.ts.dropdown:not(.basic)').dropdown();");
    $view->add_script("./include/js/lib/editormd.js");
    $view->add_script("./include/js/lib/zh-tw.js");
    $view->add_script("./include/js/lib/css.min.js");
    $view->add_script("./include/js/security.js");
    $view->add_script("./include/js/edit.js");
?>
    <form action="post.php" method="POST" name="edit" id="edit" autocomplete="off"> <!-- prevent Firefox from autocompleting -->
        <div class="ts stackable grid">
            <div class="stretched column">
                <div class="ts huge fluid underlined input">
                    <input placeholder="標題" name="title" value="<?= $title ?>">
                </div>
            </div>
            <div class="action column">
                <div class="ts buttons">
                    <button class="ts positive button">發布</button>
                    <?php if ($mode == "edit") { ?>
                    <a href="post.php?del=<?= $pid ?>" class="ts negative delete button">刪除</a>
                    <?php } ?>
                    <a href="index.php" class="ts button">取消</a>
                </div>
            </div>
        </div>
        <div id="markdownEditor">
            <textarea id="markdown" name="content" stlye="display: none;" autocomplete="off" autocorrect="off" spellcheck="false"><?= $content ?></textarea>
        </div>
        <input type="hidden" name="pid" id="pid" value="<?= $pid ?>">
    </form>
    <?php $view->render();

} else {
// List all
    if (!$user->islogin && (!isset($_GET['username']) || trim($_GET['username']) == "")) {
        http_response_code(403);
        header('Location: index.php?err=nologin');
        exit;
    }

    if (isset($_GET['username']) && trim($_GET['username']) != "") {
        $username = trim($_GET['username']);

        try {
            $target_user = new User($username);
        } catch (NoUserException $e) {
            http_response_code(404);
            header('Location: user.php?err=no');
            exit;
        }

        $post_list = article_list(cavern_query_result(
            "SELECT * FROM `post` WHERE `username`='%s' ORDER BY `time`",
            array($username))
        );
    } else if ($user->islogin) {
        $username = $user->username;
        $post_list = article_list(cavern_query_result(
            "SELECT * FROM `post` WHERE `username`='%s' ORDER BY `time`",
            array($username))
        );
    }

    $owner_view =  ($user->islogin && $username === $user->username);

    if ($user->islogin) {
        $view = new View('theme/default.html','theme/nav/util.php', 'theme/sidebar.php', $blog['name'], "文章");
        $view->add_script_source("$('tbody').on('click', 'a.negative.button', function(e) {
            e.preventDefault();
            let el = e.currentTarget;
            let href = el.getAttribute('href');
            swal({
                type: 'question',
                title: '確定要刪除嗎?',
                showCancelButton: true,
                confirmButtonText: '確定',
                cancelButtonText: '取消',
            }).then((result) => {
                if (result.value) { // confirm
                    axios.request({
                        method: 'GET',
                        maxRedirects: 0,
                        url: href
                    }).then(function (res) {
                        location.href = res.headers['axios-location'];
                    });
                }
            });
        });");
    } else {
        $view = new View('theme/default.html','theme/nav/default.html', 'theme/sidebar.php', $blog['name'], "文章");
    }

    $view->add_script("./include/js/security.js");
    $view->add_script_source("ts('.ts.dropdown').dropdown();\nts('.ts.sortable.table').tablesort();");

    if (isset($_GET['ok'])) {
        if ($_GET['ok'] == "del") {
            $view->show_message("inverted positive", "刪除成功");
        } else if ($_GET['ok'] == "login" && $user->islogin) {
            $greeting = cavern_greeting();
            $view->show_message("inverted positive", "{$greeting}!我的朋友!");
        }
    }
    if (isset($_GET['err'])) {
        switch ($_GET['err']) {
            case 'del':
                $view->show_message("inverted negative", "刪除失敗");
                break;
            case 'edit':
                $view->show_message("inverted negative", "編輯失敗");
                break;
            case 'empty':
                $view->show_message("warning", "文章內容不能為空！");
                break;
            case 'level':
                $view->show_message("inverted negative", "你沒有權限發文!");
                break;
        }
    }
?>
<div class="ts big dividing header">
    文章
    <?php if (!$owner_view) { // List other one's post ?>
    <div class="sub header"><a href="user.php?username=<?= $username ?>"><?= $target_user->name ?></a></div>
    <?php } ?>
</div>
<div class="table wrapper">
    <table class="ts sortable celled striped table">
        <thead>
            <tr>
                <th>標題</th>
                <th>讚</th>
                <th>留言</th>
                <th>日期</th>
                <?php if ($owner_view) { // Only owner could manage post ?>
                <th>管理</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
        <?php
        if (sizeof($post_list) > 0) {
            foreach ($post_list as $key => $article) {                
        ?>
            <tr>
                <td><a href="post.php?pid=<?= $article->pid ?>"><?= $article->title ?></a></td>
                <td class="center aligned collapsing"><?= $article->likes_count ?></td>
                <td class="center aligned collapsing"><?= $article->comments_count ?></td>
                <td class="collapsing"><?= $article->time ?></td>
                <?php if ($owner_view) { // Only owner could manage post ?>
                <td class="right aligned collapsing">
                    <a class="ts circular icon button" href="post.php?edit=<?= $article->pid ?>"><i class="pencil icon"></i></a>
                    <a class="ts negative circular icon button" href="post.php?del=<?= $article->pid ?>"><i class="trash icon"></i></a>
                </td>
                <?php } ?>
            </tr>
                <?php }
        } else { ?>
            <tr>
                <td colspan="<?php echo ($owner_view ? 5 : 4); ?>">沒有文章</td>
            <tr>
        <?php } ?>
        </tbody>
    </table>
</div>
    <?php
    $view->render();
}
?>