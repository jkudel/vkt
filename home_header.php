<?php
$userName = $userInfo['name'];
$role = $userInfo['role'];
$roleName = $allRoleNames[$role];
$balance = $userInfo['balance'];
?>
<script type="text/javascript" src="home.js"></script>

<header>
  <div><?= $userName ?>, <?= $roleName ?></div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <div><?= msg('current.balance') ?>: <span id="balance"><?= $balance ?></span></div><?php
  } ?>
  <div><a href="logout.php"><?= msg('exit') ?></a></div>
</header>