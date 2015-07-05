<?php
$userName = $userInfo['name'];
$role = $userInfo['role'];
$roleNames = ROLES_NAMES;
$roleName = $roleNames[$role];
$balance = '500$';
$orders = array(
  array('description' => 'order1', 'price' => 1000),
  array('description' => 'order2', 'price' => 2000)
);
?>

<header>
  <div><?= $userName ?>, <?= $roleName ?></div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <div>Текущий баланс: <?= $balance ?></div><?php
  } ?>
  <div><a id="exit" href="#">Выход</a></div>
</header>
<div>
  <?php if ($role == ROLE_EXECUTOR) { ?>
    <h1>Доступные заказы</h1>

    <?php foreach ($orders as $order) { ?>
      <div>
        <div><?= $order['description'] ?></div>
        <div><?= $order['price'] ?></div>
        <div><a href="#">Выполнить</a></div>
        <br/>
      </div>
    <?php }
  } else { ?>
    <h1>Мои заказы</h1>

    <?php foreach ($orders as $order) { ?>
      <div>
        <div><?= $order['description'] ?></div>
        <div><?= $order['price'] ?></div>
        <div><a href="#">Отменить</a></div>
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