<?php
include 'util.php';
include 'database.php';
include 'sessions.php';

const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
const ROLES_NAMES = ['Исполнитель', 'Заказчик'];

function handleLoginRequest() {
  $requestMethod = getIfExists($_SERVER, 'REQUEST_METHOD');

  if ($requestMethod != 'POST') {
    logError('incorrect request method '.$requestMethod);
    error('Внутренняя ошибка сервера');
    return;
  }
  $userName = getIfExists($_POST, 'user-name');
  $password = getIfExists($_POST, 'password');

  if (!is_string($userName) || strlen($userName) == 0) {
    error('Введите имя пользователя', 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error('Введите пароль', 'password');
    return;
  }
  if (strlen($userName) > 20 || strlen($password) > 20) {
    error('Неверное имя пользователя или пароль');
    return;
  }
  $userInfo = \database\getUserInfoByName($userName);

  if (is_null($userInfo) ||
    !array_key_exists('password', $userInfo) ||
    !password_verify($password, $userInfo['password'])
  ) {
    error('Неверное имя пользователя или пароль');
    return;
  }
  $userId = getIfExists($userInfo, 'id');

  if (intval($userId) <= 0) {
    logError("user id should be a positive int but it is " . $userId);
    error('Внутренняя ошибка сервера');
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
    error('Введите имя пользователя', 'user-name');
    return;
  }
  if (strlen($userName) > 20) {
    error('Имя пользователя может содержать максимум 20 символов', 'user-name');
    return;
  }
  $userName = strtolower($userName);

  if (!preg_match('/^\w+$/', $userName)) {
    error('Имя пользователя может содержать только буквы латинского алфавита, цифры и символ подчеркивания', 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error('Введите пароль', 'password');
    return;
  }
  if (strlen($password) < 4 || strlen($password) > 20) {
    error('Допустимая длина пароля - от 4 до 20 символов', 'password');
    return;
  }
  if ($repeatPassword !== $password) {
    error('Пароли не совпадают', 'repeat-password');
    return;
  }
  $intRole = intval($role);

  if ($intRole != $role || $intRole < 0 || $intRole > 1) {
    error('Недопустимое значение', 'role');
    return;
  }
  if (\database\getUserId($userName) != 0) {
    error('Такое имя уже занято', 'user-name');
    return;
  }
  $newUserId = \database\addUser($userName, password_hash($password, PASSWORD_BCRYPT), $role);

  if ($newUserId == 0) {
    logError('cannot add new user into db');
    error('Внутренняя ошибка сервера');
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
      $title = 'Авторизация';
      ob_start();
      include 'login.php';
      $content = ob_get_clean();
    } else {
      $title = 'Главная';
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