<?php
require_once('../libraries/common.php');
include('ajax_common.php');

$userId = \sessions\getCurrentUserId();

if (is_null($userId)) {
  notAuthErrorResponse();
  return;
}
$lwTime = intval(getIfExists($_GET, 'lw_time'));
$lwParsedOrderId = getParsedOrderId($_GET, 'lw_order_id');
$lwCustomerId = $lwParsedOrderId ? $lwParsedOrderId['customer_id'] : 0;
$lwOrderId = $lwParsedOrderId ? $lwParsedOrderId['order_id'] : 0;

$upTime = intval(getIfExists($_GET, 'up_time'));
$upParsedOrderId = getParsedOrderId($_GET, 'up_order_id');
$upCustomerId = $upParsedOrderId ? $upParsedOrderId['customer_id'] : 0;
$upOrderId = $upParsedOrderId ? $upParsedOrderId['order_id'] : 0;

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
    $lwTime, $lwCustomerId, $lwOrderId,
    $upTime, $upCustomerId, $upOrderId,
    $count + 1);
} else {
  $done = intval(getIfExists($_GET, 'done'));
  $orders = \storage\getOrdersForCustomer(
    $userId, $done, $lwTime, $lwOrderId, $upTime, $upOrderId,
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
    $element['price'] = number_format($price, 2);
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
echo jsonEncode(['list' => $list, 'has_more' => ($hasMore ? 'true' : 'value')]);