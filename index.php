<?php
require_once 'includes/common.php';

$userId = \sessions\getCurrentUserId();

if (!is_null($userId)) {
  $userInfo = \database\getUserInfoById($userId);

  if (is_null($userInfo)) {
    \sessions\logout();
  }
} else {
  $userInfo = null;
}
if (is_null($userInfo)) {
  $title = msg('login.title');
  ob_start();
  include 'login.php';
  $content = ob_get_clean();
} else {
  $title = msg('home.title');
  ob_start();
  include 'home.php';
  $content = ob_get_clean();
}
include 'main_template.php';