<?php
require_once('../config.php');
require_once('../connection/SQL.php');
require_once('../include/user.php');

$user = validate_user();
?>
<div class="center aligned item">
    <img class="ts tiny circular image" src="https://www.gravatar.com/avatar/<?= md5($user->email) ?>?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=150">
    <br><br>
    <div><?= $user->name ?></div>
</div>