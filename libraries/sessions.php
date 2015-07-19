<?php
namespace sessions;

const SESSIONS_ERROR = 'Sessions Error. See the log for details';

if (getIfExists(CONFIG, 'store_sessions_in_db') === true) {
  session_set_save_handler(
    function () { // open
      return true;
    },
    function () { // close
      return true;
    },
    function ($sessionId) { // read
      $value = \storage\readSession($sessionId);

      if (is_null($value)) {
        die(SESSIONS_ERROR);
      }
      return $value;
    },
    function ($sessionId, $data) { // write
      if (!\storage\writeSession($sessionId, $data)) {
        die(SESSIONS_ERROR);
}
    },
    function ($sessionId) { // destroy
      if (!\storage\destroySession($sessionId)) {
        die(SESSIONS_ERROR);
      }
    },
    function ($maxLifeTime) { // gc
      if (!\storage\deleteExpiredSessions($maxLifeTime)) {
        die(SESSIONS_ERROR);
      }
    }
  );
}

function login($userId) {
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