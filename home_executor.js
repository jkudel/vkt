function loadOrdersForExecutor(reload) {
  var ordersBlock = $('#orders');

  if (reload) {
    ordersBlock.html('');
    lastLoadedOrder = null;
    loadedOrders = {};
  }
  var viewMode = $('#view-mode');

  var successCallback = function (response) {
    appendLoadedOrders(response, ordersBlock, buildOrderBlockForExecutor);
  };
  var errorCallback = function (errorMessage) {
    viewMode.next('.error-placeholder').text(errorMessage);
  };
  var params = lastLoadedOrder ? buildUntilParamsByOrder(lastLoadedOrder) : {};

  if (viewMode.val() == 'done') {
    params['done'] = 1;
    ajaxGetMyOrders(params, successCallback, errorCallback);
  } else {
    ajaxGetWaitingOrders(params, successCallback, errorCallback);
  }
}

function buildOrderBlockForExecutor(data) {
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
}

function executeOrder(orderId, customerId, price, orderBlock, errorPlaceholder) {
  ajaxExecuteOrder(orderId, customerId, function () {
    var balanceElement = $('#balance');
    var delta = (parseFloat(price) * (1 - getCommission()));
    var newBalance = parseFloat(balanceElement.text()) + delta;
    balanceElement.text(newBalance.toFixed(2));
    orderBlock.remove();
  }, function (errorMessage) {
    errorPlaceholder.text(errorMessage);
  });
}

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
  $('.show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForExecutor(false);
  });
  loadOrdersForExecutor(false);
  //setInterval(loadOrdersForExecutor, 5000);
});