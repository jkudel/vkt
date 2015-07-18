function loadOrdersForExecutor(reload, count) {
  if (reload) {
    removeAllFromFeed();
  }

  var successCallback = function (response) {
    appendLoadedOrdersToFeed(response);
  };
  var errorCallback = function (errorMessage) {
    var errorPlaceholder = $('#main-error-placeholder');
    errorPlaceholder.text(errorMessage);
    errorPlaceholder.show();
  };
  var done = viewMode == 'done';
  var params = buildParamsOlderThanLastOrder(done ? 'done_time' : 'time');

  if (count) {
    params['count'] = count;
  }
  if (done) {
    params['done'] = 1;
    ajaxGetMyOrders(params, successCallback, errorCallback);
  } else {
    ajaxGetWaitingOrders(params, successCallback, errorCallback);
  }
}

function loadNewWaitingOrders() {
  var params = buildParamsNewerThanFirstOrder('time');

  ajaxGetWaitingOrders(params, function (response) {
    $('#show-new-orders').hide();
    prependLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    $('#main-error-placeholder').text(errorMessage);
  });
}

function executeOrder(orderId, price, orderBlock, link) {
  var errorPlaceholder = link.prevAll('.error-placeholder');
  link.before('<div class="progress"></div>');
  var progress = link.prevAll('.progress');
  initProgress(progress);

  ajaxExecuteOrder(orderId, function () {
    progress.remove();
    var balanceElement = $('#balance');
    var delta = (parseFloat(price) * (1 - getCommonConstant('commission')));
    var newBalance = parseFloat(balanceElement.text()) + delta;
    balanceElement.text(newBalance.toFixed(2));
    removeOrderBlock(orderBlock, orderId);
    loadOrdersForExecutor(false, 1);
  }, function (errorMessage, errorCode) {
    progress.remove();

    if (errorCode == ERROR_CODE_NO_OBJECT) {
      errorMessage = msg('order.canceled.error');
    }
    errorPlaceholder.text(errorMessage);
    errorPlaceholder.show();
  });
}

function applyUpdates(response) {
  if (viewMode != 'available') {
    return;
  }
  var newOrdersCount = response['new_orders_count'];
  var showNewOrders = $('#show-new-orders');

  if (newOrdersCount) {
    var newOrdersNotification;

    if (response['new_orders_has_more']) {
      newOrdersNotification = msg('new.orders.available') + ' ' + newOrdersCount + ' ' + msg('or.more');
    } else {
      newOrdersNotification = msg('new.orders.available') + ' ' + newOrdersCount;
    }
    showNewOrders.text(newOrdersNotification);
    showNewOrders.show();
  } else {
    showNewOrders.hide();
  }
  var doneOrCanceled = response['done_or_canceled'];

  if (doneOrCanceled) {
    var removedCount = removeOrdersFromFeed(doneOrCanceled);

    if (removedCount > 0) {
      loadOrdersForExecutor(false, removedCount);
    }
  }
}

function removeOrdersFromFeed(orderIds) {
  var ordersToRemove = {};

  for (var i = 0; i < orderIds.length; i++) {
    ordersToRemove[orderIds[i]] = true;
  }
  var result = 0;

  $('#orders').children().each(function () {
    var executeButton = $(this).find('.execute-order-button');
    var orderId = executeButton.data('order-id');

    if (ordersToRemove[orderId]) {
      removeOrderBlock($(this), orderId);
      result++;
    }
  });
  return result;
}

function scheduleCheckingUpdatesForExecutor() {
  setTimeout(function () {
    if (viewMode != 'available') {
      scheduleCheckingUpdatesForExecutor();
      return;
    }
    var params = buildParamsNewerThanFirstOrder('time');

    ajaxCheckForUpdates(params, function (response) {
      applyUpdates(response);
      scheduleCheckingUpdatesForExecutor();
    }, function (errorMessage) {
      var errorPlaceholder = $('#main-error-placeholder');
      errorPlaceholder.text(errorMessage);
      errorPlaceholder.show();
      scheduleCheckingUpdatesForExecutor();
    });
  }, 5000);
}

buildOrderBlockInFeed = function(data) {
  var addToBottomPanel = '';

  if (!data['done_time']) {
    var executeButton =
      '<input class="button execute-order-button" type="button" ' +
      'data-order-id="' + data['order_id'] + '" ' +
      'data-order-price="' + data['price'] + '" ' +
      'value="'+ msg('execute.order') +'"/>';
    addToBottomPanel = '<div class="action-panel"><span class="error-placeholder"></span>' +
    executeButton + '</div>';
  }
  return buildBaseOrderBlock(data, true, false, addToBottomPanel);
};

reloadAll = function () {
  loadOrdersForExecutor(true);
};

$(document).ready(function () {
  init('available');

  $('#orders').on('click', '.execute-order-button', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    executeOrder(link.data('order-id'), link.data('order-price'), link.parents('.order'), link);
  });
  $('#show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForExecutor(false);
  });
  $('#show-new-orders').click(function (e) {
    e.preventDefault();
    loadNewWaitingOrders();
  });
  scheduleCheckingUpdatesForExecutor();
});