<?php
require_once('connection/SQL.php');
require_once('include/user.php');
require_once('config.php');

$user = validate_user();

if(!$user->islogin){ ?>
    <div class="ts basic center aligned padded segment">
        登入或是<a href="account.php?new">註冊</a>
    </div>
<?php } else { ?>
    <a class="ts center aligned big header" data-username="<?= $user->username ?>" href="user.php?username=<?= $user->username ?>">
        <img class="ts circular avatar image" src="https://www.gravatar.com/avatar/<?= md5(strtolower($user->email)) ?>?d=https%3A%2F%2Ftocas-ui.com%2Fassets%2Fimg%2F5e5e3a6.png&s=150"> <?= $user->name ?>
<?php if ($user->muted) { ?>
        <div class="negative sub header">
            <i class="ban icon"></i>你已被禁言!
        </div>
<?php } ?>
    </a>
<?php } ?>

<div class="ts fluid icon input">
    <input type="text" placeholder="在這搜尋人、事、物">
    <i class="inverted circular search link icon"></i>
</div>
<!-- Segment 1 -->
<div class="ts tertiary top attached center aligned segment">名稱</div>
<div class="ts bottom attached segment">
    <p>項目</p>
    <p>項目</p>
    <p>項目</p>
</div>
<!-- Segment 2 -->
<div class="ts tertiary top attached center aligned segment">名稱</div>
<div class="ts bottom attached segment">
    <p>項目</p>
    <p>項目</p>
    <p>項目</p>
</div>