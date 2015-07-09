function reloadMyOrdersForCustomer() {
  var ifDone = $('#if-done');
  var params = ifDone.length > 0 ? '?done=' + ifDone.val() : '';
  var errorPlaceholder = ifDone.next('.error-placeholder');
  doReloadOrders('ajax/get_my_orders.php' + params, $('#my-orders'), errorPlaceholder, function (data) {
    return buildOwnOrderBlock(data, true);
  });
}

function buildOwnOrderBlock(data, showExecutor) {
  var html = buildBaseOrderBlock(data);
  var doneTime = data['done_time'];
  var presentableDoneTime = doneTime ? new Date(doneTime) : '';
  var executor = data['executor'];

  if (doneTime && executor) {
    if (showExecutor) {
      html += '<div>' + msg('executor') + ': ' + executor + '</div>';
    }
    html += '<div>' + msg('order.execution.time') + ': ' + presentableDoneTime + '</div>';
  }
  return html +
    '<div>' +
    '  <a class="cancel-order-link" data-order-id="' + data['id'] + '" href="#">' + msg('cancel.order') + '</a>' +
    '  <span class="error-placeholder"></span>' +
    '</div>';
}

function cancelOrder(orderId, orderBlock, errorPlaceholder) {
  // todo: progress
  $.ajax({
    url: 'ajax/cancel_order.php',
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

function validateNewOrderForm() {
  var description = $('#new-order-description');

  if (!description.val().trim()) {
    description.nextAll('span').text(msg('no.description'));
    return false;
  }
  var price = $('#new-order-price');

  if (!price.val()) {
    price.nextAll('span').text(msg('no.price'));
    return false;
  }
  var floatPrice = parseFloat(price.val());
  var decPrice = isNaN(floatPrice) ? '' : floatPrice.toFixed(2);

  if (!decPrice.startsWith(price.val())) {
    price.nextAll('span').text(msg('price.must.be.number'));
    return false;
  }
  if (decPrice.startsWith('0')) {
    price.nextAll('span').text(msg('min.price.error'));
    return false;
  }
  return true;
}

function createOrder(form) {
  // todo: progress
  $.ajax({
    url: 'ajax/create_order.php',
    type: "POST",
    dataType: "json",
    data: form.serialize(),

    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        $('#new-order-error-placeholder').text(errorMessage);
      }
      else {
        $('#new-order-form').hide();
        $('#my-orders').prepend(buildOwnOrderBlock(response['order']));
      }
    },

    error: function (response) {
      $('#new-order-error-placeholder').text(msg('internal.error'));
      console.error(response);
    }
  });
}

$(document).ready(function () {
  var ifExecutedLink = $('#if-done');

  ifExecutedLink.change(function() {
    clearErrors();
    reloadMyOrdersForCustomer();
  });

  $('#my-orders').on('click', '.cancel-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    cancelOrder(link.data('order-id'), orderBlock, link.next('span'));
  });

  $('#new-order-link').click(function(e) {
    e.preventDefault();
    $('#new-order-form').fadeIn();
    clearErrors();
  });
  $('#new-order-cancel').click(function () {
    $('#new-order-form').fadeOut();
    clearErrors();
  });
  $('#new-order-form').submit(function (e) {
    e.preventDefault();
    clearErrors();

    if (validateNewOrderForm()) {
      createOrder($(this));
    }
  });
  reloadMyOrdersForCustomer();
  setInterval(reloadMyOrdersForCustomer, 5000);
});