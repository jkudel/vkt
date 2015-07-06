<?php
namespace database;

function add_user($userName, $passwordHash, $role) {
  return connect_and_run(0, function ($link) use ($userName, $passwordHash, $role) {
    $stmt = mysqli_prepare($link, 'INSERT INTO users (name, password, role) VALUES(?, ?, ?)');

    if (!$stmt) {
      log_error('cannot prepare sql query');
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 'ssi', $userName, $passwordHash, $role)) {
      log_error('cannot bind params to sql query');
      return 0;
    }
    if (!mysqli_stmt_execute($stmt)) {
      log_error('cannot execute sql query');
      return 0;
    }
    return mysqli_insert_id($link);
  });
}

function get_user_id($name) {
  return connect_and_run(0, function ($link) use ($name) {
    $stmt = mysqli_prepare($link, 'SELECT id FROM users WHERE name=?');

    if (!$stmt) {
      log_error('cannot prepare sql query');
      return 0;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      log_error('cannot bind params to sql query');
      return 0;
    }
    return execute_and_process_result($stmt, 0, function ($result) {
      $row = mysqli_fetch_row($result);
      $fieldValue = is_array($row) ? get_if_exists($row, 0) : null;
      return $fieldValue ? intval($fieldValue) : 0;
    });
  });
}

function get_user_info_by_name($name) {
  return connect_and_run(null, function ($link) use ($name) {
    $stmt = mysqli_prepare($link, 'SELECT * FROM users WHERE name=?');

    if (!$stmt) {
      log_error('cannot prepare sql query');
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $name)) {
      log_error('cannot bind params to sql query');
      return null;
    }
    return execute_and_process_result($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });
  });
}

function get_user_info_by_id($id) {
  return connect_and_run(null, function ($link) use ($id) {
    $stmt = mysqli_prepare($link, 'SELECT * FROM users WHERE id=?');

    if (!$stmt) {
      log_error('cannot prepare sql query');
      return null;
    }
    if (!mysqli_stmt_bind_param($stmt, 's', $id)) {
      log_error('cannot bind params to sql query');
      return null;
    }
    return execute_and_process_result($stmt, null, function ($result) {
      return mysqli_fetch_assoc($result);
    });
  });
}

function execute_and_process_result($stmt, $errorValue, $func) {
  if (!mysqli_stmt_execute($stmt)) {
    log_error('cannot execute sql query');
    return $errorValue;
  }
  /** @noinspection PhpVoidFunctionResultUsedInspection */
  $result = mysqli_stmt_get_result($stmt);

  if (!$result) {
    log_error('cannot get result of sql query');
    return $errorValue;
  }
  $value = $func($result);
  mysqli_free_result($result);
  return $value;
}

function connect_and_run($errorValue, $func) {
  $link = mysqli_connect('', 'root', '', 'main');

  if (!$link) {
    return $errorValue;
  }
  $result = $func($link);
  mysqli_close($link);
  return $result;
}