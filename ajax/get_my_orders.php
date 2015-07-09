<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$done = intval(getIfExists($_GET, 'done'));
$beforeId = intval(getIfExists($_GET, 'before_id'));
$role = $userId ? \database\getUserRoleById($userId) : 0;
$orders = \database\getOrdersForUser($userId, $role, $done, $beforeId, ORDER_LIST_PART_SIZE + 1);

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$list = [];

foreach ($orders as $order) {
  $element = [
    'id' => $order['id'],
    'description' => $order['description'],
    'price' => strval($order['price']),
    'time' => $order['time'],
    'done_time' => $order['done_time']
  ];
  if ($role === ROLE_CUSTOMER) {
    $executorId = $order['executor_id'];
    $executorName = is_null($executorId) ? null : \database\getUserNameById($executorId);

    if (!is_null($executorName)) {
      $element['executor'] = $executorName;
    }
  }
  array_push($list, $element);

  if (sizeof($list) == ORDER_LIST_PART_SIZE) {
    break;
  }
}
$hasMore = sizeof($orders) > ORDER_LIST_PART_SIZE;
echo json_encode(['list' => $list, 'has_more' => ($hasMore ? 'true' : 'value')]);