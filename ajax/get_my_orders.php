<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$sinceTime = intval(getIfExists($_GET, 'since_time'));
$sinceOrderId = intval(getIfExists($_GET, 'since_order_id'));
$untilTime = intval(getIfExists($_GET, 'until_time'));
$untilOrderId = intval(getIfExists($_GET, 'until_order_id'));
$count = intval(getIfExists($_GET, 'count'));

if (!$count) {
  $count = 1;
}
$role = $userId ? \database\getUserRoleById($userId) : 0;

if ($role === ROLE_EXECUTOR) {
  $sinceCustomerId = intval(getIfExists($_GET, 'since_customer_id'));
  $untilCustomerId = intval(getIfExists($_GET, 'until_customer_id'));

  $orders = \database\getDoneOrdersForExecutor(
    $userId,
    $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId,
    $count + 1);
} else {
  $done = intval(getIfExists($_GET, 'done'));
  $orders = \database\getOrdersForCustomer(
    $userId, $done, $sinceTime, $sinceOrderId, $untilTime, $untilOrderId,
    $count + 1);
}

if (is_null($orders)) {
  internalErrorResponse();
  return;
}
$list = [];

foreach ($orders as $order) {
  $element = [
    'order_id' => $order['id'],
    'customer_id' => $order['customer_id'],
    'description' => $order['description'],
    'time' => $order['time']
  ];
  $price = getIfExists($order, 'price');

  if ($price) {
    $element['price'] = $price;
  }
  $profit = getIfExists($order, 'profit');

  if ($profit) {
    $element['profit'] = $profit;
  }
  $doneTime = getIfExists($order, 'done_time');

  if ($doneTime) {
    $element['done_time'] = $doneTime;
  }
  if ($role === ROLE_CUSTOMER) {
    $executorId = getIfExists($order, 'executor_id');
    $executorName = is_null($executorId) ? null : \database\getUserNameById($executorId);

    if (!is_null($executorName)) {
      $element['executor'] = $executorName;
    }
  }
  array_push($list, $element);

  if (sizeof($list) == $count) {
    break;
  }
}
$hasMore = sizeof($orders) > $count;
echo json_encode(['list' => $list, 'has_more' => ($hasMore ? 'true' : 'value')]);