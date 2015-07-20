<?php
require_once('../libraries/common.php');
include('ajax_common.php');

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
$descriptionMaxLength = getCommonConstant('description.max.length');

if (mb_strlen($description) > $descriptionMaxLength) {
  validationErrorResponse(msg('description.length.error', $descriptionMaxLength));
  return;
}
$price = floatval(getIfExists($_POST, 'price'));

if ($price < 1) {
  validationErrorResponse(msg('min.price.error') . ' 1 ' . msg('currency'));
  return;
}
$maxPrice = getCommonConstant('order.max.price');

if ($price > $maxPrice) {
  validationErrorResponse(msg('max.price.error') . ' ' . $maxPrice . ' ' . msg('currency'));
  return;
}
$orderFromDb = \storage\addOrder($userId, $description, $price);

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