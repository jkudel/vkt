<?php
namespace database;

const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';

function addUser($userName, $passwordHash, $role) {
  return connectAndRun(function ($link) use ($userName, $passwordHash, $role) {
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
  return connectAndRun(function ($link) use ($name) {
    $stmt = prepareQuery($link, 'SELECT id FROM users WHERE name=?');

    if (is_null($stmt)) {
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return 0;
    }
    return executeAndProcessResult($stmt, 0, function ($result) {
      $row = mysqli_fetch_row($result);
      $fieldValue = is_array($row) ? getIfExists($row, 0) : null;
      return $fieldValue ? intval($fieldValue) : 0;
    });
  });
}

function getUserInfoByName($name) {
  return connectAndRun(function ($link) use ($name) {
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

function getUserInfoById($id) {
  return connectAndRun(function ($link) use ($id) {
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

function removeOrder($orderId) {
  return connectAndRun(function ($link) use ($orderId) {
    if (!beginTransaction($link)) {
      return false;
    }
    $stmt = prepareQuery($link, 'DELETE FROM orders WHERE id=?;');

    if (is_null($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    if (!mysqli_stmt_bind_param($stmt, 'i', $orderId)) {
      mysqli_rollback($link);
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return false;
    }
    if (!executeStatement($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    if (!removeOrderFromWaiting($link, $orderId)) {
      mysqli_rollback($link);
      return false;
    }
    mysqli_commit($link);
    return true;
  });
}

function addOrder($customerId, $description, $price) {
  return connectAndRun(function ($link) use ($customerId, $description, $price) {
    if (!beginTransaction($link)) {
      return false;
    }
    $stmt = prepareQuery($link, 'INSERT INTO orders (description, price, time, customer_id) VALUES (?, ?, ?, ?);');

    if (is_null($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    $time = time();

    if (!mysqli_stmt_bind_param($stmt, 'ssii', $userId, $price, $time, $customerId)) {
      mysqli_rollback($link);
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return false;
    }
    if (!executeStatement($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    $orderId = intval(mysqli_insert_id($link));

    if ($orderId == 0) {
      mysqli_rollback($link);
      logError('cannot get last inserted id');
      return false;
    }
    $stmt = prepareQuery($link, 'INSERT INTO waiting_orders (order_id, description, price, time) VALUES (?, ?, ?, ?)');

    if (is_null($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    if (!mysqli_stmt_bind_param($stmt, 'isdi', $orderId, $description, $price, $time)) {
      mysqli_rollback($link);
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return false;
    }
    if (!executeStatement($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    mysqli_commit($link);
    return true;
  });
}

function markOrderExecuted($orderId, $executorId) {
  // todo: commission
  return connectAndRun(function ($link) use ($orderId, $executorId) {
    if (!beginTransaction($link)) {
      return false;
    }
    $stmt = prepareQuery($link, 'UPDATE orders SET done=TRUE, done_time=?, executor_id=?;');

    if (is_null($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    $time = time();

    if (!mysqli_stmt_bind_param($stmt, 'ii', time(), $executorId)) {
      mysqli_rollback($link);
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return false;
    }
    if (!executeStatement($stmt)) {
      mysqli_rollback($link);
      return false;
    }
    if (!removeOrderFromWaiting($link, $orderId)) {
      mysqli_rollback($link);
      return false;
    }
    mysqli_commit($link);
    return true;
  });
}

function getOrdersForUser($userId, $role, $done, $offset, $count) {
  return connectAndRun(function ($link) use ($userId, $role, $done, $offset, $count) {
    if ($role === ROLE_CUSTOMER) {
      $donePart = $done ? 'TRUE' : 'FALSE';
      $query = 'SELECT * FROM orders WHERE customer_id=? AND DONE=' . $donePart . ' ORDER BY id LIMIT ?, ?';
    } else if ($role === ROLE_EXECUTOR) {
      $query = 'SELECT * FROM orders WHERE executor_id=? ORDER BY id LIMIT ?, ?';
    } else {
      return null;
    }
    $stmt = prepareQuery($link, $query);

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'iii', $userId, $offset, $count)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndGetResultAssoc($stmt, null);
  });
}

function getWaitingOrders($fromId, $count) {
  return connectAndRun(function ($link) use ($fromId, $count) {
    $stmt = prepareQuery($link, 'SELECT * FROM waiting_orders WHERE id BETWEEN ? AND ? ORDER BY id');

    if (is_null($stmt)) {
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ii', $fromId, $fromId + $count)) {
      logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
      return null;
    }
    return executeAndGetResultAssoc($stmt, null);
  });
}

function removeOrderFromWaiting($link, $orderId) {
  $stmt = prepareQuery($link, 'DELETE FROM waiting_orders WHERE order_id=?;');

  if (is_null($stmt)) {
    return false;
  }
  if (!mysqli_stmt_bind_param($stmt, 'i', $orderId)) {
    logMysqlStmtError(CANNOT_BIND_SQL_PARAMS, $stmt);
    return false;
  }
  return executeStatement($stmt);
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

  if ($result === false) {
    logMysqlStmtError('cannot get result of sql query: ', $stmt);
    return $errorValue;
  }
  $value = $func($result);
  mysqli_free_result($result);
  return $value;
}

function connectAndRun($func) {
  $link = mysqli_connect('', 'root', '', 'main');
  $result = $func($link);
  mysqli_close($link);
  return $result;
}

function prepareQuery($link, $query) {
  $stmt = mysqli_prepare($link, $query);

  if ($stmt === false) {
    logError('cannot prepare sql query: ' . mysqli_error($link));
    return null;
  }
  return $stmt;
}

function logMysqlStmtError($prefix, $stmt) {
  logError($prefix . ': ' . mysqli_stmt_error($stmt));
}

function logMysqlError($prefix, $link) {
  logError($prefix . ': ' . mysqli_error($link));
}

function executeStatement($stmt) {
  if (!mysqli_stmt_execute($stmt)) {
    logMysqlStmtError('cannot execute sql query', $stmt);
    return false;
  }
  return true;
}

function beginTransaction($link) {
  if (!mysqli_begin_transaction($link)) {
    logMysqlError('cannot start transaction', $link);
    return false;
  }
  return true;
}