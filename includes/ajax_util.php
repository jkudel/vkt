<?php
require_once('util.php');

const ERROR_VALIDATION = 0;
const ERROR_INTERNAL = 1;
const NOT_AUTH_ERROR = 2;

function internalErrorResponse() {
  echo json_encode(['error_message' => msg('internal.error'), 'error_code' => ERROR_INTERNAL]);
}

function notAuthErrorResponse() {
  echo json_encode(['error_message' => msg('not.auth.error'), 'error_code' => NOT_AUTH_ERROR]);
}

function validationErrorResponse($message, $fieldName = null) {
  $arr = ['error_message' => $message, 'error_code' => ERROR_VALIDATION];

  if (!is_null($fieldName)) {
    $arr['field_name'] = $fieldName;
  }
  echo json_encode($arr);
}

function successResponse() {
  echo json_encode(['success' => 'true']);
}