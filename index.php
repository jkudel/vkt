<?php
include 'util.php';
include 'database.php';
include 'sessions.php';

const ORDER_LIST_PART_SIZE = 20;
const ROLE_EXECUTOR = 0;
const ROLE_CUSTOMER = 1;

const ERROR_VALIDATION = 0;
const ERROR_INTERNAL = 1;
const NOT_AUTH_ERROR = 2;

$allRoleNames = [msg('executor'), msg('customer')];

function handleLoginRequest() {
  $requestMethod = getIfExists($_SERVER, 'REQUEST_METHOD');

  if ($requestMethod != 'POST') {
    logError('incorrect request method ' . $requestMethod);
    internalErrorResponse();
    return;
  }
  $userName = getIfExists($_POST, 'user-name');
  $password = getIfExists($_POST, 'password');

  if (!is_string($userName) || strlen($userName) == 0) {
    validationErrorResponse(msg('no.username.error'), 'user-name');
    return;
  }
  if (!is_string($password) || strlen($password) == 0) {
    validationErrorResponse(msg('no.password.error'), 'password');
    return;
  }
  if (strlen($userName) > 20 || strlen($password) > 20) {
    validationErrorResponse(msg('auth.failed.error'));
    return;
  }
  $userInfo = \database\getUserInfoByName($userName);

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
}

function handleRegisterRequest() {
  $userName = getIfExists($_POST, 'user-name');
  $password = getIfExists($_POST, 'password');
  $repeatPassword = getIfExists($_POST, 'repeat-password');
  $role = getIfExists($_POST, 'role');

  if (!is_string($userName) || strlen($userName) == 0) {
    validationErrorResponse(msg('no.user.name'), 'user-name');
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
  if (\database\getUserId($userName) != 0) {
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
}

function handleGetWaitingOrdersRequest() {
  $fromId = intval(getIfExists($_GET, 'from_id'));
  $orders = \database\getWaitingOrders($fromId, ORDER_LIST_PART_SIZE);

  if (is_null($orders)) {
    internalErrorResponse();
    return;
  }
  $result = array_map(function ($order) {
    return [
      'description' => $order['description'],
      'price' => strval($order['price']),
      'time' => $order['time'],
    ];
  }, $orders);
  echo json_encode($result);
}

function handleCancelOrderRequest() {
  $userId = \sessions\getCurrentUserId();

  if (is_null($userId)) {
    notAuthErrorResponse();
    return;
  }
  $orderId = intval(getIfExists($_GET, 'order_id'));

  if ($orderId == 0) {
    validationErrorResponse(msg('incorrect.order.id'));
    return;
  }
  if (!\database\cancelOrder($orderId, $userId)) {
    internalErrorResponse();
    return;
  }
  successResponse();
}

function handleCreateOrderRequest() {
  $userId = \sessions\getCurrentUserId();

  if (is_null($userId)) {
    notAuthErrorResponse();
    return;
  }
  $description = trim(getIfExists($_POST, 'description'));

  if ($description == '') {
    validationErrorResponse(msg('no.description'));
    return;
  }
  $price = floatval(getIfExists($_POST, 'price'));

  if ($price < 1) {
    validationErrorResponse(msg('min.price'));
    return;
  }
  $order = \database\addOrder($userId, $description, $price);

  if (is_null($order)) {
    internalErrorResponse();
    return;
  }
  echo json_encode(['order' => $order]);
  return;
}

function handleGetMyOrdersRequest() {
  $userId = \sessions\getCurrentUserId();

  if (is_null($userId)) {
    notAuthErrorResponse();
    return;
  }
  $done = boolval(getIfExists($_GET, 'done'));
  $offset = intval(getIfExists($_GET, 'offset'));
  $role = $userId ? \database\getUserRoleById($userId) : 0;
  $orders = \database\getOrdersForUser($userId, $role, $done, $offset, ORDER_LIST_PART_SIZE);

  if (is_null($orders)) {
    internalErrorResponse();
    return;
  }
  $result = [];

  foreach ($orders as $order) {
    $element = [
      'description' => $order['description'],
      'price' => strval($order['price']),
      'time' => $order['time'],
    ];

    if ($role === ROLE_CUSTOMER) {
      $executorId = $order['executor_id'];
      $executorName = is_null($executorId) ? null : \database\getUserNameById($executorId);

      if (!is_null($executorName)) {
        $element['executor_name'] = $executorName;
      }
    }
    array_push($result, $element);
  }
  echo json_encode($result);
}

function internalErrorResponse() {
  echo json_encode(['error_message' => msg('internal.error'), 'error_code' => ERROR_INTERNAL]);
}

function notAuthErrorResponse() {
  echo json_encode(['error_message' => msg('not.auth.error'), 'error_code' => NOT_AUTH_ERROR]);
}

function validationErrorResponse($message, $fieldName = null) {
  $arr = ['error_message' => $message, 'error_code' => ERROR_VALIDATION];

  if (!is_null($fieldName)) {
    $arr['field_name'] = $fieldName;
  }
  echo json_encode($arr);
}

function successResponse() {
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
    successResponse();
    break;
  case 'get_my_orders':
    handleGetMyOrdersRequest();
    break;
  case 'get_waiting_orders':
    handleGetWaitingOrdersRequest();
    break;
  case 'do_cancel_order':
    handleCancelOrderRequest();
    break;
  case 'do_create_order':
    handleCreateOrderRequest();
    break;
}