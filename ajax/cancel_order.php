<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$orderId = intval(getIfExists($_POST, 'order_id'));

if ($orderId == 0) {
  validationErrorResponse(msg('incorrect.order.id'));
  return;
}
$result = \database\cancelOrder($orderId, $userId);

if ($result === false) {
  noObjectErrorResponse();
} else if (!$result) {
  internalErrorResponse();
} else {
  successResponse();
}
