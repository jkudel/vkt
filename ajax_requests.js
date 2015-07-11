const AJAX_REGISTER = 'ajax/register.php';
const AJAX_LOGIN = 'ajax/login.php';
const AJAX_CREATE_ORDER = 'ajax/create_order.php';

function ajaxGetMyOrders(afterId, beforeId, done, successCallback, errorCallback) {
  var params = buildParamsStr({
    'after_id': afterId,
    'before_id': beforeId,
    'done': done
  });

  $.ajax({
    url: 'ajax/get_my_orders.php' + params,
    type: "GET",
    dataType: "json",
    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        errorCallback(errorMessage);
      } else {
        successCallback(response);
      }
    },
    error: function (response) {
      errorCallback(msg('internal.error'));
      console.error(response); // todo: better console logging
    }
  });
}

function ajaxGetWaitingOrders(afterId, beforeId, successCallback, errorCallback) {
  var params = buildParamsStr({
    'after_id': afterId,
    'before_id': beforeId
  });

  $.ajax({
    url: 'ajax/get_waiting_orders.php' + params,
    type: "GET",
    dataType: "json",
    success: successCallback,
    error: errorCallback
  });
}

function ajaxCancelOrder(orderId, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/cancel_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'id': orderId
    },
    success: successCallback,
    error: errorCallback
  });
}

function ajaxExecuteOrder(orderId, successCallback, errorCallback) {
  $.ajax({
    url: 'ajax/execute_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'id': orderId
    },
    success: successCallback,
    error: errorCallback
  });
}

function ajaxSubmitForm(url, form, successCallback, errorCallback) {
  $.ajax({
    url: url,
    type: "POST",
    dataType: "json",
    data: form.serialize(),
    success: successCallback,
    error: errorCallback
  });
}