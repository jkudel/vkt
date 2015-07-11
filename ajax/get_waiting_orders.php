<?php
require_once('../includes/common.php');

$sinceTime = intval(getIfExists($_GET, 'since_time'));
$sinceCustomerId = intval(getIfExists($_GET, 'since_customer_id'));
$sinceOrderId = intval(getIfExists($_GET, 'since_order_id'));
$untilTime = intval(getIfExists($_GET, 'until_time'));
$untilCustomerId = intval(getIfExists($_GET, 'until_customer_id'));
$untilOrderId = intval(getIfExists($_GET, 'until_order_id'));

$orders = \database\getWaitingOrders(
  $sinceTime, $sinceCustomerId, $sinceOrderId,
  $untilTime, $untilCustomerId, $untilOrderId,
  ORDER_LIST_PART_SIZE + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$list = [];

foreach ($orders as $order) {
  array_push($list, [
    'order_id' => $order['id'],
    'customer_id' => $order['customer_id'],
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