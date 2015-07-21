<?php

const CONFIG = [
  'shards' => [
//    0 => ['host' => '', 'database' => 'ekudel', 'login' => 'ekudel', 'password'=>'my_password'],
//    1 => ['host' => '', 'database' => 'ekudel_shard1', 'login' => 'ekudel_shard1', 'password'=>'my_password'],
//    2 => ['host' => '', 'database' => 'ekudel_shard2', 'login' => 'ekudel_shard2', 'password'=>'my_password']
    0 => ['host' => '', 'database' => 'main', 'login' => 'root', 'password' => ''],
    1 => ['host' => '', 'database' => 'shard1', 'login' => 'root', 'password' => ''],
    2 => ['host' => '', 'database' => 'shard2', 'login' => 'root', 'password' => '']
  ],
  'shards_mapping' => [
    'users' => [1, 2],
    'waiting_orders' => [1, 2],
    'done_or_canceled_log' => [1, 2],
    'done_orders_for_customer' => [1, 2],
    'done_orders_for_executor' => [1, 2],
    'user_id_generation_sequence' => [0],
    'order_id_generation_sequence' => [1, 2],
    'sessions' => [1, 2],
    'cache' => [0]
  ],
  'store_sessions_in_db' => true,
  'use_cache' => false,

  // Debugging
  'show_down_ajax' => 0
];