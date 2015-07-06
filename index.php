<?php
include 'util.php';
include 'database.php';
include 'sessions.php';

const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
$allRoleNames = [msg('executor'), msg('customer')];

function handleLoginRequest() {
  $requestMethod = getIfExists($_SERVER, 'REQUEST_METHOD');

  if ($requestMethod != 'POST') {
    logError('incorrect request method '.$requestMethod);
    error(msg('internal.error'));
    return;
  }
  $userName = getIfExists($_POST, 'user-name');
  $password = getIfExists($_POST, 'password');

  if (!is_string($userName) || strlen($userName) == 0) {
    error(msg('no.username.error'), 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error(msg('no.password.error'), 'password');
    return;
  }
  if (strlen($userName) > 20 || strlen($password) > 20) {
    error(msg('auth.failed.error'));
    return;
  }
  $userInfo = \database\getUserInfoByName($userName);

  if (is_null($userInfo) ||
    !array_key_exists('password', $userInfo) ||
    !password_verify($password, $userInfo['password'])
  ) {
    error(msg('auth.failed.error'));
    return;
  }
  $userId = getIfExists($userInfo, 'id');

  if (intval($userId) <= 0) {
    logError("user id should be a positive int but it is " . $userId);
    error(msg('internal.error'));
    return;
  }
  \sessions\login($userId);
  success();
}

function handleRegisterRequest() {
  $userName = getIfExists($_POST, 'user-name');
  $password = getIfExists($_POST, 'password');
  $repeatPassword = getIfExists($_POST, 'repeat-password');
  $role = getIfExists($_POST, 'role');

  if (!is_string($userName) || strlen($userName) == 0) {
    error(msg('no.user.name'), 'user-name');
    return;
  }
  if (strlen($userName) > 20) {
    error(msg('user.name.length.error'), 'user-name');
    return;
  }
  $userName = strtolower($userName);

  if (!preg_match('/^\w+$/', $userName)) {
    error(msg('invalid.char.in.username.error'), 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error(msg('no.password.error'), 'password');
    return;
  }
  if (strlen($password) < 4 || strlen($password) > 20) {
    error(msg('password.length.error'), 'password');
    return;
  }
  if ($repeatPassword !== $password) {
    error(msg('passwords.matching.error'), 'repeat-password');
    return;
  }
  $intRole = intval($role);

  if ($intRole != $role || $intRole < 0 || $intRole > 1) {
    error(msg('invalid.value'));
    return;
  }
  if (\database\getUserId($userName) != 0) {
    error(msg('username.conflict.error'), 'user-name');
    return;
  }
  $newUserId = \database\addUser($userName, password_hash($password, PASSWORD_BCRYPT), $role);

  if ($newUserId == 0) {
    logError('cannot add new user into db');
    error(msg('internal.error'));
    return;
  }
  \sessions\login($newUserId);
  success();
}

function error($message, $fieldName = null) {
  $arr = ['error_message' => $message];

  if (!is_null($fieldName)) {
    $arr['field_name'] = $fieldName;
  }
  echo json_encode($arr);
}

function success() {
  echo json_encode(['success' => 'true']);
}

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($path) {
  case '':
    $userId = \sessions\getCurrentUserId();

    if (!is_null($userId)) {
      $userInfo = \database\getUserInfoById($userId);

      if (is_null($userInfo)) {
        \sessions\logout();
      }
    } else {
      $userInfo = null;
    }
    if (is_null($userInfo)) {
      $title = msg('login.title');
      ob_start();
      include 'login.php';
      $content = ob_get_clean();
    } else {
      $title = msg('home.title');
      ob_start();
      include 'home.php';
      $content = ob_get_clean();
    }
    include 'main_template.php';
    break;
  case 'do_login':
    handleLoginRequest();
    break;
  case 'do_register':
    handleRegisterRequest();
    break;
  case 'do_logout':
    \sessions\logout();
    success();
    break;
}