<?php
require_once('../includes/common.php');

$userName = getIfExists($_POST, 'user-name');
$password = getIfExists($_POST, 'password');
$repeatPassword = getIfExists($_POST, 'repeat-password');
$role = getIfExists($_POST, 'role');

if (!is_string($userName) || strlen($userName) == 0) {
  validationErrorResponse(msg('no.username.error'), 'user-name');
  return;
}
if (strlen($userName) > 20) {
  validationErrorResponse(msg('user.name.length.error'), 'user-name');
  return;
}
$userName = strtolower($userName);

if (!preg_match('/^\w+$/', $userName)) {
  validationErrorResponse(msg('invalid.char.in.username.error'), 'user-name');
  return;
}
if (!is_string($password) || strlen($password) == 0) {
  validationErrorResponse(msg('no.password.error'), 'password');
  return;
}
if (strlen($password) < 4 || strlen($password) > 20) {
  validationErrorResponse(msg('password.length.error'), 'password');
  return;
}
if ($repeatPassword !== $password) {
  validationErrorResponse(msg('passwords.matching.error'), 'repeat-password');
  return;
}
$intRole = intval($role);

if ($intRole != $role || $intRole < 0 || $intRole > 1) {
  validationErrorResponse(msg('invalid.value'), 'role');
  return;
}
$userId = \database\getUserId($userName);

if (is_null($userId)) {
  internalErrorResponse();
  return;
}
if ($userId != 0) {
  validationErrorResponse(msg('username.conflict.error'), 'user-name');
  return;
}
$newUserId = \database\addUser($userName, password_hash($password, PASSWORD_BCRYPT), $role);

if ($newUserId == 0) {
  logError('cannot add new user into db');
  internalErrorResponse();
  return;
}
\sessions\login($newUserId);
successResponse();