<!DOCTYPE html>
<html>
<head lang="en">
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <link rel="stylesheet" href="styles.css" type="text/css"/>
  <script type="text/javascript" src="jquery.min.js"></script>
  <script type="text/javascript">
    var messages = {
      <?php
       $i = 0;
      foreach (MESSAGES as $key => $value) {
        if($i > 0) {
          echo ',';
        }
        echo '"'.$key.'":"'.$value.'"';
        $i++;
      }?>
    };
    var commission = <?=COMMISSION?>;
  </script>
  <script type="text/javascript" src="util.js"></script>
  <script type="text/javascript" src="ajax_requests.js"></script>
</head>
<body>
<?= $content ?>
</body>
</html>