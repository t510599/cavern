<?php
require_once('include/function.php');
if(!session_id()) {
    session_start();
}

global $blog;

date_default_timezone_set("Asia/Taipei");

$blog['name'] = 'Cavern'; //網站名稱
$blog['limit'] = 10; //首頁顯示文章數量
$blog['register'] = true; //是否允許註冊
?>
