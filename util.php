<?php

define('ROLE_EXECUTOR', 0);
define('ROLE_CUSTOMER', 1);

function get_all_roles() {
  return array(ROLE_EXECUTOR, ROLE_CUSTOMER);
}

function get_role_name($role) {
  switch ($role) {
    case ROLE_EXECUTOR:
      return 'Исполнитель';
    case ROLE_CUSTOMER:
      return 'Заказчик';
    default:
      return null;
  }
}

function get_include_contents($file_name) {
  if (is_file($file_name)) {
    ob_start();
    include $file_name;
    return ob_get_clean();
  }
  return null;
}