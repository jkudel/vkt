<?php
require_once('../libraries/common.php');
include('ajax_common.php');

$requestMethod = getIfExists($_SERVER, 'REQUEST_METHOD');

if ($requestMethod != 'POST') {
  logError('incorrect request method ' . $requestMethod);
  internalErrorResponse();
  return;
}
$userName = getIfExists($_POST, 'user-name');
$password = getIfExists($_POST, 'password');

if (!is_string($userName) || mb_strlen($userName) == 0) {
  validationErrorResponse(msg('no.username.error'), 'user-name');
  return;
}
if (!is_string($password) || mb_strlen($password) == 0) {
  validationErrorResponse(msg('no.password.error'), 'password');
  return;
}
if (mb_strlen($userName) > 20 || mb_strlen($password) > 20) {
  validationErrorResponse(msg('auth.failed.error'));
  return;
}
$userInfo = \storage\getUserInfoByName($userName);

if (is_null($userInfo) ||
  !array_key_exists('password', $userInfo) ||
  !password_verify($password, $userInfo['password'])
) {
  validationErrorResponse(msg('auth.failed.error'));
  return;
}
$userId = getIfExists($userInfo, 'id');

if (intval($userId) <= 0) {
  logError("user id should be a positive int but it is " . $userId);
  internalErrorResponse();
  return;
}
\sessions\login($userId);
successResponse();