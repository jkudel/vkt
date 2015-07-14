<?php
namespace database;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';

/** @noinspection PhpUnusedLocalVariableInspection */
$hostAndDb2link = [];

function addUser($userName, $passwordHash, $role) {
  $dbInfo = getDbForSequences();
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !beginTransaction($link)) {
    return 0;
  }
  $userId = getNextUserId($link);

  if (!$userId || !insertUser($userId, $userName, $passwordHash, $role)) {
    rollbackTransaction($link);
    return 0;
  }
  return commitTransaction($link) ? $userId : 0;
}

function insertUser($userId, $userName, $passwordHash, $role) {
  $dbInfo = getDbForUsers($userId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  $stmt = prepareQuery($link, 'INSERT INTO users (id, name, password, role) VALUES(?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'issi', $userId, $userName, $passwordHash, $role)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  if (!executeStatement($stmt)) {
    return false;
  }
  return true;
}

function getNextUserId($link) {
  if (!mysqli_query($link, 'UPDATE sequences SET user_id = user_id + 1')) {
    logMysqlError('cannot execute query', $link);
    rollbackTransaction($link);
    return 0;
  }
  $result = mysqli_query($link, 'SELECT user_id FROM sequences');

  if (!$result) {
    logMysqlError('cannot execute query', $link);
    rollbackTransaction($link);
    return 0;
  }
  $value = intval(fetchOnlyValue($result));
  mysqli_free_result($result);
  return $value;
}

/*
 * Returns 0 if user not found, and NULL if error occurred
 */
function getUserId($name) {
  foreach (getAllDbsForUsers() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $stmt = prepareQuery($link, 'SELECT id FROM users WHERE name=?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    $userId = executeAndProcessResult($stmt, null, function ($result) {
      return intval(fetchOnlyValue($result));
    });
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
    $stmt = prepareQuery($link, 'SELECT * FROM users WHERE name=?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    $userInfo = executeAndProcessResult($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });

    if ($userInfo) {
      return $userInfo;
    }
  }
  return null;
}

function getUserNameById($id) {
  $dbInfo = getDbForUsers($id);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return null;
  }
  $stmt = prepareQuery($link, 'SELECT name FROM users WHERE id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  return executeAndProcessResult($stmt, null, function ($result) {
    return fetchOnlyValue($result);
  });
}

function getUserRoleById($id) {
  $dbInfo = getDbForUsers($id);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return null;
  }
  $stmt = prepareQuery($link, 'SELECT role FROM users WHERE id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  return executeAndProcessResult($stmt, null, function ($result) {
    $value = fetchOnlyValue($result);
    return is_null($value) ? null : intval($value);
  });
}

function getUserInfoById($id) {
  $dbInfo = getDbForUsers($id);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return null;
  }
  $stmt = prepareQuery($link, 'SELECT * FROM users WHERE id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  return executeAndProcessResult($stmt, null, function ($result) {
    return mysqli_fetch_assoc($result);
  });
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

  if (!$link || !beginTransaction($link)) {
    return null;
  }
  $result = doCancelOrder($link, $orderId, $customerId);

  if (is_null($result)) {
    rollbackTransaction($link);
    return null;
  }
  if ($result && !addChangeToLog($customerId, $orderId)) {
    rollbackTransaction($link);
    return null;
  }
  return commitTransaction($link) ? true : null;
}

function doCancelOrder($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    rollbackTransaction($link);
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    rollbackTransaction($link);
    return null;
  }
  if (!executeStatement($stmt)) {
    rollbackTransaction($link);
    return null;
  }
  return mysqli_stmt_affected_rows($stmt) !== 0;
}

function addChangeToLog($customerId, $orderId) {
  $dbInfo = getDbForDoneOrCanceledLog($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  $stmt = prepareQuery($link, 'INSERT INTO done_or_canceled_log (customer_id, order_id, time) VALUE (?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'iii', $customerId, $orderId, time())) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
}

function addOrder($customerId, $description, $price) {
  $role = getUserRoleById($customerId);

  if ($role !== ROLE_CUSTOMER) {
    return null;
  }
  $dbInfo = getDbForWaitingOrders($customerId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link || !beginTransaction($link)) {
    return null;
  }
  $time = time();
  $orderId = doAddOrder($link, $customerId, $description, $price, $time);

  if (!$orderId) {
    rollbackTransaction($link);
    return null;
  }
  return commitTransaction($link) ? [
    'order_id' => $orderId,
    'customer_id' => $customerId,
    'description' => $description,
    'price' => $price,
    'time' => $time
  ] : null;
}

function doAddOrder($link, $customerId, $description, $price, $time) {
  $stmt = prepareQuery($link, 'INSERT INTO waiting_orders (description, price, time, customer_id) VALUES (?, ?, ?, ?);');

  if (is_null($stmt)) {
    return 0;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ssii', $description, $price, $time, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return 0;
  }
  if (!executeStatement($stmt)) {
    return 0;
  }
  $orderId = intval(mysqli_insert_id($link));

  if ($orderId == 0) {
    logError('cannot get last inserted id');
  }
  return $orderId;
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

  if (!$link || !beginTransaction($link)) {
    return null;
  }
  $result = doMarkOrderExecuted($link, $orderId, $customerId, $executorId, $commission);

  if (is_null($result)) {
    rollbackTransaction($link);
  }
  return commitTransaction($link) ? $result : null;
}

function doMarkOrderExecuted($waitingOrdersLink, $orderId, $customerId, $executorId, $commission) {
  $stmt = prepareQuery($waitingOrdersLink, 'SELECT * FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  $orderInfos = executeAndGetResultAssoc($stmt, null);
  $orderInfo = $orderInfos ? getIfExists($orderInfos, 0) : null;

  if (!$orderInfo) {
    return false;
  }
  $price = getIfExists($orderInfo, 'price');

  if (!$price) {
    logError('incorrect price');
    return null;
  }
  $stmt = prepareQuery($waitingOrdersLink, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  if (!executeStatement($stmt)) {
    return null;
  }
  if (mysqli_stmt_affected_rows($stmt) === 0) {
    return false;
  }
  $profit = $price * (1 - $commission);

  return
    insertOrderIntoDoneTables($orderInfo, $executorId, $profit) &&
    giveProfitToUser($executorId, $profit) &&
    addChangeToLog($customerId, $orderId)
      ? true : null;
}

function giveProfitToUser($userId, $profit) {
  $dbInfo = getDbForUsers($userId);
  $link = $dbInfo ? connect($dbInfo) : null;

  if (!$link) {
    return false;
  }
  $stmt = prepareQuery($link, 'UPDATE users SET balance=balance+? WHERE id=?');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'di', $profit, $userId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatementAndCheckRowsAffected($stmt);
}

function insertOrderIntoDoneTables($orderInfo, $executorId, $profit) {
  $orderId = getIfExists($orderInfo, 'order_id');
  $customerId = getIfExists($orderInfo, 'customer_id');
  $description = getIfExists($orderInfo, 'description');
  $time = getIfExists($orderInfo, 'time');
  $price = getIfExists($orderInfo, 'price');

  if (!$orderId || !$customerId || !$description || !$time || !$price) {
    logError('cannot get full order info from waiting_orders');
    return false;
  }
  $dbInfo = getDbForDoneOrdersForCustomer($customerId);
  $doneForCustomerLink = $dbInfo ? connect($dbInfo) : null;

  $dbInfo = getDbForDoneOrdersForExecutor($executorId);
  $doneForExecutorLink = $dbInfo ? connect($dbInfo) : null;

  if (!$doneForCustomerLink || !$doneForExecutorLink) {
    return false;
  }
  $doneTime = time();

  $stmt = prepareQuery($doneForCustomerLink,
    'INSERT INTO done_orders_for_customer (order_id, customer_id, description, price, time, executor_id, done_time) ' .
    'VALUES (?, ?, ?, ?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'sssssss', $orderId, $customerId, $description,
    $price, $time, $executorId, $doneTime)
  ) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  if (!executeStatement($stmt)) {
    return false;
  }
  $stmt = prepareQuery($doneForExecutorLink,
    'INSERT INTO done_orders_for_executor (order_id, customer_id, description, profit, time, executor_id, done_time) ' .
    'VALUES (?, ?, ?, ?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'sssssss', $orderId, $customerId, $description,
    $profit, $time, $executorId, $doneTime)
  ) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
}

function getDoneOrCanceledLog($sinceTime) {
  $arrays = [];

  foreach (getAllDbsForDoneOrCanceledLog() as $dbInfo) {
    $link = connect($dbInfo);

    if (!$link) {
      return null;
    }
    $stmt = prepareQuery($link, 'SELECT order_id, customer_id, time FROM done_or_canceled_log WHERE time >= ?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $sinceTime)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    $elements = executeAndGetResultAssoc($stmt, null);

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
  $query = 'SELECT * FROM' . ' ' . $tableName;

  if ($condition != '') {
    $query .= ' WHERE ' . $condition;
  }
  $query .= ' ORDER BY ' . $timeColumnName . ' DESC, customer_id DESC, order_id DESC LIMIT ?';

  $stmt = prepareQuery($link, $query);

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'i', $count)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  return executeAndGetResultAssoc($stmt, null);
}

function fetchOnlyValue($result) {
  $row = mysqli_fetch_row($result);
  return is_array($row) ? getIfExists($row, 0) : null;
}

function executeAndGetResultAssoc($stmt, $defaultValue) {
  return executeAndProcessResult($stmt, $defaultValue, function ($result) {
    $elements = [];

    while ($e = mysqli_fetch_assoc($result)) {
      array_push($elements, $e);
    }
    return $elements;
  });
}

function executeAndProcessResult($stmt, $errorValue, $func) {
  if (!executeStatement($stmt)) {
    return $errorValue;
  }
  /** @noinspection PhpVoidFunctionResultUsedInspection */
  $result = mysqli_stmt_get_result($stmt);

  if (!$result) {
    logMysqlStmtError('cannot get result of sql query: ', $stmt);
    return $errorValue;
  }
  $value = $func($result);
  /** @noinspection PhpParamsInspection */
  mysqli_free_result($result);
  return $value;
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
    logInfo("DATABASE: " . $database);
    $link = mysqli_connect($host, 'root', '', $database);

    if (!$link) {
      logError('cannot connect to database ' . $host . ': '.$database);
      return null;
    }
    $hostAndDb2link[$key] = $link;
  }
  return $link;
}

function prepareQuery($link, $query) {
  $stmt = mysqli_prepare($link, $query);

  if (!$stmt) {
    logError('cannot prepare sql query: ' . mysqli_error($link));
    return null;
  }
  return $stmt;
}

function beginTransaction($link) {
  if (!mysqli_begin_transaction($link)) {
    logMysqlError('cannot start transaction', $link);
    return false;
  }
  return true;
}

function commitTransaction($link) {
  if (!mysqli_commit($link)) {
    logMysqlError('cannot commit transaction', $link);
    mysqli_rollback($link);
    return false;
  }
  return true;
}

function rollbackTransaction($link) {
  if (!mysqli_rollback($link)) {
    logMysqlError('cannot rollback transaction', $link);
    return false;
  }
  return true;
}

function executeStatement($stmt) {
  if (!mysqli_stmt_execute($stmt)) {
    logMysqlStmtError('cannot execute sql query', $stmt);
    return false;
  }
  return true;
}

function executeStatementAndCheckRowsAffected($stmt) {
  if (!executeStatement($stmt)) {
    return false;
  }
  if (mysqli_stmt_affected_rows($stmt) === 0) {
    logError('no rows affected');
    return false;
  }
  return true;
}

function logMysqlStmtError($prefix, $stmt) {
  logError($prefix . ': ' . mysqli_stmt_error($stmt));
}

function logMysqlError($prefix, $link) {
  logError($prefix . ': ' . mysqli_error($link));
}

register_shutdown_function(function () {
  global $hostAndDb2link;

  foreach ($hostAndDb2link as $link) {
    mysqli_close($link);
  }
});