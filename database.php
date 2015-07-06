<?php
namespace database;

const CANNOT_PREPARE_SQL = 'cannot prepare sql query';
const CANNOT_BIND_SQL_PARAMS = 'cannot bind params to sql query';
const CANNOT_EXECUTE_SQL = 'cannot execute sql query';

function addUser($userName, $passwordHash, $role) {
  return connectAndRun(0, function ($link) use ($userName, $passwordHash, $role) {
    $stmt = mysqli_prepare($link, 'INSERT INTO users (name, password, role) VALUES(?, ?, ?)');

    if (!$stmt) {
      logError(CANNOT_PREPARE_SQL);
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssi', $userName, $passwordHash, $role)) {
      logError(CANNOT_BIND_SQL_PARAMS);
      return 0;
    }
    if (!mysqli_stmt_execute($stmt)) {
      logError(CANNOT_EXECUTE_SQL);
      return 0;
    }
    return mysqli_insert_id($link);
  });
}

function getUserId($name) {
  return connectAndRun(0, function ($link) use ($name) {
    $stmt = mysqli_prepare($link, 'SELECT id FROM users WHERE name=?');

    if (!$stmt) {
      logError(CANNOT_PREPARE_SQL);
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logError(CANNOT_BIND_SQL_PARAMS);
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
  return connectAndRun(null, function ($link) use ($name) {
    $stmt = mysqli_prepare($link, 'SELECT * FROM users WHERE name=?');

    if (!$stmt) {
      logError(CANNOT_PREPARE_SQL);
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      logError(CANNOT_BIND_SQL_PARAMS);
      return null;
    }
    return executeAndProcessResult($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });
  });
}

function getUserInfoById($id) {
  return connectAndRun(null, function ($link) use ($id) {
    $stmt = mysqli_prepare($link, 'SELECT * FROM users WHERE id=?');

    if (!$stmt) {
      logError(CANNOT_PREPARE_SQL);
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
      logError(CANNOT_BIND_SQL_PARAMS);
      return null;
    }
    return executeAndProcessResult($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });
  });
}

function executeAndProcessResult($stmt, $errorValue, $func) {
  if (!mysqli_stmt_execute($stmt)) {
    logError(CANNOT_EXECUTE_SQL);
    return $errorValue;
  }
  /** @noinspection PhpVoidFunctionResultUsedInspection */
  $result = mysqli_stmt_get_result($stmt);

  if (!$result) {
    logError('cannot get result of sql query');
    return $errorValue;
  }
  $value = $func($result);
  mysqli_free_result($result);
  return $value;
}

function connectAndRun($errorValue, $func) {
  $link = mysqli_connect('', 'root', '', 'main');

  if (!$link) {
    return $errorValue;
  }
  $result = $func($link);
  mysqli_close($link);
  return $result;
}