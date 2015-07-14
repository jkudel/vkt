<?php
require_once('../includes/common.php');
const MAX_CHECK_COUNT = 20;

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$sinceTime = intval(getIfExists($_GET, 'since_time'));
$parsedSinceOrderId = getParsedOrderId($_GET, 'since_order_id');
$sinceCustomerId = $parsedSinceOrderId ? $parsedSinceOrderId['customer_id'] : 0;
$sinceOrderId = $parsedSinceOrderId ? $parsedSinceOrderId['order_id'] : 0;

$orders = \storage\getWaitingOrders($sinceTime, $sinceCustomerId, $sinceOrderId, 0, 0, 0, MAX_CHECK_COUNT + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$newOrdersCount = min(MAX_CHECK_COUNT, sizeof($orders));
$newOrdersHasMore = sizeof($orders) > MAX_CHECK_COUNT;
$log = \storage\getDoneOrCanceledLog($sinceTime);

if (is_null($log)) {
  internalErrorResponse();
  return;
}
$presentableLog = array_map(function ($order) {
  return getCompositeOrderId($order);
}, $log);

echo json_encode([
  'new_orders_count' => $newOrdersCount,
  'new_orders_has_more' => $newOrdersHasMore,
  'done_or_canceled' => $presentableLog
]);
