<?php
require_once('connection/SQL.php');
require_once('config.php');
require_once('include/view.php');
require_once('include/user.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Location: index.php?err=account");
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

    $posts = cavern_query_result("SELECT * FROM `post` WHERE `username`='%s'", array($username));
    $posts_count = ($posts['num_rows'] > 0 ? $posts['num_rows'] : 0);

    if ($user->islogin) {
        $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], $target_user->name);
        $view->add_script_source("ts('.ts.dropdown').dropdown();");
    } else {
        $view = new View('theme/default.html', 'theme/nav/default.html', 'theme/sidebar.php', $blog['name'], $target_user->name);
    }
    $view->add_script("./include/js/security.js");

    if (isset($_GET['err'])) {
        if ($_GET['err'] == "no") {
            $view->show_message('negative', "找不到使用者");
            $view->render();
            exit;
        }
    }
?>
<div class="ts big dividing header"><?= $target_user->name ?> 的個人資料</div>
<div class="ts stackable grid">
    <div class="column">
        <div class="ts center aligned flatted borderless segment">
            <img src="https://www.gravatar.com/avatar/<?= md5(strtolower($target_user->email)) ?>?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=500" class="ts rounded image" id="avatar">
        </div>
    </div>
    <div class="stretched column">
        <div class="table wrapper">
            <table class="ts borderless three column table">
                <thead>
                    <tr>
                        <th colspan="2">基本資料</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>使用者名稱</td>
                        <td><?= $username ?></td>
                    </tr>
                    <tr>
                        <td>暱稱</td>
                        <td><?= $target_user->name ?></td>
                    </tr>
                    <tr>
                        <td>權限</td>
                        <td><?= cavern_level_to_role($target_user->level) ?></td>
                    </tr>
                </tbody>
            </table>
            <table class="ts borderless two column table">
                <thead>
                    <tr>
                        <th colspan="2">統計</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>文章數</td>
                        <td><?= $posts_count ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="right aligned"><a href="post.php?username=<?= $username ?>">看他的文章 <i class="hand outline right icon"></i></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $view->render();
} else {
    if (isset($_GET['err'])) {
        if ($user->islogin) {
            $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], "使用者");
            $view->add_script_source("ts('.ts.dropdown').dropdown();");
        } else {
            $view = new View('theme/default.html', 'theme/nav/default.html', 'theme/sidebar.php', $blog['name'], "使用者");
        }
        $view->add_script("./include/js/security.js");

        if ($_GET['err'] == "no") {
            $view->show_message('negative', "找不到使用者");
            $view->render();
            exit;
        }
    } else {
        header('Location: user.php?err=no');
        exit;
    }
}
?>
