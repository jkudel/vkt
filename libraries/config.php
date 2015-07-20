<?php

const CONFIG = [
  'db_credentials' => [
    'login' => 'root',
    'password' => ''
  ],
  'shards' => [
    0 => ['host' => '', 'database' => 'main'],
    1 => ['host' => '', 'database' => 'shard1'],
    2 => ['host' => '', 'database' => 'shard2']
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