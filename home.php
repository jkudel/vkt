<?php
$userName = $userInfo['name'];
$role = $userInfo['role'];
$roleName = $allRoleNames[$role];
$balance = '500$';
$orders = array(
  array('description' => 'order1', 'price' => 1000),
  array('description' => 'order2', 'price' => 2000)
);
?>

<header>
  <div><?= $userName ?>, <?= $roleName ?></div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <div><?=msg('current.balance')?>: <?= $balance ?></div><?php
  } ?>
  <div><a id="exit" href="#"><?=msg('exit')?></a></div>
</header>
<div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <h1><?=msg('available.orders')?></h1>

    <?php foreach ($orders as $order) { ?>
      <div>
        <div><?= $order['description'] ?></div>
        <div><?= $order['price'] ?></div>
        <div><a href="#"><?=msg('execute.order')?></a></div>
        <br/>
      </div>
    <?php }
  } else { ?>
    <h1>Мои заказы</h1>

    <?php foreach ($orders as $order) { ?>
      <div>
        <div><?= $order['description'] ?></div>
        <div><?= $order['price'] ?></div>
        <div><a href="#"><?=msg('cancel.order')?></a></div>
        <br/>
      </div>
    <?php }
  } ?>
</div>

<script type="text/javascript">
  $(document).ready(function () {
    $('#exit').click(function (e) {
      e.preventDefault();

      $.ajax({
        url: 'do_logout',
        type: "POST",
        dataType: "text",
        success: function () {
          location.reload();
        },
        error: function (response) {
          console.error(response);
        }
      });
    });
  });
</script>