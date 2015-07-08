<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$orderId = intval(getIfExists($_POST, 'id'));

if ($orderId == 0) {
  validationErrorResponse(msg('incorrect.order.id'));
  return;
}
if (!\database\cancelOrder($orderId, $userId)) {
  internalErrorResponse();
  return;
}
successResponse();