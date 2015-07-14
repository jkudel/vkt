<?php
require_once('../includes/common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$sinceTime = intval(getIfExists($_GET, 'since_time'));
$sinceParsedOrderId = getParsedOrderId($_GET, 'since_order_id');
$sinceCustomerId = $sinceParsedOrderId ? $sinceParsedOrderId['customer_id'] : 0;
$sinceOrderId = $sinceParsedOrderId ? $sinceParsedOrderId['order_id'] : 0;

$untilTime = intval(getIfExists($_GET, 'until_time'));
$untilParsedOrderId = getParsedOrderId($_GET, 'until_order_id');
$untilCustomerId = $untilParsedOrderId ? $untilParsedOrderId['customer_id'] : 0;
$untilOrderId = $untilParsedOrderId ? $untilParsedOrderId['order_id'] : 0;

$count = intval(getIfExists($_GET, 'count'));

if (!$count) {
  $count = 1;
}
$userInfo = $userId ? \storage\getUserInfoById($userId) : null;

if (!$userInfo) {
  internalErrorResponse();
  return;
}
$role = getIfExists($userInfo, 'role');

if ($role === ROLE_EXECUTOR) {
  $orders = \storage\getDoneOrdersForExecutor(
    $userId,
    $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId,
    $count + 1);
} else {
  $done = intval(getIfExists($_GET, 'done'));
  $orders = \storage\getOrdersForCustomer(
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
    'order_id' => getCompositeOrderId($order),
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
    $executorInfo = $executorId ? \storage\getUserInfoById($executorId) : null;
    $executorName = $executorInfo ? getIfExists($executorInfo, 'name') : null;

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