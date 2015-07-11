function loadOrdersForExecutor(reload) {
  var ordersBlock = $('#orders');

  if (reload) {
    ordersBlock.html('');
    lastLoadedOrderId = 0;
  }
  var viewMode = $('#view-mode');

  var successCallback = function (response) {
    var lastOrderId = appendHtmlForOrders(response, ordersBlock, buildOrderBlockForExecutor);

    if (lastOrderId) {
      lastLoadedOrderId = lastOrderId;
    }
  };
  var errorCallback = function (errorMessage) {
    viewMode.next('.error-placeholder').text(errorMessage);
  };
  if (viewMode.val() == 'done') {
    ajaxGetMyOrders(0, lastLoadedOrderId, true, successCallback, errorCallback);
  } else {
    ajaxGetWaitingOrders(0, lastLoadedOrderId, successCallback, errorCallback);
  }
}

function buildOrderBlockForExecutor(data) {
  var html = buildBaseOrderBlock(data);

  if (data['done_time']) {
    return html;
  }
  var executeButton =
    '<a class="execute-order-link" href="#" ' +
    'data-order-id="' + data['id'] + '" ' +
    'data-order-price="' + data['price'] + '">' +
    msg('execute.order') + '</a>';
  return '<div>' + html +
    '<div>' +
    executeButton +
    '<span class="error-placeholder"></span>' +
    '</div></div>';
}

function executeOrder(orderId, price, orderBlock, errorPlaceholder) {
  ajaxExecuteOrder(orderId, function () {
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
  initViewModeChooser(function () {
    loadOrdersForExecutor(true);
  });
  $('#orders').on('click', '.execute-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    executeOrder(link.data('order-id'), link.data('order-price'), orderBlock, link.next('span'));
  });
  $('.show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForExecutor(false);
  });
  loadOrdersForExecutor(false);
  //setInterval(loadOrdersForExecutor, 5000);
});