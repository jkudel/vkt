<?php include 'home_header.php'; ?>
<script type="text/javascript" src="home_executor.js"></script>
<div>
  <div>
    <h1><?= msg('available.orders') ?></h1>

    <div class="error-placeholder"></div>
    <div id="available-orders"></div>
  </div>
  <div>
    <h1><?= msg('my.orders') ?></h1>

    <div class="error-placeholder"></div>
    <div id="my-orders"></div>
  </div>
</div>