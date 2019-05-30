<?php
require_once('include/security.php');
require_once('connection/SQL.php');
require_once('config.php');

if (isset($_SESSION['cavern_username'])) {
    if (isset($_GET['logout'])) {
        if (validate_csrf()) {
            cavern_logout();
            header('axios-location: index.php?ok=logout');
        } else {
            http_response_code(403);
            echo json_encode(array("status" => 'csrf'));
        }
    } else if (isset($_GET['next']) && $_GET['next'] == "admin") {
        header("Location: ./admin/");
    } else {
        header('Location: index.php');
    }
    exit;
}

if ((isset($_POST['username'])) && (isset($_POST['password'])) && ($_POST['username']!='') && ($_POST['password']!='')) {
    if (cavern_login($_POST['username'], $_POST['password']) == 1) {
        if (isset($_POST['next']) && trim($_POST['next']) == "admin") {
            header('Location: ./admin/');
        } else if ((isset($_POST['next']) && filter_var($_POST['next'], FILTER_VALIDATE_URL)) || isset($_SERVER['HTTP_REFERER'])) {
            // redirect to previous page before login
            $next = (isset($_POST['next']) ? $_POST['next'] : $_SERVER['HTTP_REFERER']); // users login directly from navbar
            $url_data = parse_url($next);

            $len = strlen("index.php");
            if (mb_substr($url_data['path'], -$len) === "index.php") {
                // the user was viewing the index page, so we just redirect him to index page
                header('Location: index.php?ok=login');
            } else {
                if (!isset($url_data['query'])) {
                    $url_data['query'] = "ok=login";
                } else if (!strpos($url_data['query'], "ok=login")) {
                    // for those already have url queries, such as 'post.php?pid=1'
                    $url_data['query'] .= "&ok=login";
                }
    
                $url = "{$url_data['path']}?{$url_data['query']}";
                header("Location: $url");
            }
        } else {
            // previous page doesn't exist, so we just redirect to default page
            header('Location: index.php?ok=login');
        }
    } else {
        header('Location: index.php?err=login');
    }
    exit;
} else { 
    $admin = (isset($_GET['next']) && trim($_GET['next']) == "admin");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/tocas-ui/2.3.3/tocas.css" rel='stylesheet'>
        <title>登入 | <?php echo $blog['name']; ?></title>
        <style type="text/css">
            html,body {
                min-height: 100%;
                margin: 0;
            }
            body {
                background: linear-gradient(180deg, deepskyblue 5%, aqua);
            }
            .ts.narrow.container {
                padding: 4em 0;
            }
            .segment {
                max-width: 300px;
            }
            /* admin style */
            body.admin {
                background: linear-gradient(0deg, #1CB5E0, #000046);
            }
            body.admin .ts.header, body.admin .ts.header .sub.header{
                color: white;
            }
            .inverted .ts.form .field > label {
                color: #EFEFEF;
            }
        </style>
    </head>
    <body <?= ($admin ? 'class="admin"' : "") ?>>
        <div class="ts narrow container">
            <h1 class="ts center aligned header">
                <?= $blog['name'] ?>
                <div class="sub header"><?= ($admin ? "安全門" : "傳送門") ?></div>
            </h1>
            <div class="ts centered <?= ($admin ? "inverted" : "secondary") ?> segment">
                <form class="ts form" method="POST" action="login.php">
                    <div class="field">
                        <label>帳號</label>
                        <input type="text" name="username">
                    </div>
                    <div class="field">
                        <label>密碼</label>
                        <input type="password" name="password">
                    </div>
                    <input type="hidden" name="next" value="<?= ($admin ? "admin" : @$_SERVER['HTTP_REFERER']); ?>">
                    <div class="ts separated vertical fluid buttons">
                        <input type="submit" class="ts positive button" value="登入">
                        <a href="account.php?new" class="ts button">註冊</a>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
<?php }
?>