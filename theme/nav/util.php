<?php
$self = @end(explode('/',$_SERVER['PHP_SELF']));
?>
<div class="ts top attached pointing secondary large menu" id="menu">
    <div class="ts narrow container">
        <a href="index.php" class="<?php if ($self == 'index.php') { echo "active "; } ?>item">首頁</a>
        <div class="ts <?php if ($self == 'post.php') { echo "active "; } ?>dropdown item">
            <div class="text">文章</div>
            <div class="menu">
                <a href="post.php?new" class="item">新增</a>
                <a href="post.php" class="item">列表</a>
            </div>
        </div>
        <a href="account.php" class="<?php if ($self == 'account.php') { echo "active "; } ?>item">帳號</a>
        <div class="right menu">
            <a href="#" class="notification icon item"><i class="bell outline icon"></i></a>
            <a href="#" class="item" id="logout">登出</a>
        </div>
    </div>
</div>
<div class="ts narrow container" id="notification-wrapper">
    <div class="notification container">
        <div class="ts borderless top attached segment">通知</div>
        <div class="ts relaxed divided feed"></div>
        <a class="ts bottom attached fluid button" href="notification.php">看所有通知</a>
    </div>
</div>
<script src="include/js/notification.js"></script>