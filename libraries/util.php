<?php

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
      $absolutePath = getIfExists($traceElement, 'file');

      if (is_null($absolutePath)) {
        $file = 'unknown';
      } else {
        $file = getRelativePath($_SERVER['DOCUMENT_ROOT'], $absolutePath);

        if (is_null($file)) {
          $file = $absolutePath;
        }
      }
      $line = getIfExists($traceElement, 'line');

      if (is_null($line)) {
        $line = 'unknown';
      }
      $function = getIfExists($traceElement, 'function');

      if (is_null($function)) {
        $function = 'unknown';
      }
      $traceStr .= "\n    " . $file . ':' . $line . ' ' . $function;
    }
    $i++;
  }
  error_log($level . ': ' . $message . $traceStr);
}

function getRelativePath($ancestorPath, $path) {
  $ancestorPath = str_replace('\\', '/', $ancestorPath);
  $path = str_replace('\\', '/', $path);
  return startsWith($path, $ancestorPath) ? mb_substr($path, mb_strlen($ancestorPath)) : null;
}

function startsWith($s, $substring) {
  return mb_substr($s, 0, mb_strlen($substring)) === $substring;
}

function endsWidth($s, $substring) {
  $l = mb_strlen($substring);
  return mb_substr($s, mb_strlen($s) - $l, $l) === $substring;
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

function printJsArrayContent($array) {
  $i = 0;
  foreach ($array as $key => $value) {
    if ($i > 0) {
      echo ',';
    }
    echo '"' . $key . '":"' . $value . '"';
    $i++;
  }
}

function mergeSortedArrays($arrays, $removeDuplicates, $asc, $func) {
  $result = [];
  $indexes = array_fill(0, sizeof($arrays), 0);

  while (true) {
    $bestValue = null;
    $bestElement = null;
    $bestArrayIndex = null;
    $i = 0;

    foreach ($arrays as $arr) {
      $index = $indexes[$i];
      $element = getIfExists($arr, $index);

      if (!is_null($element)) {
        $value = $func($element);

        if (is_null($bestElement) || ($asc && $value < $bestValue) || (!$asc && $value > $bestValue)) {
          $bestElement = $element;
          $bestValue = $value;
          $bestArrayIndex = $i;
        }
      }
      $i++;
    }
    if (is_null($bestElement)) {
      break;
    } else {
      $indexes[$bestArrayIndex]++;
      $lastElement = getIfExists($result, sizeof($result) - 1);

      if (!$removeDuplicates || is_null($lastElement) || $func($lastElement) != $bestValue) {
        array_push($result, $bestElement);
      }
    }
  }
  return $result;
}

function jsonEncode($value) {
  $result = json_encode($value);

  if ($result === false) {
    logError('cannot encode json: ' . json_last_error() . ': ' . json_last_error_msg());
    return null;
  }
  return $result;
}