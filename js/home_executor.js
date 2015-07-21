function loadNewWaitingOrders(showNewLink) {
  var progress = showNewLink.next('.progress');

  if (progress.length > 0) {
    return;
  }
  showNewLink.after('<div class="progress"></div>');
  progress = initProgress(showNewLink.next());
  var params = buildParamsNewerThanOrders('time');

  scheduleFeedAction(function (runAfter, canceledFunc) {
    if (canceledFunc()) {
      return;
    }
    ajaxGetWaitingOrders(params, function (response) {
      if (canceledFunc()) {
        return;
      }
      progress.remove();
      showNewLink.hide();
      prependLoadedOrdersToFeed(response);
      runAfter();
    }, function (errorMessage) {
      if (canceledFunc()) {
        return;
      }
      progress.remove();
      var errorPlaceholder = $('#top-error-placeholder');
      errorPlaceholder.show();
      errorPlaceholder.text(errorMessage);
      runAfter();
    });
  }, function () {
    progress.remove();
  });
}

function executeOrder(orderId, orderBlock, link) {
  var errorPlaceholder = link.prevAll('.error-placeholder');
  var progress = link.prev('.progress');

  if (progress.length > 0) {
    return;
  }
  link.before('<div class="progress"></div>');
  progress = initProgress(link.prev());

  scheduleFeedAction(function (runAfter, canceledFunc) {
    if (canceledFunc()) {
      return;
    }
    ajaxExecuteOrder(orderId, function (response) {
      if (canceledFunc()) {
        return;
      }
      progress.remove();
      $('#balance').text(response['balance'] + ' ' + msg('currency'));
      removeOrderBlock(orderBlock, orderId);
      loadUnderProgress(false, 1);
      runAfter();
    }, function (errorMessage, errorCode) {
      if (canceledFunc()) {
        return;
      }
      progress.remove();

      if (errorCode == ERROR_CODE_NO_OBJECT) {
        errorMessage = msg('already.executed.or.canceled.error');
      }
      errorPlaceholder.text(errorMessage);
      errorPlaceholder.show();
      runAfter();
    });
  }, function () {
    progress.remove();
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
      loadUnderProgress(false, removedCount);
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
    scheduleFeedAction(function (runAfter, canceledFunc) {
      if (canceledFunc()) {
        return;
      }
      if (viewMode != 'available') {
        scheduleCheckingUpdatesForExecutor();
        runAfter();
        return;
      }
      var params = buildParamsNewerThanOrders('time');

      ajaxCheckForUpdates(params, function (response) {
        if (canceledFunc()) {
          return;
        }
        applyUpdates(response);
        scheduleCheckingUpdatesForExecutor();
        runAfter();
      }, function (errorMessage) {
        if (canceledFunc()) {
          return;
        }
        console.log(errorMessage);
        scheduleCheckingUpdatesForExecutor();
        runAfter();
      });
    }, function() {
      scheduleCheckingUpdatesForExecutor();
    });
  }, 1000);
}

buildOrderBlockInFeed = function (data) {
  var addToBottomPanel = '';

  if (!data['done_time']) {
    var executeButton =
      '<input class="button execute-order-button" type="button" ' +
      'data-order-id="' + data['order_id'] + '" ' +
      'value="' + msg('execute.order') + '"/>';
    addToBottomPanel = '<div class="action-panel"><span class="error-placeholder"></span>' +
    executeButton + '</div>';
  }
  return buildBaseOrderBlock(data, true, false, addToBottomPanel);
};

loadOrders = function (reload, count, errorCallback, canceledFunc, callback) {
  var successCallback = function (response) {
    if (canceledFunc()) {
      return;
    }
    callback();
    appendLoadedOrdersToFeed(response);
  };
  var done = viewMode == 'done';
  var params = buildParamsOlderThanOrders(done ? 'done_time' : 'time');

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
  var viewModeButtons = init('available');

  viewModeButtons.click(function () {
    $('#show-new-orders').hide();
  });
  $('#orders').on('click', '.execute-order-button', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    executeOrder(link.data('order-id'), link.parents('.order'), link);
  });
  $('#show-new-orders').click(function (e) {
    e.preventDefault();
    clearErrors();
    loadNewWaitingOrders($(this));
  });
  scheduleCheckingUpdatesForExecutor();
});