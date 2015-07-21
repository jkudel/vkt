<?php
require_once('../libraries/common.php');
include('ajax_common.php');

const COMMISSION = 0.1;

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
$result = \storage\markOrderExecuted(
  $parsedOrderId['order_id'], $parsedOrderId['customer_id'], $userId, COMMISSION);

if ($result === false) {
  noObjectErrorResponse();
} else if (is_null($result)) {
  internalErrorResponse();
} else {
  echo jsonEncode(['success' => 'true', 'balance' => $result]);
}
