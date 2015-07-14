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
 * Return values:
 * false - no object
 * true - success
 * null - error
 */
function markOrderExecuted($orderId, $customerId, $executorId, $commission) {
  $dbInfo = getDbForWaitingOrders($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !\database\beginTransaction($link)) {
    return null;
  }
  $result = doMarkOrderExecuted($link, $orderId, $customerId, $executorId, $commission);

  if (is_null($result)) {
    \database\rollbackTransaction($link);
  }
  return \database\commitTransaction($link) ? $result : null;
}

function doMarkOrderExecuted($waitingOrdersLink, $orderId, $customerId, $executorId, $commission) {
  $orderInfo = \database\selectFromWaitingOrders($waitingOrdersLink, $orderId, $customerId);

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
  return \database\updateUserBalance($link, $userId, $profit);
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

function getDoneOrCanceledLog($sinceTime) {
  $arrays = [];

  foreach (getAllDbsForDoneOrCanceledLog() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $elements = \database\selectDoneOrCanceledLog($link, $sinceTime);

    if (is_null($elements)) {
      return null;
    }
    array_push($arrays, $elements);
  }
  return mergeSortedArrays($arrays, function($element) {
    return $element['time'];
  });
}

function mergeSortedArrays($arrays, $func) {
  $result = [];
  $indexes = array_fill(0, sizeof($arrays), 0);

  while (true) {
    $maxValue = null;
    $maxElement = null;
    $maxArrayIndex = null;
    $i = 0;

    foreach ($arrays as $arr) {
      $index = $indexes[$i];
      $element = getIfExists($arr, $index);

      if (!is_null($element)) {
        $value = $func($element);
        
        if (is_null($maxElement) || $value > $maxValue) {
          $maxElement = $element;
          $maxValue = $value;
          $maxArrayIndex = $i;
        }
      }
      $i++;
    }
    if (is_null($maxElement)) {
      break;
    } else {
      $indexes[$maxArrayIndex]++;
      array_push($result, $maxElement);
    }
  }
  return $result;
}

function getDoneOrdersForExecutor($userId,
                                  $sinceTime, $sinceCustomerId, $sinceOrderId,
                                  $untilTime, $untilCustomerId, $untilOrderId,
                                  $count) {
  $params = buildParamsForGetOrders('done_time', $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId);
  $dbInfo = getDbForDoneOrdersForExecutor($userId);
  $link = $dbInfo ? connect($dbInfo) : null;
  return !$link ? null : doGetOrders($link, 'done_orders_for_executor',
    'executor_id = ' . intval($userId), $count, $params);
}

function getOrdersForCustomer($userId, $done,
                              $sinceTime, $sinceOrderId, $untilTime, $untilOrderId,
                              $count) {
  $timeColumnName = $done ? 'done_time' : 'time';
  $params = buildParamsForGetOrders($timeColumnName, $sinceTime, 0, $sinceOrderId, $untilTime, 0, $untilOrderId);
  $tableName = $done ? 'done_orders_for_customer' : 'waiting_orders';
  $dbInfo = $done ? getDbForDoneOrdersForCustomer($userId) : getDbForWaitingOrders($userId);
  $link = $dbInfo ? connect($dbInfo) : null;
  return !$link ? null : doGetOrders($link, $tableName,
    'customer_id = ' . intval($userId), $count, $params);
}

function getWaitingOrders($sinceTime, $sinceCustomerId, $sinceOrderId,
                          $untilTime, $untilCustomerId, $untilOrderId, $count) {
  $params = buildParamsForGetOrders('time', $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId);
  $arrays = [];
  
  foreach (getAllDbsForWaitingOrders() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $elements = doGetOrders($link, 'waiting_orders', '', $count, $params);

    if (is_null($elements)) {
      return null;
    }
    array_push($arrays, $elements);
  }
  return mergeSortedArrays($arrays, function ($element) {
    return $element['time'];
  });
}

function buildParamsForGetOrders($timeColumnName,
                                 $sinceTime, $sinceCustomerId, $sinceOrderId,
                                 $untilTime, $untilCustomerId, $untilOrderId) {
  $params = [
    'time_column_name' => $timeColumnName,
    'since_time' => $sinceTime,
    'since_customer_id' => $sinceCustomerId,
    'since_order_id' => $sinceOrderId,
    'until_time' => $untilTime,
    'until_customer_id' => $untilCustomerId,
    'until_order_id' => $untilOrderId
  ];
  return $params;
}

function buildSinceCondition($params) {
  $timeColumnName = getIfExists($params, 'time_column_name');
  $sinceTime = intval(getIfExists($params, 'since_time'));

  if (!$sinceTime || !$timeColumnName) {
    return '';
  }
  $sinceOrderId = intval(getIfExists($params, 'since_order_id'));
  $sinceCustomerId = intval(getIfExists($params, 'since_customer_id'));

  if ($sinceCustomerId == 0 && $sinceOrderId == 0) {
    return $timeColumnName . ' >= ' . $sinceTime;
  } else {
    $condition = $timeColumnName . ' > ' . $sinceTime . ' OR ' . $timeColumnName . ' = ' . $sinceTime . ' AND ';

    if ($sinceCustomerId > 0) {
      $condition .= '(customer_id > ' . $sinceCustomerId . ' OR customer_id = ' . $sinceCustomerId .
        ' AND order_id > ' . $sinceOrderId . ')';
    } else {
      $condition .= 'order_id > ' . $sinceOrderId;
    }
    return $condition;
  }
}

function buildUntilCondition($params) {
  $timeColumnName = getIfExists($params, 'time_column_name');
  $untilTime = intval(getIfExists($params, 'until_time'));

  if (!$untilTime || !$timeColumnName) {
    return '';
  }
  $untilOrderId = intval(getIfExists($params, 'until_order_id'));
  $untilCustomerId = intval(getIfExists($params, 'until_customer_id'));

  if ($untilCustomerId == 0 && $untilOrderId == 0) {
    return $timeColumnName . ' <= ' . $untilTime;
  } else {
    $condition = $timeColumnName . ' < ' . $untilTime . ' OR ' . $timeColumnName . ' = ' . $untilTime . ' AND ';

    if ($untilCustomerId > 0) {
      $condition .= '(customer_id < ' . $untilCustomerId . ' OR customer_id = ' . $untilCustomerId .
        ' AND order_id < ' . $untilOrderId . ')';
    } else {
      $condition .= 'order_id < ' . $untilOrderId;
    }
    return $condition;
  }
}

function doGetOrders($link, $tableName, $additionalCondition, $count, $params) {
  $condition = $additionalCondition;
  $timeColumnName = getIfExists($params, 'time_column_name');

  if (!$timeColumnName) {
    $timeColumnName = 'time';
  }
  $sinceCondition = buildSinceCondition($params);

  if ($sinceCondition) {
    if ($condition != '') {
      $condition .= ' AND ';
    }
    $condition .= '(' . $sinceCondition . ')';
  }
  $untilCondition = buildUntilCondition($params);

  if ($untilCondition) {
    if ($condition != '') {
      $condition .= ' AND ';
    }
    $condition .= '(' . $untilCondition . ')';
  }
  return \database\selectOrders($link, $tableName, $timeColumnName, $count, $condition);
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
  $key = $host . '||' . $database;
  global $hostAndDb2link;
  $link = getIfExists($hostAndDb2link, $key);

  if (!$link) {
    $link = \database\connect($host, $port, $database);

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