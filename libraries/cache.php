<?php
namespace cache;

const FEED_CACHE_ID = 1;
const DONE_OR_CANCELED_LOG_CACHE_ID = 2;

const FEED_CACHE_LIFETIME = 2;
const FEED_CACHE_SIZE = 100;
const DONE_OR_CANCELED_LOG_CACHE_LIFETIME = 2;

function getDoneOrCanceledLog($userId, $lwTime) {
  $cacheDbInfo = getDbForCache($userId);

  if (getIfExists(CONFIG, 'use_cache') !== true || !$cacheDbInfo) {
    return \storage\getDoneOrCanceledLog($lwTime);
  }
  $link = \storage\connect($cacheDbInfo);

  if (!$link) {
    return null;
  }
  $timestamp = \database\getTimestamp($link);

  if (!$timestamp) {
    return null;
  }
  $ordersFromCache = getCachedDoneOrCanceledLog($link, $lwTime, $timestamp);

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
  if (!\database\setCacheExpirationTime($link, FEED_CACHE_ID, 0)) {
    logError('cannot invalidate feed cache');
  }
  return $orders;
}

function getCachedDoneOrCanceledLog($link, $lwTime, $timestamp) {
  $expirationTime = \database\getCacheExpirationTime($link, DONE_OR_CANCELED_LOG_CACHE_ID);

  if (!$expirationTime || $timestamp > $expirationTime) {
    return null;
  }
  $cachedOrders = \database\getDoneOrCanceledLogCache($link);
  return is_null($cachedOrders) ? null :
    filterDoneOrCanceledLog($cachedOrders, $lwTime);
}

function filterDoneOrCanceledLog($orders, $lwTime) {
  $result = [];

  foreach ($orders as $order) {
    if ($order['time'] <= $lwTime) {
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
  $expirationTime = $timestamp + DONE_OR_CANCELED_LOG_CACHE_LIFETIME;

  if (!\database\setCacheExpirationTime($link, DONE_OR_CANCELED_LOG_CACHE_ID, $expirationTime) ||
    !\database\putDoneOrCanceledLogCache($link, $orders)
  ) {
    \database\rollbackTransaction($link);
    return false;
  }
  return \database\commitTransaction($link);
}

function getWaitingOrders($userId,
                          $lwTime, $lwCustomerId, $lwOrderId,
                          $upTime, $upCustomerId, $upOrderId, $count) {
  $cacheDbInfo = getDbForCache($userId);

  if (getIfExists(CONFIG, 'use_cache') !== true || !$cacheDbInfo) {
    return \storage\getWaitingOrders($lwTime, $lwCustomerId, $lwOrderId,
      $upTime, $upCustomerId, $upOrderId, $count);
  }
  $lowerBound = $lwTime ? [$lwTime, $lwCustomerId, $lwOrderId] : null;
  $upperBound = $upTime ? [$upTime, $upCustomerId, $upOrderId] : null;
  $link = \storage\connect($cacheDbInfo);

  if (!$link) {
    return null;
  }
  $timestamp = \database\getTimestamp($link);

  if (!$timestamp) {
    return null;
  }
  $ordersFromCache = getCachedWaitingOrders($link, $lowerBound, $upperBound, $count, $timestamp);

  if (!is_null($ordersFromCache) && $ordersFromCache !== false) {
    return $ordersFromCache;
  }
  if ($ordersFromCache !== false) {
    $orders = \storage\getWaitingOrders(0, 0, 0, 0, 0, 0, FEED_CACHE_SIZE);

    if (is_null($orders)) {
      return null;
    }
    if (!cacheWaitingOrders($link, $orders, $timestamp)) {
      logError('cannot cache waiting orders');
    }
    $filteredOrders = filterWaitingOrders($orders, $lowerBound, $upperBound, $count);

    if ($filteredOrders !== false) {
      return $filteredOrders;
    }
  }
  return \storage\getWaitingOrders($lwTime, $lwCustomerId, $lwOrderId,
    $upTime, $upCustomerId, $upOrderId, $count);
}


function getCachedWaitingOrders($link, $lowerBound, $upperBound, $count, $timestamp) {
  $expirationTime = \database\getCacheExpirationTime($link, FEED_CACHE_ID);

  if (!$expirationTime || $timestamp > $expirationTime) {
    return null;
  }
  $cachedOrders = \database\getFeedCache($link);
  return is_null($cachedOrders) ? null :
    filterWaitingOrders($cachedOrders, $lowerBound, $upperBound, $count);
}

function filterWaitingOrders($orders, $lowerBound, $upperBound, $count) {
  $started = false;
  $result = [];

  foreach ($orders as $order) {
    $key = buildKey($order);

    if (!$started && (!$upperBound || compare($key, $upperBound) < 0)) {
      $started = true;
    }
    if ($started) {
      if ($lowerBound && compare($key, $lowerBound) <= 0) {
        return $result;
      }
      array_push($result, $order);

      if (sizeof($result) == $count) {
        return $result;
      }
    }
  }
  return FEED_CACHE_SIZE == sizeof($orders) ? false : $result;
}

function cacheWaitingOrders($link, $orders, $timestamp) {
  if (!\database\beginTransaction($link)) {
    return false;
  }
  $expirationTime = $timestamp + FEED_CACHE_LIFETIME;

  if (!\database\setCacheExpirationTime($link, FEED_CACHE_ID, $expirationTime) ||
    !\database\putFeedCache($link, $orders)
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