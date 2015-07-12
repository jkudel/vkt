<?php include 'home_header.php'; ?>
<script type="text/javascript" src="home_customer.js"></script>
<div>
  <h1><?= msg('orders') ?></h1>

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
  <select id="view-mode">
    <option value="waiting"><?= msg('view.mode.waiting') ?></option>
    <option value="done"><?= msg('view.mode.done') ?></option>
  </select>

  <div class="error-placeholder"></div>
  <div id="orders"></div>
  <div><a href="#" id="show-more" class="hidden"><?= msg('show.more') ?></a></div>
</div>