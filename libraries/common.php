<?php
require_once(__DIR__ . '/messages.php');
require_once(__DIR__ . '/util.php');
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/ajax_util.php');
require_once(__DIR__ . '/sessions.php');
require_once(__DIR__ . '/shards.php');
require_once(__DIR__ . '/database.php');
require_once(__DIR__ . '/storage.php');
require_once(__DIR__ . '/cache.php');

/*
 * Общие констаты для PHP и JS
 */
const COMMON_CONSTANTS = [
  'user.name.max.length' => 20,
  'password.min.length' => 4,
  'password.max.length' => 20,
  'description.max.length' => 300,
  'order.max.price' => 1000000
];

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

function getCommonConstant($key) {
  $value = getIfExists(COMMON_CONSTANTS, $key);

  if (is_null($value)) {
    logError('cannot find constant "' . $key . '"');
    exit;
  }
  return $value;
}