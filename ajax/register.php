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
$userNameMaxLength = getCommonConstant('user.name.max.length');

if (strlen($userName) > $userNameMaxLength) {
  validationErrorResponse(msg('user.name.length.error', $userNameMaxLength), 'user-name');
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
$passwordMinLength = getCommonConstant('password.min.length');
$passwordMaxLength = getCommonConstant('password.max.length');

if (strlen($password) < $passwordMinLength || strlen($password) > $passwordMaxLength) {
  validationErrorResponse(msg('password.length.error', $passwordMinLength, $passwordMaxLength), 'password');
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
$userId = \storage\getUserId($userName);

if (is_null($userId)) {
  internalErrorResponse();
  return;
}
if ($userId != 0) {
  validationErrorResponse(msg('username.conflict.error'), 'user-name');
  return;
}
$newUserId = \storage\addUser($userName, password_hash($password, PASSWORD_BCRYPT), $role);

if ($newUserId == 0) {
  logError('cannot add new user into db');
  internalErrorResponse();
  return;
}
\sessions\login($newUserId);
successResponse();