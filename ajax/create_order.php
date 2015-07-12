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
$orderFromDb = \database\addOrder($userId, $description, $price);

if (is_null($orderFromDb)) {
  internalErrorResponse();
  return;
}
$order = [
  'order_id' => $orderFromDb['id'],
  'customer_id' => $orderFromDb['customer_id'],
  'description' => $orderFromDb['description'],
  'price' => strval($orderFromDb['price']),
  'time' => $orderFromDb['time'],
];
echo json_encode(['order' => $order]);