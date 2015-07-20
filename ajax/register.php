<?php
require_once('../libraries/common.php');
include('ajax_common.php');

$userName = getIfExists($_POST, 'user-name');
$password = getIfExists($_POST, 'password');
$repeatPassword = getIfExists($_POST, 'repeat-password');
$role = getIfExists($_POST, 'role');

if (!is_string($userName) || mb_strlen($userName) == 0) {
  validationErrorResponse(msg('no.username.error'), 'user-name');
  return;
}
$userNameMaxLength = getCommonConstant('user.name.max.length');

if (mb_strlen($userName) > $userNameMaxLength) {
  validationErrorResponse(msg('user.name.length.error', $userNameMaxLength), 'user-name');
  return;
}
$userName = mb_strtolower($userName);

if (!preg_match('/^\w+$/', $userName)) {
  validationErrorResponse(msg('invalid.char.in.username.error'), 'user-name');
  return;
}
if (!is_string($password) || mb_strlen($password) == 0) {
  validationErrorResponse(msg('no.password.error'), 'password');
  return;
}
$passwordMinLength = getCommonConstant('password.min.length');
$passwordMaxLength = getCommonConstant('password.max.length');

if (mb_strlen($password) < $passwordMinLength || mb_strlen($password) > $passwordMaxLength) {
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
$userId = \storage\getUserIdByName($userName);

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