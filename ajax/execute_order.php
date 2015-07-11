<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$orderId = intval(getIfExists($_POST, 'order_id'));
$customerId = intval(getIfExists($_POST, 'customer_id'));

if ($orderId == 0 || $customerId == 0) {
  validationErrorResponse(msg('incorrect.order.id'));
  return;
}
if (!\database\markOrderExecuted($orderId, $customerId, $userId, COMMISSION)) {
  internalErrorResponse();
  return;
}
successResponse();