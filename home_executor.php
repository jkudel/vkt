<div class="home-content executor">
  <?php include 'home_header.php'; ?>
  <script type="text/javascript" src="js/home_executor.js"></script>
  <div id="view-mode">
    <a href="?view-mode=available" class="toggle"><?= msg('view.mode.available') ?></a>
    <a href="?view-mode=done" class="toggle"><?= msg('view.mode.done') ?></a>
  </div>
  <div class="orders-panel">
    <div id="top-error-placeholder" class="error-placeholder"></div>
    <div class="show-new-panel"><span id="show-new-orders" class="in-feed-link hidden"></span></div>
    <div class="orders" id="orders"></div>
    <div class="show-more-panel"><span class="in-feed-link hidden" id="show-more"><?= msg('show.more') ?></span></div>
    <div id="bottom-error-placeholder" class="error-placeholder"></div>
  </div>
  <?php include 'footer.php' ?>
</div>