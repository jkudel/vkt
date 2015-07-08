<?php
require_once('../includes/common.php');

$fromId = intval(getIfExists($_GET, 'from_id'));
$orders = \database\getWaitingOrders($fromId, ORDER_LIST_PART_SIZE);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$result = array_map(function ($order) {
  return [
    'description' => $order['description'],
    'price' => strval($order['price']),
    'time' => $order['time'],
  ];
}, $orders);
echo json_encode($result);