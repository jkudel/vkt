<?php
namespace storage;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';

/** @noinspection PhpUnusedLocalVariableInspection */
$hostAndDb2link = [];

function addUser($userName, $passwordHash, $role) {
  $dbInfo = getDbForSequences();
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !\database\beginTransaction($link)) {
    return 0;
  }
  $userId = \database\getNextUserId($link);

  if (!$userId || !insertUser($userId, $userName, $passwordHash, $role)) {
    \database\rollbackTransaction($link);
    return 0;
  }
  return \database\commitTransaction($link) ? $userId : 0;
}

function insertUser($userId, $userName, $passwordHash, $role) {
  $dbInfo = getDbForUsers($userId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  return \database\insertUser($link, $userId, $userName, $passwordHash, $role);
}

/*
 * Returns 0 if user not found, and NULL if error occurred
 */
function getUserIdByName($name) {
  foreach (getAllDbsForUsers() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $userId = \database\getUserIdByName($link, $name);

    if (is_null($userId)) {
      return null;
    }
    if ($userId) {
      return $userId;
    }
  }
  return 0;
}

function getUserInfoByName($name) {
  foreach (getAllDbsForUsers() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $userInfo = \database\getUserInfoByName($link, $name);

    if ($userInfo) {
      return $userInfo;
    }
  }
  return null;
}

function getUserInfoById($id) {
  $dbInfo = getDbForUsers($id);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return null;
  }
  return \database\getUserInfoById($link, $id);
}

/**
 * Return values:
 * false - no order
 * true - success
 * null - error
 */
function cancelOrder($orderId, $customerId) {
  $dbInfo = getDbForWaitingOrders($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !\database\beginTransaction($link)) {
    return null;
  }
  $result = \database\cancelOrder($link, $orderId, $customerId);

  if (is_null($result)) {
    \database\rollbackTransaction($link);
    return null;
  }
  if ($result && !addToChangeLog($customerId, $orderId)) {
    \database\rollbackTransaction($link);
    return null;
  }
  return \database\commitTransaction($link) ? true : null;
}

function addToChangeLog($customerId, $orderId) {
  $dbInfo = getDbForDoneOrCanceledLog($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  return \database\addToChangeLog($link, $customerId, $orderId);
}

function addOrder($customerId, $description, $price) {
  $userInfo = getUserInfoById($customerId);

  if (!$userInfo || getIfExists($userInfo, 'role') !== ROLE_CUSTOMER) {
    return null;
  }
  $dbInfo = getDbForWaitingOrders($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !\database\beginTransaction($link)) {
    return null;
  }
  $time = time();
  $orderId = \database\addOrder($link, $customerId, $description, $price, $time);

  if (!$orderId) {
    \database\rollbackTransaction($link);
    return null;
  }
  return \database\commitTransaction($link) ? [
    'order_id' => $orderId,
    'customer_id' => $customerId,
    'description' => $description,
    'price' => $price,
    'time' => $time
  ] : null;
}

/**
 * Return:
 * null - error
 * false - no object
 * balance - success
 */
function markOrderExecuted($orderId, $customerId, $executorId, $commission) {
  $dbInfo = getDbForWaitingOrders($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !\database\beginTransaction($link)) {
    return null;
  }
  $result = doMarkOrderExecuted($link, $orderId, $customerId, $executorId, $commission);

  if (!$result) {
    \database\rollbackTransaction($link);
    return $result;
  }
  $userInfo = \storage\getUserInfoById($executorId);

  if (!$userInfo) {
    \database\rollbackTransaction($link);
    return null;
  }
  return \database\commitTransaction($link) ? $userInfo['balance'] : null;
}

function doMarkOrderExecuted($waitingOrdersLink, $orderId, $customerId, $executorId, $commission) {
  $orderInfo = \database\findWaitingOrder($waitingOrdersLink, $orderId, $customerId);

  if (!$orderInfo) {
    return false;
  }
  $price = getIfExists($orderInfo, 'price');

  if (!$price) {
    logError('incorrect price');
    return null;
  }
  $result = \database\deleteFromWaitingOrders($waitingOrdersLink, $orderId, $customerId);

  if (!$result) {
    return $result; // NULL (error) or FALSE (not found)
  }
  $profit = $price * (1 - $commission);

  return
    insertOrderIntoDoneTables($orderInfo, $executorId, $profit) &&
    giveProfitToUser($executorId, $profit) &&
    addToChangeLog($customerId, $orderId)
      ? true : null;
}

function giveProfitToUser($userId, $profit) {
  $dbInfo = getDbForUsers($userId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  \database\updateUserBalance($link, $userId, $profit);
  return true;
}

function insertOrderIntoDoneTables($orderInfo, $executorId, $profit) {
  $customerId = getIfExists($orderInfo, 'customer_id');

  if (!$customerId) {
    logError('cannot get customer_id');
    return false;
  }
  $dbInfo = getDbForDoneOrdersForCustomer($customerId);
  $doneForCustomerLink = $dbInfo ? connect($dbInfo) : null;

  $dbInfo = getDbForDoneOrdersForExecutor($executorId);
  $doneForExecutorLink = $dbInfo ? connect($dbInfo) : null;

  if (!$doneForCustomerLink || !$doneForExecutorLink) {
    return false;
  }
  return \database\insertIntoDoneTables($doneForCustomerLink, $doneForExecutorLink,
    $orderInfo, $customerId, $executorId, $profit);
}

function getDoneOrCanceledLog($lwTime) {
  $arrays = [];

  foreach (getAllDbsForDoneOrCanceledLog() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $elements = \database\getDoneOrCanceledLog($link, $lwTime);

    if (is_null($elements)) {
      return null;
    }
    array_push($arrays, $elements);
  }
  return mergeSortedArrays($arrays, false, false, function($element) {
    return $element['time'];
  });
}

function cleanDoneOrCanceledLog() {
  foreach (getAllDbsForDoneOrCanceledLog() as $dbInfo) {
    $link = connect($dbInfo);

    if ($link) {
      \database\cleanDoneOrCanceledLog($link);
    }
  }
}

function getDoneOrdersForExecutor($userId,
                                  $lwTime, $lwCustomerId, $lwOrderId,
                                  $upTime, $upCustomerId, $upOrderId,
                                  $count) {
  $params = buildParamsForGetOrders('done_time', $lwTime, $lwCustomerId, $lwOrderId,
    $upTime, $upCustomerId, $upOrderId);
  $dbInfo = getDbForDoneOrdersForExecutor($userId);
  $link = $dbInfo ? connect($dbInfo) : null;
  $columns = ['order_id', 'customer_id', 'description', 'profit', 'time', 'done_time'];
  return !$link ? null : doGetOrders($link, 'done_orders_for_executor', $columns,
    'executor_id = ' . intval($userId), $count, $params);
}

function getOrdersForCustomer($userId, $done,
                              $lwTime, $lwOrderId, $upTime, $upOrderId,
                              $count) {
  $timeColumnName = $done ? 'done_time' : 'time';
  $params = buildParamsForGetOrders($timeColumnName, $lwTime, 0, $lwOrderId, $upTime, 0, $upOrderId);
  $tableName = $done ? 'done_orders_for_customer' : 'waiting_orders';
  $columns = ['order_id', 'customer_id', 'description', 'price', 'time'];

  if ($done) {
    array_push($columns, 'done_time', 'executor_id');
  }
  $dbInfo = $done ? getDbForDoneOrdersForCustomer($userId) : getDbForWaitingOrders($userId);
  $link = $dbInfo ? connect($dbInfo) : null;
  return !$link ? null : doGetOrders($link, $tableName, $columns,
    'customer_id = ' . intval($userId), $count, $params);
}

function getWaitingOrders($lwTime, $lwCustomerId, $lwOrderId,
                          $upTime, $upCustomerId, $upOrderId, $count) {
  $params = buildParamsForGetOrders('time', $lwTime, $lwCustomerId, $lwOrderId,
    $upTime, $upCustomerId, $upOrderId);
  $arrays = [];
  $columns = ['order_id', 'customer_id', 'description', 'price', 'time'];
  
  foreach (getAllDbsForWaitingOrders() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $elements = doGetOrders($link, 'waiting_orders', $columns, '', $count, $params);

    if (is_null($elements)) {
      return null;
    }
    array_push($arrays, $elements);
  }
  return mergeSortedArrays($arrays, false, false, function ($element) {
    return $element['time'];
  });
}

function buildParamsForGetOrders($timeColumnName,
                                 $lwTime, $lwCustomerId, $lwOrderId,
                                 $upTime, $upCustomerId, $upOrderId) {
  $params = [
    'time_column_name' => $timeColumnName,
    'lw_time' => $lwTime,
    'lw_customer_id' => $lwCustomerId,
    'lw_order_id' => $lwOrderId,
    'up_time' => $upTime,
    'up_customer_id' => $upCustomerId,
    'up_order_id' => $upOrderId
  ];
  return $params;
}

function buildLowerBoundCondition($params) {
  $timeColumnName = getIfExists($params, 'time_column_name');
  $lwTime = intval(getIfExists($params, 'lw_time'));

  if (!$lwTime || !$timeColumnName) {
    return '';
  }
  $lwOrderId = intval(getIfExists($params, 'lw_order_id'));
  $lwCustomerId = intval(getIfExists($params, 'lw_customer_id'));

  if ($lwCustomerId == 0 && $lwOrderId == 0) {
    return $timeColumnName . ' >= ' . $lwTime;
  } else {
    $condition = $timeColumnName . ' > ' . $lwTime . ' OR ' . $timeColumnName . ' = ' . $lwTime . ' AND ';

    if ($lwCustomerId > 0) {
      $condition .= '(customer_id > ' . $lwCustomerId . ' OR customer_id = ' . $lwCustomerId .
        ' AND order_id > ' . $lwOrderId . ')';
    } else {
      $condition .= 'order_id > ' . $lwOrderId;
    }
    return $condition;
  }
}

function buildUpperBoundCondition($params) {
  $timeColumnName = getIfExists($params, 'time_column_name');
  $upTime = intval(getIfExists($params, 'up_time'));

  if (!$upTime || !$timeColumnName) {
    return '';
  }
  $upOrderId = intval(getIfExists($params, 'up_order_id'));
  $upCustomerId = intval(getIfExists($params, 'up_customer_id'));

  if ($upCustomerId == 0 && $upOrderId == 0) {
    return $timeColumnName . ' <= ' . $upTime;
  } else {
    $condition = $timeColumnName . ' < ' . $upTime . ' OR ' . $timeColumnName . ' = ' . $upTime . ' AND ';

    if ($upCustomerId > 0) {
      $condition .= '(customer_id < ' . $upCustomerId . ' OR customer_id = ' . $upCustomerId .
        ' AND order_id < ' . $upOrderId . ')';
    } else {
      $condition .= 'order_id < ' . $upOrderId;
    }
    return $condition;
  }
}

function doGetOrders($link, $tableName, $columns, $additionalCondition, $count, $params) {
  $condition = $additionalCondition;
  $timeColumnName = getIfExists($params, 'time_column_name');

  if (!$timeColumnName) {
    $timeColumnName = 'time';
  }
  $lowerBoundCondition = buildLowerBoundCondition($params);

  if ($lowerBoundCondition) {
    if ($condition != '') {
      $condition .= ' AND ';
    }
    $condition .= '(' . $lowerBoundCondition . ')';
  }
  $upperBoundCondition = buildUpperBoundCondition($params);

  if ($upperBoundCondition) {
    if ($condition != '') {
      $condition .= ' AND ';
    }
    $condition .= '(' . $upperBoundCondition . ')';
  }
  return \database\getOrders($link, $tableName, $timeColumnName, $columns, $count, $condition);
}

function readSession($sessionId) {
  $dbInfo = getDbForSessions($sessionId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return null;
  }
  return \database\readSession($link, $sessionId);
}

function writeSession($sessionId, $data) {
  $dbInfo = getDbForSessions($sessionId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  return \database\writeSession($link, $sessionId, $data);
}

function destroySession($sessionId) {
  $dbInfo = getDbForSessions($sessionId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  return \database\destroySession($link, $sessionId);
}

function deleteExpiredSessions($maxLifeTime) {
  foreach (getAllDbsForSessions() as $dbInfo) {
    $link = $dbInfo ? connect($dbInfo) : null;

    if (!$link || !\database\deleteExpiredSessions($link, $maxLifeTime)) {
      return false;
    }
  }
  return true;
}

function connect($dbInfo) {
  $host = getIfExists($dbInfo, 'host');
  $database = getIfExists($dbInfo, 'database');

  if (is_null($host) || is_null($database)) {
    logError('incorrect db info');
    return null;
  }
  $port = getIfExists($dbInfo, 'port');

  if (!$port) {
    $port = '';
  }
  $login = getIfExists($dbInfo, 'login');

  if (is_null($login)) {
    $login = '';
  }
  $password = getIfExists($dbInfo, 'password');

  if (is_null($password)) {
    $password = '';
  }
  $key = $host . '||' . $database;
  global $hostAndDb2link;
  $link = getIfExists($hostAndDb2link, $key);

  if (!$link) {
    $link = \database\connect($host, $port, $database, $login, $password);

    if (!$link) {
      logError('cannot connect to database ' . $host . ': '.$database);
      return null;
    }
    $hostAndDb2link[$key] = $link;
  }
  return $link;
}

register_shutdown_function(function () {
  session_write_close();
  global $hostAndDb2link;

  foreach ($hostAndDb2link as $link) {
    \database\close($link);
  }
});