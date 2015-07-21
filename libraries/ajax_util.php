<?php
const ERROR_VALIDATION = 0;
const ERROR_INTERNAL = 1;
const NOT_AUTH_ERROR = 2;
const NO_OBJECT = 3;

function internalErrorResponse() {
  echo jsonEncode(['error_message' => msg('internal.error'), 'error_code' => ERROR_INTERNAL]);
}

function noObjectErrorResponse() {
  echo jsonEncode(['error_message' => msg('internal.error'), 'error_code' => NO_OBJECT]);
}

function notAuthErrorResponse() {
  echo jsonEncode(['error_message' => msg('not.auth.error'), 'error_code' => NOT_AUTH_ERROR]);
}

function validationErrorResponse($message, $fieldName = null) {
  $arr = ['error_message' => $message, 'error_code' => ERROR_VALIDATION];

  if (!is_null($fieldName)) {
    $arr['field_name'] = $fieldName;
  }
  echo jsonEncode($arr);
}

function successResponse() {
  echo jsonEncode(['success' => 'true']);
}