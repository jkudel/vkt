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
      $value = fetchOnlyValue($result);
      return is_null($value) ? null : intval($value);
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

function cancelOrder($orderId, $customerId) {
  return connectAndRun(false, function ($link) use ($orderId, $customerId) {
    if (!beginTransaction($link)) {
      return false;
    }
    $stmt = prepareQuery($link, 'DELETE FROM orders WHERE id=? AND customer_id=?;');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return false;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ii', $orderId, $customerId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return false;
    }
    if (!executeStatement($stmt)) {
      rollbackTransaction($link);
      return false;
    }
    if (!removeOrderFromWaiting($link, $orderId)) {
      rollbackTransaction($link);
      return false;
    }
    return commitTransaction($link);
  });
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
    $stmt = prepareQuery($link, 'INSERT INTO orders (description, price, time, customer_id) VALUES (?, ?, ?, ?);');

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
    $stmt = prepareQuery($link, 'INSERT INTO waiting_orders (id, description, price, time) VALUES (?, ?, ?, ?)');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'isdi', $orderId, $description, $price, $time)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return null;
    }
    if (!executeStatement($stmt)) {
      rollbackTransaction($link);
      return null;
    }
    if (!commitTransaction($link)) {
      return null;
    }
    return ['id' => $orderId, 'description' => $description, 'price' => $price, 'time' => $time];
  });
}

function markOrderExecuted($orderId, $executorId, $commission) {
  return connectAndRun(null, function ($link) use ($orderId, $executorId, $commission) {
    if (!beginTransaction($link)) {
      return false;
    }
    $stmt = prepareQuery($link, 'UPDATE orders SET done=TRUE, done_time=?, executor_id=? WHERE id=? AND done=FALSE;');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return false;
    }
    if (!mysqli_stmt_bind_param($stmt, 'iii', time(), $executorId, $orderId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return false;
    }
    if (!executeStatement($stmt) || mysqli_stmt_affected_rows($stmt) == 0) {
      rollbackTransaction($link);
      return false;
    }
    if (!removeOrderFromWaiting($link, $orderId)) {
      rollbackTransaction($link);
      return false;
    }
    $stmt = prepareQuery($link, 'SELECT price FROM orders WHERE id=?;');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return false;
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $orderId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return false;
    }
    $price = executeAndProcessResult($stmt, null, function ($result) {
      return fetchOnlyValue($result);
    });
    if (is_null($price)) {
      rollbackTransaction($link);
      return false;
    }
    $stmt = prepareQuery($link, 'UPDATE users SET balance=balance+? WHERE id=?;');

    if (is_null($stmt)) {
      rollbackTransaction($link);
      return false;
    }
    $delta = $price * (1 - $commission);

    if (!mysqli_stmt_bind_param($stmt, 'di', $delta, $executorId)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      rollbackTransaction($link);
      return false;
    }
    if (!executeStatement($stmt) || mysqli_stmt_affected_rows($stmt) == 0) {
      rollbackTransaction($link);
      return false;
    }
    return commitTransaction($link);
  });
}

function getOrdersForUser($userId, $role, $done, $afterId, $beforeId, $count) {
  return connectAndRun(null, function ($link) use ($userId, $role, $done, $afterId, $beforeId, $count) {
    if ($role === ROLE_CUSTOMER) {
      $donePart = $done ? 'TRUE' : 'FALSE';
      $query = 'SELECT * FROM orders WHERE customer_id=? AND id > ? AND DONE=' . $donePart;
    } else if ($role === ROLE_EXECUTOR) {
      $query = 'SELECT * FROM orders WHERE executor_id=? AND id > ?';
    } else {
      return null;
    }
    $intBeforeId = intval($beforeId);

    if ($intBeforeId > 0) {
      $query .= ' AND id < ' . $intBeforeId;
    }
    $query .= ' ORDER BY id DESC LIMIT ?';
    $stmt = prepareQuery($link, $query);

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'iii', $userId, $afterId, $count)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndGetResultAssoc($stmt, null);
  });
}

function getWaitingOrders($afterId, $beforeId, $count) {
  return connectAndRun(null, function ($link) use ($afterId, $beforeId, $count) {
    $intBeforeId = intval($beforeId);
    $query = 'SELECT * FROM waiting_orders WHERE id > ?';

    if ($intBeforeId > 0) {
      $query .= ' AND id < ' . $intBeforeId;
    }
    $query .= ' ORDER BY id DESC LIMIT ?';
    $stmt = prepareQuery($link, $query);

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ii', $afterId, $count)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndGetResultAssoc($stmt, null);
  });
}

function removeOrderFromWaiting($link, $orderId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE id=?;');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'i', $orderId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
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

function logMysqlStmtError($prefix, $stmt) {
  logError($prefix . ': ' . mysqli_stmt_error($stmt));
}

function logMysqlError($prefix, $link) {
  logError($prefix . ': ' . mysqli_error($link));
}