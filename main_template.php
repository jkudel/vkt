<!DOCTYPE html>
<html>
<head lang="en">
  <meta charset="UTF-8">
  <title>VKT | <?= $title ?></title>
  <link rel="stylesheet" href="styles.css" type="text/css"/>
  <script type="text/javascript" src="js/jquery.min.js"></script>
  <script type="text/javascript" src="js/moment.min.js"></script>
  <script type="text/javascript">
    var messages = {<?php printJsArrayContent(MESSAGES); ?>};
    var commonConstants = {<?php printJsArrayContent(COMMON_CONSTANTS); ?>};
  </script>
  <script type="text/javascript" src="js/util.js"></script>
  <script type="text/javascript" src="js/ajax_requests.js"></script>
</head>
<body>
<div class="content"><?= $content ?></div>
</body>
</html>