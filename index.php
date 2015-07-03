<?php
include 'util.php';

function login() {
  // todo: implement
  echo json_encode(array('success' => 'true'));
}

$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($path) {
  case 'register':
    $title = 'Регистрация';
    $content = get_include_contents('register.php');
    include 'main_template.php';
    break;
  case 'login':
    $title = 'Авторизация';
    $content = get_include_contents('login.php');
    include 'main_template.php';
    break;
  case '':
    $title = 'Главная';
    $content = get_include_contents('home.php');
    include 'main_template.php';
    break;
  case 'do_login':
    login();
    break;
}