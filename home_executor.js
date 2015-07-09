function reloadMyOrdersForExecutor() {
  var errorPlaceholder = $('my-orders').prev('.error-placeholder');
  doReloadOrders('ajax/get_my_orders.php', $('#my-orders'), errorPlaceholder, function (data) {
    return buildOwnOrderBlock(data, false);
  });
}

function reloadAvailableOrders() {
  var availableOrders = $('#available-orders');
  var errorPlaceholder = availableOrders.prev('.error-placeholder');
  doReloadOrders('ajax/get_waiting_orders.php', availableOrders,
    errorPlaceholder, buildAvailableOrderBlock);
}

function buildAvailableOrderBlock(data) {
  return buildBaseOrderBlock(data) +
    '<div>' +
    '  <a class="execute-order-link" data-order-id="' + data['id'] + '" href="#">' + msg('execute.order') + '</a>' +
    '  <span class="error-placeholder"></span>' +
    '</div>';
}

function executeOrder(orderId, orderBlock, errorPlaceholder) {
  $.ajax({
    url: 'ajax/execute_order.php',
    type: "POST",
    dataType: "json",
    data: {
      'id' : orderId
    },
    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        errorPlaceholder.text(errorMessage);
      }
      else {
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
  $('#available-orders').on('click', '.execute-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    executeOrder(link.data('order-id'), orderBlock, link.next('span'));
  });
  reloadAvailableOrders();
  //reloadMyOrdersForExecutor();
  setInterval(reloadAvailableOrders, 5000);
});