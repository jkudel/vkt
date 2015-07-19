<div class="home-content customer">
  <?php include 'home_header.php'; ?>
  <script type="text/javascript" src="home_customer.js"></script>
  <div>
    <div class="hidden">
      <form id="new-order-form" action="#" method="POST" class="new-order-form">
        <div class="field">
          <label for="new-order-description"><?= msg('description') ?>:</label>
          <textarea id="new-order-description" class="fill" name="description" rows="7"
                    maxlength="<?= getCommonConstant('description.max.length') ?>"></textarea>

          <div class="error-placeholder"></div>
        </div>
        <div class="field">
          <label for="new-order-price"><?= msg('price') ?> (<?= msg('currency') ?>):</label>
          <input id="new-order-price" class="fill" name="price" autocomplete="off"/>

          <div class="error-placeholder"></div>
        </div>
        <div class="field last">
          <span id="new-order-error-placeholder" class="error-placeholder"></span>
          <input class="button" id="new-order-ok" type="submit" value="<?= msg('new.order') ?>"/>
          <input class="button" id="new-order-cancel" type="button" value="<?= msg('cancel') ?>"/>
        </div>
      </form>
    </div>

    <div id="view-mode">
      <a href="?view-mode=waiting" class="toggle"><?= msg('view.mode.waiting') ?></a>
      <a href="?view-mode=done" class="toggle"><?= msg('view.mode.done') ?></a>
    </div>

    <div class="orders-panel">
      <div class="show-new-panel"><span id="refresh-waiting-orders" class="in-feed-link hidden"><?= msg('refresh') ?></span></div>

      <div id="main-error-placeholder" class="error-placeholder"></div>
      <div id="orders" class="orders"></div>
      <div class="show-more-panel">
        <span id="show-more" class="in-feed-link hidden"><?= msg('show.more') ?></span>
        <span class="error-placeholder"></span>
      </div>
    </div>
  </div>

  <?php include 'footer.php' ?>
</div>