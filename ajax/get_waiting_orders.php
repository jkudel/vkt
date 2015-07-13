<?php
require_once('../includes/common.php');

$sinceTime = intval(getIfExists($_GET, 'since_time'));
$sinceParsedOrderId = getParsedOrderId($_GET, 'since_order_id');
$sinceCustomerId = $sinceParsedOrderId ? $sinceParsedOrderId['customer_id'] : 0;
$sinceOrderId = $sinceParsedOrderId ? $sinceParsedOrderId['order_id'] : 0;

$untilTime = intval(getIfExists($_GET, 'until_time'));
$untilParsedOrderId = getParsedOrderId($_GET, 'until_order_id');
$untilCustomerId = $untilParsedOrderId ? $untilParsedOrderId['customer_id'] : 0;
$untilOrderId = $untilParsedOrderId ? $untilParsedOrderId['order_id'] : 0;

$count = intval(getIfExists($_GET, 'count'));

if (!$count) {
  $count = 1;
}
$orders = \database\getWaitingOrders(
  $sinceTime, $sinceCustomerId, $sinceOrderId,
  $untilTime, $untilCustomerId, $untilOrderId,
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