<?php
require_once('connection/SQL.php');
require_once('config.php');
require_once('include/view.php');

if (isset($_SESSION['cavern_username'])) {
    $view = new View('theme/default.html', 'theme/nav/util.php', 'theme/sidebar.php', $blog['name'], "通知");
    $view->add_script_source("ts('.ts.dropdown:not(.basic)').dropdown();");
    $view->add_script("./include/js/security.js");
    
    $notice_list = cavern_query_result("SELECT * FROM `notification` WHERE `username` = '%s' ORDER BY `time` DESC", array($_SESSION['cavern_username']));
    
    if ($notice_list['num_rows'] > 0) {
        $regex = array(
            "username" => "/\{([^\{\}]+)\}@(\w+)/",
            "url" => "/\[([^\[\[]*)\]/"
        );
?>
<div class="ts big dividing header">通知 <span class="notification description">#僅顯示最近 100 則通知</span></div>
<div class="table wrapper">
    <table class="ts sortable celled striped table">
        <thead>
            <tr>
                <th>內容</th>
                <th>日期</th>
            </tr>
        </thead>
        <tbody>
<?php
        do {
            $message = $notice_list['row']['message'];
            $time = $notice_list['row']['time'];
            $url = $notice_list['row']['url'];

            $message = preg_replace($regex["username"], '<a href="user.php?username=$2">$1</a>', $message);
            $message = preg_replace($regex["url"], "<a href=\"${url}\">$1</a>", $message);
?>
            <tr>
                <td><?= $message ?></td>
                <td class="collapsing"><?= $time ?></td>
            </tr>
<?php } while ($notice_list['row'] = $notice_list['query']->fetch_assoc()); ?>
        </tbody>
    </table>
</div>
    <?php 
    } else {
        $view->show_message('inverted info', '目前沒有通知。');
    }

    $view->render();
} else {
    http_response_code(204);
    header('Location: index.php?err=nologin');
    exit;
}
?>