<?php
require_once 'messages.php';

function getIfExists(array $array, $key) {
  return array_key_exists($key, $array) ? $array[$key] : null;
}

function logInfo($message) {
  logMessage($message, debug_backtrace(0, 3), 'INFO');
}

function logError($message) {
  logMessage($message, debug_backtrace(0, 8), 'ERROR');
}

function logMessage($message, $trace, $level) {
  $traceStr = '';
  $i = 0;

  foreach ($trace as $traceElement) {
    if (sizeof($trace) == 1 || $i > 0) {
      $absolutePath = $traceElement['file'];
      $file = getRelativePath($_SERVER['DOCUMENT_ROOT'], $absolutePath);

      if (is_null($file)) {
        $file = $absolutePath;
      }
      $traceStr .= "\n    " . $file . ':' . $traceElement['line'] . ' ' . $traceElement['function'];
    }
    $i++;
  }
  error_log($level . ': ' . $message . $traceStr);
}

function getRelativePath($ancestorPath, $path) {
  $ancestorPath = str_replace('\\', '/', $ancestorPath);
  $path = str_replace('\\', '/', $path);
  return startsWith($path, $ancestorPath) ? substr($path, strlen($ancestorPath)) : null;
}

function startsWith($s, $substring) {
  return substr($s, 0, strlen($substring)) === $substring;
}

function endsWidth($s, $substring) {
  $l = strlen($substring);
  return substr($s, strlen($s) - $l, $l) === $substring;
}

function msg($key, ...$params) {
  $format = getIfExists(MESSAGES, $key);

  if (is_null($format)) {
    logError('unknown message key ' . $key);
    return 'unknown';
  }
  $patterns = array_map(function ($index) {
    return '{' . $index . '}';
  }, array_keys($params));
  return str_replace($patterns, $params, $format);
}