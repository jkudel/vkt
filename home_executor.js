
function loadOrdersForExecutor(reload) {
  var ordersBlock = $('#orders');

  if (reload) {
    ordersBlock.html('');
    lastLoadedOrderId = 0;
  }
  var viewMode = $('#view-mode');
  var url = viewMode.val() == 'done'
    ? 'ajax/get_my_orders.php'
    : 'ajax/get_waiting_orders.php';
  var errorPlaceholder = viewMode.prev('.error-placeholder');
  var params = '?before_id=' + lastLoadedOrderId;

  doLoadOrders(url + params, ordersBlock,
    errorPlaceholder, buildOrderBlockForExecutor, function (lastOrderId) {
      if (lastOrderId) {
        lastLoadedOrderId = lastOrderId;
      }
    });
}

function buildOrderBlockForExecutor(data) {
  var html = buildBaseOrderBlock(data);

  if (data['done_time']) {
    return html;
  }
  var executeButton =
    '<a class="execute-order-link" href="#" ' +
    'data-order-id="' + data['id'] + '" '+
    'data-order-price="' + data['price'] + '">' +
    msg('execute.order') + '</a>';
  return html +
    '<div>' +
    executeButton +
    '<span class="error-placeholder"></span>' +
    '</div>';
}

function executeOrder(orderId, price, orderBlock, errorPlaceholder) {
  $.ajax({
    url: 'ajax/execute_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'id': orderId
    },
    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        errorPlaceholder.text(errorMessage);
      }
      else {
        var balanceElement = $('#balance');
        var delta = (parseFloat(price) * (1 - getCommission()));
        var newBalance = parseFloat(balanceElement.text()) + delta;
        balanceElement.text(newBalance.toFixed(2));
        orderBlock.remove();
      }
    },
    error: function (response) {
      errorPlaceholder.text(msg('internal.error'));
      console.error(response);
    }
  });
}

$(document).ready(function () {
  var viewMode = $('#view-mode');

  viewMode.change(function () {
    history.pushState({}, '', '?view-mode=' + viewMode.val());
    clearErrors();
    loadOrdersForExecutor(true);
  });
  var defaultViewMode = 'available';

  $(window).bind('popstate', function () {
    updateSelectedViewMode(viewMode, defaultViewMode);
    clearErrors();
    loadOrdersForExecutor(true);
  });
  updateSelectedViewMode(viewMode, defaultViewMode);

  $('#orders').on('click', '.execute-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    executeOrder(link.data('order-id'), link.data('order-price'), orderBlock, link.next('span'));
  });
  $('.show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForExecutor();
  });
  loadOrdersForExecutor();
  //reloadMyOrdersForExecutor();
  //setInterval(loadOrdersForExecutor, 5000);
});