function getFullKey(customerId, orderId) {
  return customerId + '_' + orderId;
}

function loadOrdersForExecutor(reload, count) {
  if (!count) {
    count = ORDER_LIST_PART_SIZE;
  }
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
  var params = buildUntilParamsByLastOrder();
  params['count'] = count;

  if (viewMode.val() == 'done') {
    params['done'] = 1;
    ajaxGetMyOrders(params, successCallback, errorCallback);
  } else {
    ajaxGetWaitingOrders(params, successCallback, errorCallback);
  }
}

function loadNewWaitingOrders() {
  var params = buildSinceParamsByFirstOrder();
  ajaxGetWaitingOrders(params, function (response) {
    $('#show-new-orders').hide();
    prependLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    $('#view-mode').next('.error-placeholder').text(errorMessage);
  });
}

function executeOrder(orderId, customerId, price, orderBlock, errorPlaceholder) {
  ajaxExecuteOrder(orderId, customerId, function () {
    var balanceElement = $('#balance');
    var delta = (parseFloat(price) * (1 - getCommission()));
    var newBalance = parseFloat(balanceElement.text()) + delta;
    balanceElement.text(newBalance.toFixed(2));
    removeOrderBlock(orderBlock, getFullKey(customerId, orderId));
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
  var doneOrExecutedOrders = response['done_or_executed'];

  if (doneOrExecutedOrders) {
    var removedCount = removeOrdersFromFeed(doneOrExecutedOrders);

    if (removedCount > 0) {
      loadOrdersForExecutor(false, removedCount);
    }
  }
}

function scheduleCheckingUpdates() {
  setTimeout(function () {
    if ($('#view-mode').val() != 'available') {
      scheduleCheckingUpdates();
      return;
    }
    var params = buildSinceParamsByFirstOrder();

    ajaxCheckForUpdates(params, function (response) {
      applyUpdates(response);
      scheduleCheckingUpdates();
    }, function (errorMessage) {
      $('#view-mode').next('.error-placeholder').text(errorMessage);
      scheduleCheckingUpdates();
    });
  }, 5000);
}

feedData.keyFunc = function (order) {
  return getFullKey(order['customer_id'], order['order_id']);
};

feedData.buildBlockFunc = function(data) {
  var html = buildBaseOrderBlock(data, true);

  if (data['done_time']) {
    return html;
  }
  var executeButton =
    '<a class="execute-order-link" href="#" ' +
    'data-order-id="' + data['order_id'] + '" ' +
    'data-customer-id="' + data['customer_id'] + '" ' +
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
    executeOrder(link.data('order-id'), link.data('customer-id'),
      link.data('order-price'), orderBlock, link.next('span'));
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
  scheduleCheckingUpdates();
});