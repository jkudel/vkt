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
$result = \database\markOrderExecuted(
  $parsedOrderId['order_id'], $parsedOrderId['customer_id'], $userId, COMMISSION);

if ($result === false) {
  noObjectErrorResponse();
} else if (!$result) {
  internalErrorResponse();
} else {
  successResponse();
}
