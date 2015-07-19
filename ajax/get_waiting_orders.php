<?php
require_once('../libraries/common.php');
include('ajax_common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$lwTime = intval(getIfExists($_GET, 'lw_time'));
$lwParsedOrderId = getParsedOrderId($_GET, 'lw_order_id');
$lwCustomerId = $lwParsedOrderId ? $lwParsedOrderId['customer_id'] : 0;
$lwOrderId = $lwParsedOrderId ? $lwParsedOrderId['order_id'] : 0;

$upTime = intval(getIfExists($_GET, 'up_time'));
$upParsedOrderId = getParsedOrderId($_GET, 'up_order_id');
$upCustomerId = $upParsedOrderId ? $upParsedOrderId['customer_id'] : 0;
$upOrderId = $upParsedOrderId ? $upParsedOrderId['order_id'] : 0;

$count = intval(getIfExists($_GET, 'count'));

if (!$count) {
  $count = 1;
}
$orders = \cache\getWaitingOrders(
  $userId,
  $lwTime, $lwCustomerId, $lwOrderId,
  $upTime, $upCustomerId, $upOrderId,
  $count + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$list = [];

foreach ($orders as $order) {
  array_push($list, [
    'order_id' => getCompositeOrderId($order),
    'customer_id' => $order['customer_id'],
    'description' => $order['description'],
    'price' => number_format($order['price'], 2),
    'time' => $order['time'],
  ]);

  if (sizeof($list) == $count) {
    break;
  }
}
$hasMore = sizeof($orders) > $count;
echo json_encode(['list' => $list, 'has_more' => ($hasMore ? 'true' : 'false')]);