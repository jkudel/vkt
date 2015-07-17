<?php

function getShardsByTableName($tableName) {
  $shards = doGetShardsByTableName($tableName);

  if (!$shards) {
    logError('cannot get shards for "'.$tableName.'""');
  }
  return $shards;
}

function doGetShardsByTableName($tableName) {
  $shards = getIfExists(CONFIG, 'shards');

  if (!$shards) {
    return [];
  }
  $mapping = getIfExists(CONFIG, 'shards_mapping');
  $ids = $mapping ? getIfExists($mapping, $tableName) : null;

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
  return getShardsByTableName('users');
}

function getAllDbsForSessions() {
  return getShardsByTableName('sessions');
}

function getDbForSessions($sessionId) {
  $key = hexdec(substr(md5($sessionId), 0, 3));
  return chooseShard(getAllDbsForSessions(), $key);
}

function getDbForSequences() {
  $shards = getShardsByTableName('sequences');
  return $shards ? getIfExists($shards, 0) : null;
}

function getDbForWaitingOrders($customerId) {
  return chooseShard(getAllDbsForWaitingOrders(), $customerId);
}

function getAllDbsForWaitingOrders() {
  return getShardsByTableName('waiting_orders');
}

function getDbForDoneOrdersForCustomer($customerId) {
  return chooseShard(getShardsByTableName('done_orders_for_customer'), $customerId);
}

function getDbForDoneOrdersForExecutor($executorId) {
  return chooseShard(getShardsByTableName('done_orders_for_executor'), $executorId);
}

function getDbForDoneOrCanceledLog($customerId) {
  return chooseShard(getAllDbsForDoneOrCanceledLog(), $customerId);
}

function getAllDbsForDoneOrCanceledLog() {
  return getShardsByTableName('done_or_canceled_log');
}

function getDbForCache($userId) {
  return chooseShard(getShardsByTableName('cache'), $userId);
}