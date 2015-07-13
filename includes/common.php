<?php
require_once('util.php');
require_once('ajax_util.php');
require_once('messages.php');
require_once('sessions.php');
require_once('database.php');

const COMMISSION = 0.1;

const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
$allRoleNames = [msg('executor'), msg('customer')];

function parseCompositeOrderId($compositeId) {
  $dotIndex = strpos($compositeId, '.');

  if ($dotIndex === false) {
    return null;
  }
  $customerId = substr($compositeId, 0, $dotIndex);
  $id = substr($compositeId, $dotIndex + 1);

  if ($customerId && $id) {
    return ['customer_id' => $customerId, 'order_id' => $id];
  }
  return null;
}

function getCompositeOrderId($order) {
  return $order['customer_id'] . '.' . $order['order_id'];
}

function getParsedOrderId($arr, $key) {
  $value = getIfExists($arr, $key);
  return $value ? parseCompositeOrderId($value) : null;
}