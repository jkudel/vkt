<?php include 'home_header.php'; ?>
<script type="text/javascript" src="home_executor.js"></script>
<div>
  <h1><?= msg('orders') ?></h1>

  <!--suppress HtmlFormInputWithoutLabel -->
  <select id="view-mode">
    <option value="available"><?= msg('view.mode.available') ?></option>
    <option value="done"><?= msg('view.mode.done') ?></option>
  </select>

  <div class="error-placeholder"></div>
  <div id="orders"></div>
  <div><a href="#" class="show-more"><?= msg('show.more') ?></a></div>
</div>