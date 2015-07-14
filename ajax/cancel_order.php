<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$parsedOrderId = getParsedOrderId($_POST, 'order_id');

if (!$parsedOrderId) {
  validationErrorResponse(msg('incorrect.order.id'));
  return;
}
$result = \storage\cancelOrder($parsedOrderId['order_id'], $userId);

if ($result === false) {
  noObjectErrorResponse();
} else if (!$result) {
  internalErrorResponse();
} else {
  successResponse();
}
