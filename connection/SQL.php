<?php
if(!@$includepath){
    set_include_path('include/');
}

error_reporting(E_ALL);
require_once('db.php');

$database_SQL = ""; // 資料庫名稱
$username_SQL = ""; // 連線帳號
$password_SQL = ""; // 連線密碼
$hostname_SQL = ""; // MySQL伺服器

global $SQL;
$SQL = new Database($hostname_SQL,$username_SQL,$password_SQL,$database_SQL);
$SQL->query("SET NAMES 'utf8mb4'");
