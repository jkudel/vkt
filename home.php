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
    <div><?= msg('current.balance') ?>: <?= $balance ?></div><?php
  } ?>
  <div><a href="logout.php"><?= msg('exit') ?></a></div>
</header>
<div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <h1><?= msg('available.orders') ?></h1>
  <?php } else { ?>
    <h1><?= msg('my.orders') ?></h1>
    <div><a id="new-order-link" href="#"><?= msg('new.order') ?></a></div>

    <form id="new-order-form" action="#" method="POST" class="hidden">
      <div class="field">
        <label for="new-order-description"><?= msg('description') ?>:</label><br/>
        <textarea name="description" id="new-order-description"></textarea><br/>
        <span class="error-placeholder"></span>
      </div>
      <div class="field">
        <label for="new-order-price"><?= msg('price') ?> (<?= msg('currency') ?>):</label><br/>
        <input id="new-order-price" name="price" autocomplete="off"/><br/>
        <span class="error-placeholder"></span>
      </div>
      <div id="new-order-error-placeholder" class="error-placeholder"></div>
      <div class="field">
        <input type="submit" value="<?= msg('new.order') ?>"/>
        <input id="new-order-cancel" type="button" value="<?= msg('cancel') ?>"/>
      </div>
    </form>
    <!--suppress HtmlFormInputWithoutLabel -->
    <select id="if-done">
      <option value="0" selected><?= msg('list.mode.waiting') ?></option>
      <option value="1"><?= msg('list.mode.done') ?></option>
    </select>
    <span class="error-placeholder"></span>
    <div id="my-orders"></div>
  <?php } ?>
</div>