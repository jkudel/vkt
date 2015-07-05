<?php

function get_if_exists(array $array, $key) {
  return array_key_exists($key, $array) ? $array[$key] : null;
}

function log_info($message) {
  log_message($message, debug_backtrace(0, 2), 'INFO');
}

function log_error($message) {
  log_message($message, debug_backtrace(0, 2), 'ERROR');
}

function log_message($message, $trace, $level) {
  $traceElement = sizeof($trace) == 1 ? $trace[0] : $trace[1];
  $file = get_relative_path($_SERVER['DOCUMENT_ROOT'], $traceElement['file']);
  error_log($level . ' at ' . $file . ':' . $traceElement['line'] . ': ' . $message);
}

function get_relative_path($ancestorPath, $path) {
  $ancestorPath = str_replace('\\', '/', $ancestorPath);
  $path = str_replace('\\', '/', $path);
  return start_with($path, $ancestorPath) ? substr($path, strlen($ancestorPath)) : null;
}

function start_with($s, $substring) {
  return substr($s, 0, strlen($substring)) === $substring;
}