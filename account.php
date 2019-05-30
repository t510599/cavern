<?php
require_once('connection/SQL.php');
require_once('config.php');
require_once('include/view.php');
require_once('include/security.php');
require_once('include/user.php');

$user = validate_user();
if (!$user->valid) {
    http_response_code(403);
    header("Location: index.php?err=account");
    exit;
}

if (isset($_POST['username']) && trim($_POST['username']) != "" && isset($_POST['password']) && isset($_POST['name']) && isset($_POST['email'])) {
    // create new account
    if (!$blog['register']) {
        http_response_code(403);
        header('axios-location: account.php');
        exit;
    }

    if (!validate_csrf()) {
        http_response_code(403);
        header('axios-location: account.php?new');
        exit;
    }

    $username = $_POST['username'];
    try {
        $target_user = new User($username);

        http_response_code(409); // 409 Conflict
        header('axios-location: account.php?new&err=used');
        exit;
    } catch (NoUserException $e) {
        if (preg_match('/^[a-z][a-z0-9\_\-]*$/', $username) && strlen($username) <= 20 && strlen($_POST['name']) <= 40 && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $SQL->query("INSERT INTO `user` (`username`, `pwd`, `name`, `email`) VALUES ('%s', '%s', '%s', '%s')", array($username, cavern_password_hash($_POST['password'], $username), htmlspecialchars($_POST['name']), $_POST['email']));
            header('axios-location: index.php?ok=reg');
        } else {
            http_response_code(400);
            header('axios-location: index.php?err=miss');
        }
        exit;
    }
} else if ($user->islogin && isset($_POST['username']) && isset($_POST['old']) && (isset($_POST['name']) || isset($_POST['new']))) {
    // modify account data
    if (!validate_csrf()) {
        http_response_code(403);
        header('axios-location: account.php');
        exit;
    }
    $username = $_POST['username'];
    if ($username !== $user->username) {
        // not the same person
        http_response_code(403);
        header('axios-location: account.php?err=edit');
        exit;
    } else {
        // confirm old password and mofify account data
        $original = cavern_query_result("SELECT * FROM `user` WHERE `username`='%s'", array($username));
        if (!hash_equals(cavern_password_hash($_POST['old'], $username), $original['row']['pwd']) || $original['num_rows'] == 0) {
            http_response_code(403);
            header('axios-location: account.php?err=old');
            exit;
        } else {
            if (trim($_POST['new']) != '') {
                $password = cavern_password_hash($_POST['new'], $username);
                $SQL->query("UPDATE `user` SET `pwd`='%s' WHERE `username`='%s'", array($password, $username));
            }
            if (trim($_POST['name']) != '' && strlen($_POST['name']) <= 40) {
                $SQL->query("UPDATE `user` SET `name`='%s' WHERE `username`='%s'", array(htmlspecialchars($_POST['name']), $username));
            } else {
                http_response_code(400);
                header('axios-location: account.php?err=miss');
                exit;
            }
            if (trim($_POST['email']) != '' && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $emailExist = cavern_query_result("SELECT * FROM `user` WHERE NOT `username`='%s' AND `email`='%s'", array($username, $_POST["email"]));
                if ($emailExist['num_rows'] == 0) {
                    $SQL->query("UPDATE `user` SET `email`='%s' WHERE `username`='%s'", array($_POST['email'], $username));
                } else {
                    http_response_code(400);
                    header('axios-location: account.php?err=used');
                    exit;
                }
            } else {
                http_response_code(400);
                header('axios-location: account.php?err=miss');
                exit;
            }
            header('axios-location: account.php?ok=edit');
            exit;
        }
    }
} else if (!$user->islogin && !isset($_GET['new'])) {
    // if mode isn't defined, redirect to register page
    header('Location: account.php?new');
    exit;
} else if ($user->islogin && isset($_GET['new'])) {
    // if someone is logged in, then redirect to account setting page
    header('Location: account.php');
    exit;
}

// create new account
if (isset($_GET['new'])) {
    $view = new View('theme/default.html', 'theme/nav/default.html', 'theme/sidebar.php', $blog['name'], "註冊");
    if (!$blog['register']) {
        $view->show_message('inverted negative', "抱歉，目前暫停註冊");
        $view->render();
        exit;
    }

    if (isset($_GET['err'])) {
        if ($_GET['err'] == "miss") {
            $view->show_message('inverted negative', "請正確填寫所有欄位");
        } else if ($_GET['err'] == "used") {
            $view->show_message('inverted negative', "此使用者名稱或是信箱已被使用"); 
        }
    }

    $view->add_script("./include/js/security.js");
    $view->add_script("./include/js/account.js");
?>
<form action="account.php" method="POST" name="newacc" autocomplete="off">
    <div class="ts form">
        <div class="ts big dividing header">註冊</div>
        <div class="required field">
            <label>帳號</label>
            <input required="required" name="username" maxlength="20" pattern="^[a-z][a-z0-9_-]*$" type="text">
            <small>上限20字元 (小寫英文、數字、底線以及連字號)。首字元必須為英文。</small>
            <small>你未來將無法更改這項設定。</small>
        </div>
        <div class="required field">
            <label>暱稱</label>
            <input required="required" name="name" maxlength="40" type="text">
            <small>上限40字元。</small>
        </div>
        <div class="required field">
            <label>密碼</label>
            <input required="required" name="password" type="password">
        </div>
        <div class="required field">
            <label>重複密碼</label>
            <input required="required" name="repeat" type="password">
        </div>
        <div class="required field">
            <label>信箱</label>
            <input required="required" name="email" type="email">
            <small>用於辨識頭貼。（Powered by <a href="https://en.gravatar.com/" target="_blank">Gravatar</a>）</small>
        </div>
        <input class="ts right floated primary button" value="送出" type="submit">
    </div>
</form>
<?php
    $view->render();
} else {
// edit account data
    $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], "帳號");
    $view->add_script_source("ts('.ts.dropdown').dropdown();");
    $view->add_script("./include/js/security.js");
    $view->add_script("./include/js/account.js");

    if (isset($_GET['err'])) {
        switch ($_GET['err']) {
            case 'edit':
                $view->show_message('inverted negative', "修改失敗");
                break;
            case 'old':
                $view->show_message('inverted negative', "舊密碼錯誤");
                break;
            case "miss":
                $view->show_message('inverted negative', "請正確填寫所有欄位");
                break;
            case "used":
                $view->show_message('inverted negative', "此信箱已被其他帳號使用");
                break;
        }
    }
    if (isset($_GET['ok'])) {
        if ($_GET['ok'] == "edit") {
            $view->show_message('inverted positive', "修改成功!");
        }
    }
?>
<form action="account.php" method="POST" name="editacc">
    <div class="ts form">
        <div class="ts big dividing header">編輯帳號</div>
        <div class="fields">
            <div class="six wide field">
                <label>頭貼</label>
                <div class="ts center aligned flatted borderless segment">
                    <img src="https://www.gravatar.com/avatar/<?= md5(strtolower($user->email)) ?>?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=500" class="ts rounded image" id="avatar">
                </div>
                <div data-tooltip="請透過電子信箱更換頭貼" data-tooltip-position="bottom right" class="ts top right attached label avatar tooltip">?</div>
            </div>
            <div class="ten wide field">
                <div class="disabled field">
                    <label>帳號</label>
                    <input type="text" name="username" value="<?= $user->username ?>">
                </div>
                <div class="required field">
                    <label>暱稱</label>
                    <input type="text" required="required" name="name" maxlength="40" value="<?= $user->name ?>">
                    <small>上限40字元。</small>
                </div>
            </div>
        </div>
        <div class="required field">
            <label>信箱</label>
            <input type="email" required="required" name="email" value="<?= $user->email ?>">
            <small>透過電子信箱，在 <a href="https://en.gravatar.com/" target="_blank">Gravatar</a> 更改你的頭貼。</small>
        </div>
        <div class="required field">
            <label>舊密碼</label>
            <input type="password" required="required" name="old">
        </div>
        <div class="field">
            <label>新密碼</label>
            <input type="password" name="new">
            <small>留空則不修改。</small>
        </div>
        <div class="field">
            <label>重複密碼</label>
            <input name="repeat" type="password">
            <small>重複新密碼。</small>
        </div>
        <input type="submit" class="ts right floated primary button" value="送出">
    </div>
</form>
<?php $view->render();
}
?>