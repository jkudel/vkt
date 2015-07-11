<?php
require_once('../includes/common.php');
const MAX_CHECK_COUNT = 20;

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$role = $userId ? \database\getUserRoleById($userId) : 0;

if ($role === ROLE_EXECUTOR) {
  $sinceTime = intval(getIfExists($_GET, 'since_time'));
  $untilTime = intval(getIfExists($_GET, 'until_time'));
  $orders = \database\getWaitingOrders($untilTime, 0, MAX_CHECK_COUNT + 1);

  if ($orders == null) {
    internalErrorResponse();
    return;
  }
  $newOrdersCount = min(MAX_CHECK_COUNT, sizeof($orders));
  $newOrdersHasMore = sizeof($orders) > MAX_CHECK_COUNT;

}