<?php
require_once('../libraries/common.php');
include('ajax_common.php');
const MAX_CHECK_COUNT = 20;

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$lwTime = intval(getIfExists($_GET, 'lw_time'));
$lwParsedOrderId = getParsedOrderId($_GET, 'lw_order_id');
$lwCustomerId = $lwParsedOrderId ? $lwParsedOrderId['customer_id'] : 0;
$lwOrderId = $lwParsedOrderId ? $lwParsedOrderId['order_id'] : 0;

$orders = \cache\getWaitingOrders($userId, $lwTime, $lwCustomerId,
  $lwOrderId, 0, 0, 0, MAX_CHECK_COUNT + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$newOrdersCount = min(MAX_CHECK_COUNT, sizeof($orders));
$newOrdersHasMore = sizeof($orders) > MAX_CHECK_COUNT;
$log = \cache\getDoneOrCanceledLog($userId, $lwTime);

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
