<?php
namespace cache;

const WAITING_ORDERS_CACHE_ID = 1;
const DONE_OR_CANCELED_LOG_CACHE_ID = 2;

const WAITING_ORDERS_CACHE_LIFETIME = 10;
const WAITING_ORDERS_CACHE_SIZE = 300;

const DONE_OR_CANCELED_LOG_CACHE_LIFETIME = 10000;

function getDoneOrCanceledLog($userId, $sinceTime) {
  $cacheDbInfo = getDbForCache($userId);

  if (getIfExists(CONFIG, 'use_cache') !== true || !$cacheDbInfo) {
    return \storage\getDoneOrCanceledLog($sinceTime);
  }
  $link = \storage\connect($cacheDbInfo);

  if (!$link) {
    return null;
  }
  $timestamp = \database\getTimestamp($link);

  if (!$timestamp) {
    return null;
  }
  $ordersFromCache = getCachedDoneOrCanceledLog($link, $sinceTime, $timestamp);

  if (!is_null($ordersFromCache)) {
    return $ordersFromCache;
  }
  $orders = \storage\getDoneOrCanceledLog(0);

  if (is_null($orders)) {
    return null;
  }
  if (!cacheDoneOrCanceledLog($link, $orders, $timestamp)) {
    logError('cannot cache done_or_canceled_log');
  }
  if (!\database\setCacheExpirationTime($link, WAITING_ORDERS_CACHE_ID, 0)) {
    logError('cannot invalidate waiting_orders cache');
  }
  return $orders;
}

function getCachedDoneOrCanceledLog($link, $sinceTime, $timestamp) {
  $expirationTime = \database\getCacheExpirationTime($link, DONE_OR_CANCELED_LOG_CACHE_ID);

  if (!$expirationTime || $timestamp > $expirationTime) {
    return null;
  }
  $cachedOrders = \database\getDoneOrCanceledLogCache($link);
  return is_null($cachedOrders) ? null :
    filterDoneOrCanceledLog($cachedOrders, $sinceTime);
}

function filterDoneOrCanceledLog($orders, $sinceTime) {
  $result = [];

  foreach ($orders as $order) {
    if ($order['time'] <= $sinceTime) {
      return $result;
    }
    array_push($result, $order);
  }
  return $result;
}

function cacheDoneOrCanceledLog($link, $orders, $timestamp) {
  if (!\database\beginTransaction($link)) {
    return false;
  }
  $expirationTime = $timestamp + WAITING_ORDERS_CACHE_LIFETIME;

  if (!\database\setCacheExpirationTime($link, DONE_OR_CANCELED_LOG_CACHE_ID, $expirationTime) ||
    !\database\putDoneOrCanceledLogCache($link, $orders)
  ) {
    \database\rollbackTransaction($link);
    return false;
  }
  return \database\commitTransaction($link);
}

function getWaitingOrders($userId,
                          $sinceTime, $sinceCustomerId, $sinceOrderId,
                          $untilTime, $untilCustomerId, $untilOrderId, $count) {
  $cacheDbInfo = getDbForCache($userId);

  if (getIfExists(CONFIG, 'use_cache') !== true || !$cacheDbInfo) {
    return \storage\getWaitingOrders($sinceTime, $sinceCustomerId, $sinceOrderId,
      $untilTime, $untilCustomerId, $untilOrderId, $count);
  }
  $since = $sinceTime ? [$sinceTime, $sinceCustomerId, $sinceOrderId] : null;
  $until = $untilTime ? [$untilTime, $untilCustomerId, $untilOrderId] : null;
  $link = \storage\connect($cacheDbInfo);

  if (!$link) {
    return null;
  }
  $timestamp = \database\getTimestamp($link);

  if (!$timestamp) {
    return null;
  }
  $ordersFromCache = getCachedWaitingOrders($link, $since, $until, $count, $timestamp);

  if (!is_null($ordersFromCache) && $ordersFromCache !== false) {
    return $ordersFromCache;
  }
  if ($ordersFromCache !== false) {
    $orders = \storage\getWaitingOrders(0, 0, 0, 0, 0, 0, WAITING_ORDERS_CACHE_SIZE);

    if (is_null($orders)) {
      return null;
    }
    if (!cacheWaitingOrders($link, $orders, $timestamp)) {
      logError('cannot cache waiting orders');
    }
    $filteredOrders = filterWaitingOrders($orders, $since, $until, $count);

    if ($filteredOrders !== false) {
      return $filteredOrders;
    }
  }
  return \storage\getWaitingOrders($sinceTime, $sinceCustomerId, $sinceOrderId,
    $untilTime, $untilCustomerId, $untilOrderId, $count);
}


function getCachedWaitingOrders($link, $since, $until, $count, $timestamp) {
  $expirationTime = \database\getCacheExpirationTime($link, WAITING_ORDERS_CACHE_ID);

  if (!$expirationTime || $timestamp > $expirationTime) {
    return null;
  }
  $cachedOrders = \database\getWaitingOrdersCache($link);
  return is_null($cachedOrders) ? null :
    filterWaitingOrders($cachedOrders, $since, $until, $count);
}

function filterWaitingOrders($orders, $since, $until, $count) {
  $started = false;
  $result = [];

  foreach ($orders as $order) {
    $key = buildKey($order);

    if (!$started && (!$until || compare($key, $until) < 0)) {
      $started = true;
    }
    if ($started) {
      if ($since && compare($key, $since) <= 0) {
        return $result;
      }
      array_push($result, $order);

      if (sizeof($result) == $count) {
        return $result;
      }
    }
  }
  return WAITING_ORDERS_CACHE_SIZE == sizeof($orders) ? false : $result;
}

function cacheWaitingOrders($link, $orders, $timestamp) {
  if (!\database\beginTransaction($link)) {
    return false;
  }
  $expirationTime = $timestamp + WAITING_ORDERS_CACHE_LIFETIME;

  if (!\database\setCacheExpirationTime($link, WAITING_ORDERS_CACHE_ID, $expirationTime) ||
    !\database\putWaitingOrdersCache($link, $orders)
  ) {
    \database\rollbackTransaction($link);
    return false;
  }
  return \database\commitTransaction($link);
}

function buildKey($order) {
  return [$order['time'], $order['customer_id'], $order['order_id']];
}

function compare($arr1, $arr2) {
  for ($i = 0; $i < sizeof($arr1); $i++) {
    $e1 = $arr1[$i];
    $e2 = $arr2[$i];

    if ($e1 < $e2) {
      return -1;
    } else if ($e2 < $e1) {
      return 1;
    }
  }
  return 0;
}