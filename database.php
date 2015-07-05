<?php
namespace database;

$testDb = [
  ['id' => '1', 'name' => 'user1', 'password_hash' => password_hash('123', PASSWORD_BCRYPT), 'role' => 0],
  ['id' => '2', 'name' => 'user2', 'password_hash' => password_hash('123', PASSWORD_BCRYPT), 'role' => 1]
];

function add_user($userName, $passwordHash, $role) {
  return 0;
}

function get_user_id($userName) {
  $info = get_user_info_by_name($userName);
  return is_null($info) ? null : get_if_exists($info, 'id');
}

function get_user_info_by_name($name) {
  global $testDb;
  foreach ($testDb as $e) {
    if ($e['name'] === $name) {
      return $e;
    }
  }
  return null;
}

function get_user_info_by_id($id) {
  global $testDb;
  foreach ($testDb as $e) {
    if ($e['id'] === $id) {
      return $e;
    }
  }
  return null;
}