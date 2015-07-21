<?php

const CONFIG = [
  'shards' => [
    0 => ['host' => '', 'database' => 'ekudel', 'login' => 'ekudel', 'password'=>'my_password'],
    1 => ['host' => '', 'database' => 'ekudel_shard1', 'login' => 'ekudel_shard1', 'password'=>'my_password'],
    2 => ['host' => '', 'database' => 'ekudel_shard2', 'login' => 'ekudel_shard2', 'password'=>'my_password']
  ],
  'shards_mapping' => [
    'users' => [1, 2],
    'waiting_orders' => [1, 2],
    'done_or_canceled_log' => [1, 2],
    'done_orders_for_customer' => [1, 2],
    'done_orders_for_executor' => [1, 2],
    'sequences' => [0],
    'sessions' => [1, 2],
    'cache' => [0]
  ],
  'store_sessions_in_db' => true,
  'use_cache' => true,

  // Debugging
  'show_down_ajax' => 0
];