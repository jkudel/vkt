<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$description = trim(getIfExists($_POST, 'description'));

if ($description == '') {
  validationErrorResponse(msg('no.description'));
  return;
}
$price = floatval(getIfExists($_POST, 'price'));

if ($price < 1) {
  validationErrorResponse(msg('min.price'));
  return;
}
$order = \database\addOrder($userId, $description, $price);

if (is_null($order)) {
  internalErrorResponse();
  return;
}
echo json_encode(['order' => $order]);