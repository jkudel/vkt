<?php

const CONFIG = [
  'shards' => [
    0 => ['host' => '', 'database' => 'shard1'],
    1 => ['host' => '', 'database' => 'shard2'],
    2 => ['host' => '', 'database' => 'shard3']
  ],
  'shards_mapping' => [
    'users' => [0, 1, 2],
    'waiting_orders' => [0, 1, 2],
    'done_or_canceled_log' => [0, 1, 2],
    'done_orders_for_customer' => [0, 1, 2],
    'done_orders_for_executor' => [0, 1, 2],
    'sequences' => [0]
  ]
];