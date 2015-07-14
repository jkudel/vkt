<!DOCTYPE html>
<html>
<head lang="en">
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="styles.css" type="text/css"/>
  <script type="text/javascript" src="jquery.min.js"></script>
  <script type="text/javascript">
    var messages = {<?php printJsArrayContent(MESSAGES); ?>};
    var commonConstants = {<?php printJsArrayContent(COMMON_CONSTANTS); ?>};
  </script>
  <script type="text/javascript" src="util.js"></script>
  <script type="text/javascript" src="ajax_requests.js"></script>
</head>
<body>
<?= $content ?>
</body>
</html>