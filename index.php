<?php
include 'util.php';
include 'database.php';
include 'sessions.php';

const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;
const ROLES_NAMES = ['Исполнитель', 'Заказчик'];

function handle_login_request() {
  $requestMethod = get_if_exists($_SERVER, 'REQUEST_METHOD');

  if ($requestMethod != 'POST') {
    log_error('incorrect request method '.$requestMethod);
    error('Внутренняя ошибка сервера');
    return;
  }
  $userName = get_if_exists($_POST, 'user-name');
  $password = get_if_exists($_POST, 'password');

  if (!is_string($userName) || strlen($userName) == 0) {
    error('Введите имя пользователя', 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error('Введите пароль', 'password');
    return;
  }
  $userInfo = \database\get_user_info_by_name($userName);

  if (is_null($userInfo) ||
    !array_key_exists('password_hash', $userInfo) ||
    !password_verify($password, $userInfo['password_hash'])
  ) {
    error('Неверное имя пользователя или пароль');
    return;
  }
  $userId = get_if_exists($userInfo, 'id');

  if (intval($userId) <= 0) {
    log_error("user id should be a positive int but it is " . $userId);
    error('Внутренняя ошибка сервера');
    return;
  }
  \sessions\login($userId);
  success();
}

function handle_register_request() {
  $userName = get_if_exists($_POST, 'user-name');
  $password = get_if_exists($_POST, 'password');
  $repeatPassword = get_if_exists($_POST, 'repeat-password');
  $role = get_if_exists($_POST, 'role');

  if (!is_string($userName) || strlen($userName) == 0) {
    error('Введите имя пользователя', 'user-name');
    return;
  }
  $userName = strtolower($userName);

  if (!preg_match('/[^a-z0-9_]/', $userName)) {
    error('Имя пользователя может содержать только буквы латинского алфавита, цифры и символ подчеркивания', 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    error('Введите пароль', 'password');
    return;
  }
  if (strlen($password) < 4) {
    error('Пароль должен быть минимум 4 символа', 'password');
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
  if (\database\get_user_id($userName) != 0) {
    error('Такое имя уже занято', 'user-name');
    return;
  }
  $newUserId = \database\add_user($userName, password_hash($password, PASSWORD_BCRYPT), $role);

  if ($newUserId == 0) {
    log_error('cannot add new user into db');
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
    $userId = \sessions\get_current_user_id();

    if (!is_null($userId)) {
      $userInfo = \database\get_user_info_by_id($userId);

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
    handle_login_request();
    break;
  case 'do_register':
    handle_register_request();
    break;
  case 'do_logout':
    \sessions\logout();
    success();
    break;
}