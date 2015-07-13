function loadOrdersForExecutor(reload, count) {
  if (reload) {
    removeAllFromFeed();
  }
  var viewMode = $('#view-mode');

  var successCallback = function (response) {
    appendLoadedOrdersToFeed(response);
  };
  var errorCallback = function (errorMessage) {
    viewMode.next('.error-placeholder').text(errorMessage);
  };
  var done = viewMode.val() == 'done';
  var params = buildParamsUntilLastOrder(done ? 'done_time' : 'time');

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
  var params = buildParamsSinceFirstOrder('time');

  ajaxGetWaitingOrders(params, function (response) {
    $('#show-new-orders').hide();
    prependLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    $('#view-mode').next('.error-placeholder').text(errorMessage);
  });
}

function executeOrder(orderId, price, orderBlock, errorPlaceholder) {
  ajaxExecuteOrder(orderId, function () {
    var balanceElement = $('#balance');
    var delta = (parseFloat(price) * (1 - getCommission()));
    var newBalance = parseFloat(balanceElement.text()) + delta;
    balanceElement.text(newBalance.toFixed(2));
    removeOrderBlock(orderBlock, orderId);
    loadOrdersForExecutor(false, 1);
  }, function (errorMessage, errorCode) {
    if (errorCode == ERROR_CODE_NO_OBJECT) {
      errorMessage = msg('order.canceled.error');
    }
    errorPlaceholder.text(errorMessage);
  });
}

function applyUpdates(response) {
  if ($('#view-mode').val() != 'available') {
    return;
  }
  var newOrdersCount = response['new_orders_count'];
  var showNewOrders = $('#show-new-orders');

  if (newOrdersCount) {
    if (response['new_orders_show_more']) {
      showNewOrders.text(newOrdersCount + msg('more.new.orders.available'));
    } else {
      showNewOrders.text(newOrdersCount + msg('new.orders.available'));
    }
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

function scheduleCheckingUpdatesForExecutor() {
  setTimeout(function () {
    if ($('#view-mode').val() != 'available') {
      scheduleCheckingUpdatesForExecutor();
      return;
    }
    var params = buildParamsSinceFirstOrder('time');

    ajaxCheckForUpdates(params, function (response) {
      applyUpdates(response);
      scheduleCheckingUpdatesForExecutor();
    }, function (errorMessage) {
      $('#view-mode').next('.error-placeholder').text(errorMessage);
      scheduleCheckingUpdatesForExecutor();
    });
  }, 5000);
}

buildOrderBlockInFeed = function(data) {
  var html = buildBaseOrderBlock(data, true);

  if (data['done_time']) {
    return html;
  }
  var executeButton =
    '<a class="execute-order-link" href="#" ' +
    'data-order-id="' + data['order_id'] + '" ' +
    'data-order-price="' + data['price'] + '">' +
    msg('execute.order') + '</a>';
  return '<div>' + html +
    '<div>' +
    executeButton +
    '<span class="error-placeholder"></span>' +
    '</div></div>';
};

$(document).ready(function () {
  initViewModeChooser('available', function () {
    loadOrdersForExecutor(true);
  });
  $('#orders').on('click', '.execute-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    executeOrder(link.data('order-id'), link.data('order-price'), orderBlock, link.next('span'));
  });
  $('#show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForExecutor(false);
  });
  $('#show-new-orders').click(function (e) {
    e.preventDefault();
    loadNewWaitingOrders();
  });
  loadOrdersForExecutor(false);
  scheduleCheckingUpdatesForExecutor();
});