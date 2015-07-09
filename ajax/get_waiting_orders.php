<?php
require_once('../includes/common.php');

$beforeId = intval(getIfExists($_GET, 'before_id'));
$orders = \database\getWaitingOrders($beforeId, ORDER_LIST_PART_SIZE + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$list = [];

foreach ($orders as $order) {
  array_push($list, [
    'id' => $order['id'],
    'description' => $order['description'],
    'price' => strval($order['price']),
    'time' => $order['time'],
  ]);

  if (sizeof($list) == ORDER_LIST_PART_SIZE) {
    break;
  }
}
$hasMore = sizeof($orders) > ORDER_LIST_PART_SIZE;
echo json_encode(['list' => $list, 'has_more' => ($hasMore ? 'true' : 'false')]);