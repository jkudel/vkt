var autoUpdateEnabled = true;
var autoUpdateCanceled = false;

function loadNewWaitingOrders(element) {
  element.after('<div class="progress"></div>');
  var progress = initProgress(element.next());
  var params = buildParamsNewerThanFirstOrder('time');

  ajaxGetWaitingOrders(params, function (response) {
    element.hide();
    progress.remove();
    prependLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    progress.remove();
    $('#main-error-placeholder').text(errorMessage);
  });
}

function executeOrder(orderId, price, orderBlock, link) {
  var errorPlaceholder = link.prevAll('.error-placeholder');
  link.before('<div class="progress"></div>');
  var progress = initProgress(link.prev());

  ajaxExecuteOrder(orderId, function () {
    progress.remove();
    var balanceElement = $('#balance');
    var delta = (parseFloat(price) * (1 - getCommonConstant('commission')));
    var newBalance = parseFloat(balanceElement.text()) + delta;
    balanceElement.text(newBalance.toFixed(2));
    removeOrderBlock(orderBlock, orderId);
    reload(false, 1);
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
      reload(false, removedCount);
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
    if (!autoUpdateEnabled || viewMode != 'available') {
      scheduleCheckingUpdatesForExecutor();
      return;
    }
    autoUpdateCanceled = false;
    var params = buildParamsNewerThanFirstOrder('time');

    ajaxCheckForUpdates(params, function (response) {
      if (!autoUpdateCanceled) {
        applyUpdates(response);
      }
      scheduleCheckingUpdatesForExecutor();
    }, function (errorMessage) {
      if (!autoUpdateCanceled) {
        var errorPlaceholder = $('#main-error-placeholder');
        errorPlaceholder.text(errorMessage);
        errorPlaceholder.show();
      }
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

reload = function (reload, count, errorPlaceholder, runAfter) {
  if (reload) {
    autoUpdateEnabled = false;
    autoUpdateCanceled = true;
    removeAllFromFeed();
  }
  var successCallback = function (response) {
    appendLoadedOrdersToFeed(response);

    if (runAfter) {
      runAfter();
    }
    if (reload) {
      autoUpdateEnabled = true;
    }
  };
  var errorCallback = function (errorMessage) {
    if (!errorPlaceholder) {
      errorPlaceholder = $('#main-error-placeholder');
    }
    errorPlaceholder.text(errorMessage);
    errorPlaceholder.show();

    if (runAfter) {
      runAfter();
    }
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
};

$(document).ready(function () {
  init('available');

  $('#orders').on('click', '.execute-order-button', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    executeOrder(link.data('order-id'), link.data('order-price'), link.parents('.order'), link);
  });
  $('#show-new-orders').click(function (e) {
    e.preventDefault();
    loadNewWaitingOrders($(this));
  });
  scheduleCheckingUpdatesForExecutor();
});