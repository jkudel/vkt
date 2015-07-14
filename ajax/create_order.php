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
  validationErrorResponse(msg('min.price.error') . ' 1 ' . msg('currency'));
  return;
}
$orderFromDb = \database\addOrder($userId, $description, $price);

if (is_null($orderFromDb)) {
  internalErrorResponse();
  return;
}
$order = [
  'order_id' => getCompositeOrderId($orderFromDb),
  'customer_id' => $orderFromDb['customer_id'],
  'description' => $orderFromDb['description'],
  'price' => number_format($orderFromDb['price'], 2),
  'time' => $orderFromDb['time'],
];
echo json_encode(['order' => $order]);