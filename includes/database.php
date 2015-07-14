<?php
namespace database;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';

function insertUser($link, $userId, $userName, $passwordHash, $role) {
  $stmt = prepareQuery($link, 'INSERT INTO users (id, name, password, role) VALUES(?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'issi', $userId, $userName, $passwordHash, $role)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
}

function getNextUserId($link) {
  if (!mysqli_query($link, 'UPDATE sequences SET user_id = user_id + 1')) {
    logMysqlError('cannot execute query', $link);
    \database\rollbackTransaction($link);
    return 0;
  }
  $result = mysqli_query($link, 'SELECT user_id FROM sequences');

  if (!$result) {
    logMysqlError('cannot execute query', $link);
    \database\rollbackTransaction($link);
    return 0;
  }
  $value = intval(fetchOnlyValue($result));
  mysqli_free_result($result);
  return $value;
}

function getUserIdByName($link, $name) {
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
}

function getUserInfoByName($link, $name) {
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
}

function getUserInfoById($link, $id) {
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

function cancelOrder($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    \database\rollbackTransaction($link);
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    \database\rollbackTransaction($link);
    return null;
  }
  if (!executeStatement($stmt)) {
    \database\rollbackTransaction($link);
    return null;
  }
  return mysqli_stmt_affected_rows($stmt) !== 0;
}

function addToChangeLog($link, $customerId, $orderId) {
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

function addOrder($link, $customerId, $description, $price, $time) {
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

function deleteFromWaitingOrders($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

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
  return mysqli_stmt_affected_rows($stmt) !== 0;
}

function selectFromWaitingOrders($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'SELECT * FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  $orderInfos = executeAndGetResultAssoc($stmt, null);
  return $orderInfos ? getIfExists($orderInfos, 0) : null;
}

function updateUserBalance($link, $userId, $profit) {
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

function insertIntoDoneTables($doneForCustomerLink, $doneForExecutorLink, $orderInfo,
                              $customerId, $executorId, $profit) {
  $orderId = getIfExists($orderInfo, 'order_id');
  $description = getIfExists($orderInfo, 'description');
  $time = getIfExists($orderInfo, 'time');
  $price = getIfExists($orderInfo, 'price');

  if (!$orderId || !$customerId || !$description || !$time || !$price) {
    logError('cannot get full order info from waiting_orders');
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

function selectDoneOrCanceledLog($link, $sinceTime) {
  $stmt = prepareQuery($link, 'SELECT order_id, customer_id, time FROM done_or_canceled_log WHERE time >= ?');

  if (is_null($stmt)) {
    return null;
  }
  if (!mysqli_stmt_bind_param($stmt, 'i', $sinceTime)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return null;
  }
  return executeAndGetResultAssoc($stmt, null);
}

function selectOrders($link, $tableName, $timeColumnName, $count, $condition) {
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

function connect($host, $port, $database) {
  return mysqli_connect($host, 'root', '', $database);
}

function close($link) {
  mysqli_close($link);
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

function prepareQuery($link, $query) {
  $stmt = mysqli_prepare($link, $query);

  if (!$stmt) {
    logError('cannot prepare sql query: ' . mysqli_error($link));
    return null;
  }
  return $stmt;
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

function logMysqlStmtError($prefix, $stmt) {
  logError($prefix . ': ' . mysqli_stmt_error($stmt));
}

function logMysqlError($prefix, $link) {
  logError($prefix . ': ' . mysqli_error($link));
}