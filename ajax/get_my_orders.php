<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$done = intval(getIfExists($_GET, 'done'));
$offset = intval(getIfExists($_GET, 'offset'));
$role = $userId ? \database\getUserRoleById($userId) : 0;
$orders = \database\getOrdersForUser($userId, $role, $done, $offset, ORDER_LIST_PART_SIZE);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$result = [];

foreach ($orders as $order) {
  $element = [
    'id' => $order['id'],
    'description' => $order['description'],
    'price' => strval($order['price']),
    'time' => $order['time'],
  ];

  if ($role === ROLE_CUSTOMER) {
    $executorId = $order['executor_id'];
    $executorName = is_null($executorId) ? null : \database\getUserNameById($executorId);

    if (!is_null($executorName)) {
      $element['executor_name'] = $executorName;
    }
  }
  array_push($result, $element);
}
echo json_encode($result);