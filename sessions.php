<?php
namespace sessions;

function login($userId) {
  if (!is_string($userId)) {
    log_error('userId must be string');
    return false;
  }
  // todo: destroy session after some time
  if (!start_session_if_necessary()) {
    return false;
  }
  $_SESSION['user_id'] = $userId;
  return true;
}

function logout() {
  if (start_session_if_necessary()) {
    session_destroy();
  }
}

function get_current_user_id() {
  if (!start_session_if_necessary()) {
    return null;
  }
  return isset($_SESSION) ? get_if_exists($_SESSION, 'user_id') : null;
}

function start_session_if_necessary() {
  if (session_status() == PHP_SESSION_NONE && !session_start()) {
    log_error('cannot start session');
    return false;
  }
  return true;
}