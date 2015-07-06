<?php
include 'messages.php';

function getIfExists(array $array, $key) {
  return array_key_exists($key, $array) ? $array[$key] : null;
}

function logInfo($message) {
  logMessage($message, debug_backtrace(0, 2), 'INFO');
}

function logError($message) {
  logMessage($message, debug_backtrace(0, 2), 'ERROR');
}

function logMessage($message, $trace, $level) {
  $traceElement = sizeof($trace) == 1 ? $trace[0] : $trace[1];
  $file = getRelativePath($_SERVER['DOCUMENT_ROOT'], $traceElement['file']);
  error_log($level . ' at ' . $file . ':' . $traceElement['line'] . ': ' . $message);
}

function getRelativePath($ancestorPath, $path) {
  $ancestorPath = str_replace('\\', '/', $ancestorPath);
  $path = str_replace('\\', '/', $path);
  return startsWith($path, $ancestorPath) ? substr($path, strlen($ancestorPath)) : null;
}

function startsWith($s, $substring) {
  return substr($s, 0, strlen($substring)) === $substring;
}

function msg($key) {
  $value = getIfExists(MESSAGES, $key);

  if (is_null($value)) {
    logError('unknown message key ' . $key);
    return 'unknown';
  } else {
    return $value;
  }
}