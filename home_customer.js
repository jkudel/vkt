
function loadOrdersForCustomer(reload) {
  var ordersBlock = $('#orders');

  if (reload) {
    ordersBlock.html('');
    lastLoadedOrderId = 0;
  }
  var params = '?before_id=' + lastLoadedOrderId;
  var viewMode = $('#view-mode');

  if (viewMode.val() == 'done') {
    params += '&done=1';
  }
  var errorPlaceholder = viewMode.next('.error-placeholder');
  var url = 'ajax/get_my_orders.php' + params;

  doLoadOrders(url, ordersBlock, errorPlaceholder, function (data) {
    return buildOrderBlockForCustomer(data);
  }, function (lastOrderId) {
    if (lastOrderId) {
      lastLoadedOrderId = lastOrderId;
    }
  });
}

function buildOrderBlockForCustomer(data) {
  var html = buildBaseOrderBlock(data);
  var doneTime = data['done_time'];
  var presentableDoneTime = doneTime ? new Date(doneTime) : '';
  var executor = data['executor'];

  if (doneTime && executor) {
    html += '<div>' + msg('executor') + ': ' + executor + '</div>';
    html += '<div>' + msg('order.execution.time') + ': ' + presentableDoneTime + '</div>';
  } else {
    html +=
      '<div>' +
      '  <a class="cancel-order-link" data-order-id="' + data['id'] + '" href="#">' + msg('cancel.order') + '</a>' +
      '  <span class="error-placeholder"></span>' +
      '</div>';
  }
  return html;
}

function cancelOrder(orderId, orderBlock, errorPlaceholder) {
  // todo: progress
  $.ajax({
    url: 'ajax/cancel_order.php',
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
        clearNewOrderFields();
        var viewModeCombo = $('#view-mode');

        if (viewModeCombo.val() == 'waiting') {
          $('#orders').prepend(buildOrderBlockForCustomer(response['order']));
        } else {
          viewModeCombo.val('waiting').change();
        }
      }
    },

    error: function (response) {
      $('#new-order-error-placeholder').text(msg('internal.error'));
      console.error(response);
    }
  });
}

function clearNewOrderFields() {
  $('#new-order-description').val('');
  $('#new-order-price').val('');
}

$(document).ready(function () {
  var viewMode = $('#view-mode');

  viewMode.change(function () {
    history.pushState({}, '', '?view-mode=' + viewMode.val());
    clearErrors();
    loadOrdersForCustomer(true);
  });
  var defaultViewMode = 'waiting';

  $(window).bind('popstate', function () {
    updateSelectedViewMode(viewMode, defaultViewMode);
    clearErrors();
    loadOrdersForCustomer(true);
  });
  updateSelectedViewMode(viewMode, defaultViewMode);

  $('#orders').on('click', '.cancel-order-link', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().parent();
    cancelOrder(link.data('order-id'), orderBlock, link.next('span'));
  });

  $('#new-order-link').click(function (e) {
    e.preventDefault();
    $('#new-order-form').fadeIn();
    clearErrors();
  });
  $('#new-order-cancel').click(function () {
    $('#new-order-form').fadeOut();
    clearNewOrderFields();
    clearErrors();
  });
  $('#new-order-form').submit(function (e) {
    e.preventDefault();
    clearErrors();

    if (validateNewOrderForm()) {
      createOrder($(this));
    }
  });
  $('.show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForCustomer(false);
  });
  loadOrdersForCustomer(false);
  //setInterval(loadOrdersForCustomer, 5000);
});