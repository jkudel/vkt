<?php

function getShardsByKey($key) {
  $shards = doGetShardsByKey($key);

  if (!$shards) {
    logError('cannot get shards for "'.$key.'""');
  }
  return $shards;
}

function doGetShardsByKey($key) {
  $shards = getIfExists(CONFIG, 'shards');

  if (!$shards) {
    return [];
  }
  $mapping = getIfExists(CONFIG, 'shards_mapping');
  $ids = $mapping ? getIfExists($mapping, $key) : null;

  if (!$ids) {
    return [];
  }
  $result = [];

  foreach ($ids as $id) {
    $shard = getIfExists($shards, $id);

    if ($shard) {
      array_push($result, $shard);
    }
  }
  return $result;
}

function chooseShard($shards, $key) {
  return $shards[$key % sizeof($shards)];
}

function getDbForUsers($userId) {
  return chooseShard(getAllDbsForUsers(), $userId);
}

function getAllDbsForUsers() {
  return getShardsByKey('users');
}

function getAllDbsForSessions() {
  return getShardsByKey('sessions');
}

function getDbForSessions($sessionId) {
  $key = hexdec(substr(md5($sessionId), 0, 3));
  return chooseShard(getAllDbsForSessions(), $key);
}

function getDbForUserIdGeneration() {
  $shards = getShardsByKey('user_id_generation_sequence');
  return $shards ? getIfExists($shards, 0) : null;
}

function getDbForOrderIdGeneration($customerId) {
  return chooseShard(getShardsByKey('order_id_generation_sequence'), $customerId);
}

function getDbForWaitingOrders($customerId) {
  return chooseShard(getAllDbsForWaitingOrders(), $customerId);
}

function getAllDbsForWaitingOrders() {
  return getShardsByKey('waiting_orders');
}

function getDbForDoneOrdersForCustomer($customerId) {
  return chooseShard(getShardsByKey('done_orders_for_customer'), $customerId);
}

function getDbForDoneOrdersForExecutor($executorId) {
  return chooseShard(getShardsByKey('done_orders_for_executor'), $executorId);
}

function getDbForDoneOrCanceledLog($customerId) {
  return chooseShard(getAllDbsForDoneOrCanceledLog(), $customerId);
}

function getAllDbsForDoneOrCanceledLog() {
  return getShardsByKey('done_or_canceled_log');
}

function getDbForCache($userId) {
  return chooseShard(getShardsByKey('cache'), $userId);
}