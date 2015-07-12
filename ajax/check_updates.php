<?php
require_once('../includes/common.php');
const MAX_CHECK_COUNT = 20;

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$sinceTime = intval(getIfExists($_GET, 'since_time'));
$sinceCustomerId = intval(getIfExists($_GET, 'since_customer_id'));
$sinceOrderId = intval(getIfExists($_GET, 'since_order_id'));

$orders = \database\getWaitingOrders(
  $sinceTime, $sinceCustomerId, $sinceOrderId, 0, 0, 0, MAX_CHECK_COUNT + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$newOrdersCount = min(MAX_CHECK_COUNT, sizeof($orders));
$newOrdersHasMore = sizeof($orders) > MAX_CHECK_COUNT;
$log = \database\getDoneOrExecutedLog($sinceTime);

if (is_null($log)) {
  internalErrorResponse();
  return;
}
echo json_encode([
  'new_orders_count' => $newOrdersCount,
  'new_orders_has_more' => $newOrdersHasMore,
  'done_or_canceled' => $log
]);
