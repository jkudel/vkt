<?php
$userName = $userInfo['name'];
$role = $userInfo['role'];
$roleName = $allRoleNames[$role];
$balance = $userInfo['balance'];
?>
<script type="text/javascript" src="js/home.js"></script>

<header>
  <div class="left-panel">
    <span class="title"><?= msg('title') ?></span>
    <?php if ($role == ROLE_CUSTOMER) { ?>
      <span id="new-order-button" class="inline-button"><?= msg('new.order') ?></span>
    <?php } ?>
    <a class="inline-button last" href="logout.php"><?= msg('exit') ?></a>
  </div>
  <div class="current-user-info">
    <div>
      <div><?= $userName ?>, <?= $roleName ?></div>
      <?php if ($role == ROLE_EXECUTOR) { ?>
        <div>
        <?= msg('current.balance') ?>: <span id="balance"><?= $balance ?> <?= msg('currency') ?></span>
        </div><?php
      } ?>
    </div>
  </div>
</header>