const AJAX_REGISTER = 'ajax/register.php';
const AJAX_LOGIN = 'ajax/login.php';
const AJAX_CREATE_ORDER = 'ajax/create_order.php';

function handleSuccessResponse(response, successCallback, errorCallback) {
  var errorMessage = response['error_message'];
  var errorCode = response['error_code'];

  if (errorMessage || errorCode) {
    errorCallback(errorMessage, errorCode, response);
  } else {
    successCallback(response);
  }
}

function handleErrorResponse(xhr, error, errorCallback) {
  errorCallback(msg('internal.error'));
  var responseTest = xhr['responseText'];

  if (responseTest) {
    console.error(responseTest);
  }
  console.error(error);
}

/**
 * Params:
 *   done, count,
 *   lw_time, lw_order_id,
 *   up_time, up_order_id
 */
function ajaxGetMyOrders(params, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/get_my_orders.php' + buildParamsStr(params),
    type: "GET",
    dataType: "json",
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}

/**
 * Params:
 *   count,
 *   lw_time, lw_order_id,
 *   up_time, up_order_id
 */
function ajaxGetWaitingOrders(params, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/get_waiting_orders.php' + buildParamsStr(params),
    type: "GET",
    dataType: "json",
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}

function ajaxCancelOrder(orderId, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/cancel_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'order_id': orderId
    },
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}

function ajaxExecuteOrder(orderId, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/execute_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'order_id': orderId,
    },
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}

function ajaxSubmitForm(url, form, successCallback, errorCallback) {
  $.ajax({
    url: url,
    type: "POST",
    dataType: "json",
    data: form.serialize(),
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}

function ajaxCheckForUpdates(params, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/check_updates.php' + buildParamsStr(params),
    type: "GET",
    dataType: "json",
    success: function (response) {
      handleSuccessResponse(response, successCallback, errorCallback);
    },
    error: function (xhr, status, error) {
      handleErrorResponse(xhr, error, errorCallback);
    }
  });
}