<?php
namespace sessions;

function login($userId) {
  // todo: destroy session after some time
  if (!startSessionIfNecessary()) {
    return false;
  }
  $_SESSION['user_id'] = strval($userId);
  return true;
}

function logout() {
  if (startSessionIfNecessary()) {
    session_destroy();
  }
}

function getCurrentUserId() {
  if (!startSessionIfNecessary()) {
    return null;
  }
  return isset($_SESSION) ? getIfExists($_SESSION, 'user_id') : null;
}

function startSessionIfNecessary() {
  if (session_status() == PHP_SESSION_NONE && !session_start()) {
    logError('cannot start session');
    return false;
  }
  return true;
}