<?php
namespace database;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';

function addUser($userName, $passwordHash, $role) {
  return connectAndRun(0, function ($link) use ($userName, $passwordHash, $role) {
    $stmt = prepareQuery($link, 'INSERT INTO users (name, password, role) VALUES(?, ?, ?)');

    if (is_null($stmt)) {
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssi', $userName, $passwordHash, $role)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return 0;
    }
    if (!executeStatement($stmt)) {
      return 0;
    }
    return mysqli_insert_id($link);
  });
}

/*
 * Returns 0 if user not found, and NULL if error occurred
 */
function getUserId($name) {
  return connectAndRun(null, function ($link) use ($name) {
    $stmt = prepareQuery($link, 'SELECT id FROM users WHERE name=?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndProcessResult($stmt, null, function ($result) {
      return intval(fetchOnlyValue($result));
    });
  });
}

function getUserInfoByName($name) {
  return connectAndRun(null, function ($link) use ($name) {
    $stmt = prepareQuery($link, 'SELECT * FROM users WHERE name=?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndProcessResult($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });
  });
}

function getUserNameById($id) {
  return connectAndRun(null, function ($link) use ($id) {
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
  });
}

function getUserRoleById($id) {
  return connectAndRun(null, function ($link) use ($id) {
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
  });
}

function getUserInfoById($id) {
  return connectAndRun(null, function ($link) use ($id) {
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
  });
}

/**
 * Return values:
 * false - no order
 * true - success
 * null - error
 */
function cancelOrder($orderId, $customerId) {
  return connectAndRun(null, function ($link) use ($orderId, $customerId) {
    if (!beginTransaction($link)) {
      return null;
    }
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
    if (mysqli_stmt_affected_rows($stmt) === 0) {
      return false;
    }
    if (!addChangeToLog($link, $customerId, $orderId)) {
      rollbackTransaction($link);
      return null;
    }
    return commitTransaction($link) ? true : null;
  });
}

function addChangeToLog($link, $customerId, $orderId) {
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
  return connectAndRun(null, function ($link) use ($customerId, $description, $price) {
    if (!beginTransaction($link)) {
      return null;
    }
    $stmt = prepareQuery($link, 'INSERT INTO waiting_orders (description, price, time, customer_id) VALUES (?, ?, ?, ?);');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    $time = time();

    if (!mysqli_stmt_bind_param($stmt, 'ssii', $description, $price, $time, $customerId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return null;
    }
    if (!executeStatement($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    $orderId = intval(mysqli_insert_id($link));

    if ($orderId == 0) {
      logError('cannot get last inserted id');
      rollbackTransaction($link);
      return null;
    }
    if (!commitTransaction($link)) {
      return null;
    }

    return [
      'order_id' => $orderId,
      'customer_id' => $customerId,
      'description' => $description,
      'price' => $price,
      'time' => $time
    ];
  });
}

/**
 * Return values:
 * false - no object
 * true - success
 * null - error
 */
function markOrderExecuted($orderId, $customerId, $executorId, $commission) {
  return connectAndRun(null, function ($link) use ($orderId, $customerId, $executorId, $commission) {
    if (!beginTransaction($link)) {
      return null;
    }
    $stmt = prepareQuery($link, 'SELECT * FROM waiting_orders WHERE order_id=? AND customer_id=?');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return null;
    }
    $orderInfos = executeAndGetResultAssoc($stmt, null);
    $orderInfo = $orderInfos ? getIfExists($orderInfos, 0) : null;

    if (is_null($orderInfo)) {
      return false;
    }
    $price = getIfExists($orderInfo, 'price');

    if (!$price) {
      logError('incorrect price');
      return null;
    }
    $profit = $price * (1 - $commission);
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
      return null;
    }
    if (mysqli_stmt_affected_rows($stmt) === 0) {
      return false;
    }
    if (!insertOrderIntoDoneTables($link, $orderInfo, $executorId, $profit)) {
      rollbackTransaction($link);
      return null;
    }
    $stmt = prepareQuery($link, 'UPDATE users SET balance=balance+? WHERE id=?');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'di', $profit, $executorId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return null;
    }
    if (!executeStatementAndCheckRowsAffected($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    if (!addChangeToLog($link, $customerId, $orderId)) {
      rollbackTransaction($link);
      return null;
    }
    return commitTransaction($link) ? true : null;
  });
}

function insertOrderIntoDoneTables($link, $orderInfo, $executorId, $profit) {
  $doneTime = time();

  $stmt = prepareQuery($link,
    'INSERT INTO done_orders_for_customer (order_id, customer_id, description, price, time, executor_id, done_time) ' .
    'VALUES (?, ?, ?, ?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  $id = getIfExists($orderInfo, 'order_id');
  $customerId = getIfExists($orderInfo, 'customer_id');
  $description = getIfExists($orderInfo, 'description');
  $time = getIfExists($orderInfo, 'time');
  $price = getIfExists($orderInfo, 'price');

  if (!$id || !$customerId || !$description || !$time || !$price) {
    logError('cannot get full order info from waiting_orders');
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'sssssss', $id, $customerId, $description,
    $price, $time, $executorId, $doneTime)
  ) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  if (!executeStatement($stmt)) {
    return false;
  }
  $stmt = prepareQuery($link,
    'INSERT INTO done_orders_for_executor (order_id, customer_id, description, profit, time, executor_id, done_time) ' .
    'VALUES (?, ?, ?, ?, ?, ?, ?)');

  if (is_null($stmt)) {
    rollbackTransaction($link);
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'sssssss', $id, $customerId, $description,
    $profit, $time, $executorId, $doneTime)
  ) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
}

function getDoneOrCanceledLog($sinceTime) {
  return connectAndRun(null, function ($link) use ($sinceTime) {
    $stmt = prepareQuery($link, 'SELECT order_id, customer_id FROM done_or_canceled_log WHERE time >= ?');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $sinceTime)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndGetResultAssoc($stmt, null);
  });
}

function getDoneOrdersForExecutor($userId,
                                  $sinceTime, $sinceCustomerId, $sinceOrderId,
                                  $untilTime, $untilCustomerId, $untilOrderId,
                                  $count) {
  $params = buildParamsForGetOrders('done_time', $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId);
  return doGetOrders('done_orders_for_executor', 'executor_id = ' . intval($userId), $count, $params);
}

function getOrdersForCustomer($userId, $done,
                              $sinceTime, $sinceOrderId, $untilTime, $untilOrderId,
                              $count) {
  $timeColumnName = $done ? 'done_time' : 'time';
  $params = buildParamsForGetOrders($timeColumnName, $sinceTime, 0, $sinceOrderId, $untilTime, 0, $untilOrderId);
  $tableName = $done ? 'done_orders_for_executor' : 'waiting_orders';
  return doGetOrders($tableName, 'customer_id = ' . intval($userId), $count, $params);
}

function getWaitingOrders($sinceTime, $sinceCustomerId, $sinceOrderId,
                          $untilTime, $untilCustomerId, $untilOrderId, $count) {
  $params = buildParamsForGetOrders('time', $sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId);
  return doGetOrders('waiting_orders', '', $count, $params);
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
    return $timeColumnName.' >= ' . $sinceTime;
  } else {
    $condition = $timeColumnName.' > ' . $sinceTime . ' OR '.$timeColumnName.' = ' . $sinceTime . ' AND ';

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
    return $timeColumnName.' <= ' . $untilTime;
  } else {
    $condition = $timeColumnName.' < ' . $untilTime . ' OR ' . $timeColumnName . ' = ' . $untilTime . ' AND ';

    if ($untilCustomerId > 0) {
      $condition .= '(customer_id < ' . $untilCustomerId . ' OR customer_id = ' . $untilCustomerId .
        ' AND order_id < ' . $untilOrderId . ')';
    } else {
      $condition .= 'order_id < ' . $untilOrderId;
    }
    return $condition;
  }
}

function doGetOrders($tableName, $additionalCondition, $count, $params) {
  return connectAndRun(null, function ($link)
  use ($tableName, $additionalCondition, $params, $count) {
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
  });
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

function connectAndRun($errorValue, $func) {
  $link = mysqli_connect('', 'root', '', 'main');

  if (!$link) {
    logError('cannot connect to database');
    return $errorValue;
  }
  $result = $func($link);
  mysqli_close($link);
  return $result;
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