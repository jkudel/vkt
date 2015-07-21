<?php
namespace database;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';
const CANNOT_BIND_RESULT = 'cannot bind result';

function insertUser($link, $userId, $userName, $passwordHash, $role) {
  $stmt = prepareQuery($link, 'INSERT INTO users (id, name, password, role) VALUES(?, ?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'issi', $userId, $userName, $passwordHash, $role)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  }
  else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function getNextUserId($link) {
  if (!performQuery($link, 'UPDATE sequences SET user_id = user_id + 1')) {
    rollbackTransaction($link);
    return 0;
  }
  $result = performQuery($link, 'SELECT user_id FROM sequences');

  if (!$result) {
    rollbackTransaction($link);
    return 0;
  }
  $value = intval(fetchOnlyValue($result));
  mysqli_free_result($result);
  return $value;
}

function getNextOrderId($link) {
  if (!performQuery($link, 'UPDATE sequences SET order_id = order_id + 1')) {
    rollbackTransaction($link);
    return 0;
  }
  $result = performQuery($link, 'SELECT order_id FROM sequences');

  if (!$result) {
    rollbackTransaction($link);
    return 0;
  }
  $value = intval(fetchOnlyValue($result));
  mysqli_free_result($result);
  return $value;
}

function performQuery($link, $query) {
  $result = mysqli_query($link, $query);

  if (!$result) {
    logMysqlError('cannot execute query', $link);
  }
  return $result;
}

function getUserIdByName($link, $name) {
  $stmt = prepareQuery($link, 'SELECT id FROM users WHERE name=?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } if (executeStatement($stmt)) {
    $result = intval(fetchOnlyValueForStmt($stmt));
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function getUserInfoByName($link, $name) {
  $stmt = prepareQuery($link, 'SELECT id, name, password, role, balance FROM users WHERE name=?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = fetchUserInfo($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function getUserInfoById($link, $id) {
  $stmt = prepareQuery($link, 'SELECT * FROM users WHERE id=?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = fetchUserInfo($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function fetchUserInfo($stmt) {
  if (!mysqli_stmt_bind_result($stmt, $id, $name, $password, $role, $balance)) {
    logMysqlStmtError(CANNOT_BIND_RESULT, $stmt);
    return null;
  }
  if (!mysqli_stmt_fetch($stmt)) {
    return null;
  }
  return [
    'id' => $id,
    'name' => $name,
    'password' => $password,
    'role' => $role,
    'balance' => $balance
  ];
}

function cancelOrder($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    rollbackTransaction($link);
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    rollbackTransaction($link);
  } else if (!executeStatement($stmt)) {
    rollbackTransaction($link);
  } else {
    $result = mysqli_stmt_affected_rows($stmt) !== 0;
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function addToChangeLog($link, $customerId, $orderId) {
  $stmt = prepareQuery($link, 'INSERT INTO done_or_canceled_log (customer_id, order_id, time) VALUE (?, ?, ?)');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'iii', $customerId, $orderId, time())) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function addOrder($link, $customerId, $orderId, $description, $price, $time) {
  $stmt = prepareQuery($link, 'INSERT INTO waiting_orders (description, price, time, order_id, customer_id) VALUES (?, ?, ?, ?, ?);');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'ssiii', $description, $price, $time, $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  }
  else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function deleteFromWaitingOrders($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = mysqli_stmt_affected_rows($stmt) !== 0;
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function findWaitingOrder($link, $orderId, $customerId) {
  $stmt = prepareQuery($link, 'SELECT order_id, customer_id, description, price, time ' .
    'FROM waiting_orders WHERE order_id=? AND customer_id=?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = fetchWaitingOrder($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function fetchWaitingOrder($stmt) {
  if (!mysqli_stmt_bind_result($stmt, $orderId, $customerId, $description, $price, $time)) {
    logMysqlStmtError(CANNOT_BIND_RESULT, $stmt);
    return null;
  }

  if (!mysqli_stmt_fetch($stmt)) {
    return null;
  }
  return [
    'order_id' => $orderId,
    'customer_id' => $customerId,
    'description' => $description,
    'price' => $price,
    'time' => $time
  ];
}

function updateUserBalance($link, $userId, $profit) {
  $stmt = prepareQuery($link, 'UPDATE users SET balance=balance+? WHERE id=?');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'di', $profit, $userId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  } else {
    $result = executeStatementAndCheckRowsAffected($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
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
    $result = false;
  }
  else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);

  if (!$result) {
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
    $result = false;
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function cleanDoneOrCanceledLog($link) {
  return performQuery($link, 'DELETE FROM done_or_canceled_log WHERE time < UNIX_TIMESTAMP() - 300');
}

function getDoneOrCanceledLog($link, $lwTime) {
  return doGetDoneOrCanceledLog($link, $lwTime, 'done_or_canceled_log');
}

function doGetDoneOrCanceledLog($link, $lwTime, $tableName) {
  $stmt = prepareQuery($link, 'SELECT order_id, customer_id, time ' . 'FROM ' . $tableName . ' WHERE TIME >= ?');

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'i', $lwTime)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = fetchDoneOrCanceledLog($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function fetchDoneOrCanceledLog($stmt) {
  if (!mysqli_stmt_bind_result($stmt, $orderId, $customerId, $time)) {
    logMysqlStmtError(CANNOT_BIND_RESULT, $stmt);
    return null;
  }
  $result = [];

  while (mysqli_stmt_fetch($stmt)) {
    array_push($result, [
      'order_id' => $orderId,
      'customer_id' => $customerId,
      'time' => $time
    ]);
  }
  return $result;
}

function getOrders($link, $tableName, $timeColumnName, $columns, $count, $condition) {
  $query = 'SELECT ' . implode($columns, ',') . ' FROM' . ' ' . $tableName;

  if ($condition != '') {
    $query .= ' WHERE ' . $condition;
  }
  $query .= ' ORDER BY ' . $timeColumnName . ' DESC, customer_id DESC, order_id DESC LIMIT ?';

  $stmt = prepareQuery($link, $query);

  if (is_null($stmt)) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'i', $count)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $order = [];
    $params = [$stmt];
    $i = 1;

    foreach ($columns as $column) {
      $params[$i++] = &$order[$column];
    }
    if (!call_user_func_array('mysqli_stmt_bind_result', $params)) {
      logMysqlStmtError(CANNOT_BIND_RESULT, $stmt);
    } else {
      $result = [];

      while (mysqli_stmt_fetch($stmt)) {
        $orderCopy = [];

        foreach ($order as $key => $value) {
          $orderCopy[$key] = $value;
        }
        array_push($result, $orderCopy);
      }
    }
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function getFeedCache($link, $lock) {
  $query = 'SELECT * FROM feed_cache ORDER BY time DESC, customer_id DESC, order_id DESC';

  if ($lock) {
    $query .= ' FOR UPDATE';
  }
  $result = performQuery($link, $query);
  return $result ? fetchAllAssoc($result) : null;
}

function putFeedCache($link, $orders) {
  if (!performQuery($link, 'TRUNCATE feed_cache')) {
    return false;
  }
  if (!$orders) {
    return true;
  }
  $valuesPart = '';

  foreach ($orders as $order) {
    $s = '(' .
      $order['order_id'] . ', ' .
      $order['customer_id'] . ', "' .
      mysqli_real_escape_string($link, $order['description']) . '", ' .
      $order['price'] . ', ' .
      intval($order['time']) . ')';

    if ($valuesPart) {
      $valuesPart .= ', ';
    }
    $valuesPart .= $s;
  }
  return performQuery($link, "INSERT INTO feed_cache " .
    "(order_id, customer_id, description, price, time) VALUES $valuesPart");
}

function getDoneOrCanceledLogCache($link) {
  return doGetDoneOrCanceledLog($link, 0, 'done_or_canceled_log_cache');
}

function putDoneOrCanceledLogCache($link, $orders) {
  if (!performQuery($link, 'TRUNCATE done_or_canceled_log_cache')) {
    return false;
  }
  if (!$orders) {
    return true;
  }
  $valuesPart = '';

  foreach ($orders as $order) {
    $s = '(' . $order['order_id'] . ', ' . $order['customer_id'] . ', ' . $order['time'] . ')';

    if ($valuesPart) {
      $valuesPart .= ', ';
    }
    $valuesPart .= $s;
  }
  return performQuery($link, "INSERT INTO done_or_canceled_log_cache " .
    "(order_id, customer_id, time) VALUES $valuesPart");
}

function getTimestamp($link) {
  $result = performQuery($link, 'SELECT UNIX_TIMESTAMP()');
  $value = $result ? fetchOnlyValue($result) : null;
  mysqli_free_result($result);
  return $value;
}

function getCacheExpirationTime($link, $cacheId) {
  $stmt = prepareQuery($link, 'SELECT time FROM expiration_times WHERE id = ?');

  if (!$stmt) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'i', $cacheId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else if (executeStatement($stmt)) {
    $result = intval(fetchOnlyValueForStmt($stmt));
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function setCacheExpirationTime($link, $cacheId, $time) {
  $stmt = prepareQuery($link, 'REPLACE INTO expiration_times (id, time) VALUES (?, ?)');

  if (!$stmt) {
    return false;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 'ii', $cacheId, $time)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function readSession($link, $sessionId) {
  $stmt = prepareQuery($link, 'SELECT data FROM sessions WHERE id = ?');

  if (!$stmt) {
    return null;
  }
  $result = null;

  if (!mysqli_stmt_bind_param($stmt, 's', $sessionId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
  }
  else if (executeStatement($stmt)) {
    $result = fetchOnlyValueForStmt($stmt);
  }
  mysqli_stmt_close($stmt);
  $sessionData = is_null($result) ? null : $result;

  if (is_null($sessionData)) {
    return null;
  }
  $time = time();
  $result = null;

  if ($sessionData !== false) {
    $stmt = prepareQuery($link, 'UPDATE sessions SET touch_time = ? WHERE id = ?');

    if (!$stmt) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'is', $time, $sessionId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    } else if (executeStatement($stmt)) {
      $result = html_entity_decode($sessionData);
    }
  } else {
    $stmt = prepareQuery($link, 'INSERT INTO sessions (id, touch_time, data) VALUES (?, ?, "")');

    if (!$stmt) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'si', $sessionId, $time)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    } else if (executeStatement($stmt)) {
      $result = '';
    }
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function writeSession($link, $sessionId, $data) {
  $stmt = prepareQuery($link, 'UPDATE sessions SET touch_time = ?, data = ? WHERE id = ?');

  if (!$stmt) {
    return false;
  }
  $time = time();
  $s = htmlentities($data, ENT_QUOTES);

  if (!mysqli_stmt_bind_param($stmt, 'iss', $time, $s, $sessionId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function destroySession($link, $sessionId) {
  $stmt = prepareQuery($link, 'DELETE FROM sessions WHERE id = ?');

  if (!$stmt) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 's', $sessionId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function deleteExpiredSessions($link, $maxLifeTime) {
  $stmt = prepareQuery($link, 'DELETE FROM sessions WHERE touch_time + ? < ?');

  if (!$stmt) {
    return false;
  }
  $time = time();

  if (!mysqli_stmt_bind_param($stmt, 'ii', $maxLifeTime, $time)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    $result = false;
  } else {
    $result = executeStatement($stmt);
  }
  mysqli_stmt_close($stmt);
  return $result;
}

function connect($host, $port, $database, $login, $password) {
  if ($port) {
    $link = mysqli_connect($host, $login, $password, $database, $port);
  } else {
    $link = mysqli_connect($host, $login, $password, $database);
  }

  if ($link && !mysqli_set_charset($link, 'utf8')) {
    close($link);
    return null;
  }
  return $link;
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
  if (!(mysqli_stmt_execute($stmt))) {
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

function fetchOnlyValueForStmt($stmt) {
  if (!mysqli_stmt_bind_result($stmt, $value)) {
    logMysqlStmtError(CANNOT_BIND_RESULT, $stmt);
    return null;
  }
  return mysqli_stmt_fetch($stmt) ? $value : false;
}

function fetchAllAssoc($result) {
  $elements = [];

  while ($e = mysqli_fetch_assoc($result)) {
    array_push($elements, $e);
  }
  return $elements;
}

function logMysqlStmtError($prefix, $stmt) {
  logError($prefix . ': ' . mysqli_stmt_error($stmt));
}

function logMysqlError($prefix, $link) {
  logError($prefix . ': ' . mysqli_error($link));
}