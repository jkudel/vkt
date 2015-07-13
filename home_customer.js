function loadOrdersForCustomer(reload, count) {
  if (reload) {
    removeAllFromFeed();
  }
  var viewMode = $('#view-mode');
  var done = viewMode.val() == 'done' ? 1 : 0;
  var params = buildParamsUntilLastOrder(done ? 'done_time' : 'time');
  params['done'] = done;

  if (count) {
    params['count'] = count;
  }
  ajaxGetMyOrders(params, function (response) {
    appendLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    viewMode.next('.error-placeholder').text(errorMessage);
  });
}

function cancelOrder(orderId, orderBlock, errorPlaceholder) {
  ajaxCancelOrder(orderId, function () {
    removeOrderBlock(orderBlock, orderId);
    loadOrdersForCustomer(false, 1);
  }, function (errorMessage, errorCode) {
    if (errorCode == ERROR_CODE_NO_OBJECT) {
      errorMessage = msg('already.executed.error');
    }
    errorPlaceholder.text(errorMessage);
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

  if (decPrice.indexOf(price.val()) != 0) {
    price.nextAll('span').text(msg('price.must.be.number'));
    return false;
  }
  if (decPrice.indexOf('0') == 0) {
    price.nextAll('span').text(msg('min.price.error'));
    return false;
  }
  return true;
}

function scheduleCheckingUpdatesForCustomer() {
  setTimeout(function () {
    if ($('#view-mode').val() == 'done') {
      loadNewDoneOrders(scheduleCheckingUpdatesForCustomer);
    } else {
      scheduleCheckingUpdatesForCustomer();
    }
  }, 5000);
}

function loadNewDoneOrders(runAfter) {
  var params = buildParamsSinceFirstOrder('done_time');
  params['done'] = 1;

  ajaxGetMyOrders(params, function (response) {
    prependLoadedOrdersToFeed(response);
    runAfter();
  }, function (errorMessage) {
    $('#view-mode').next('.error-placeholder').text(errorMessage);
    runAfter();
  });
}

function createOrder(form) {
  ajaxSubmitForm(AJAX_CREATE_ORDER, form, function (response) {
    $('#new-order-form').hide();
    clearNewOrderFields();
    var viewModeCombo = $('#view-mode');

    if (viewModeCombo.val() == 'waiting') {
      prependOrdersToFeed([response['order']]);
    } else {
      viewModeCombo.val('waiting').change();
    }
  }, function (errorMessage) {
    $('#new-order-error-placeholder').text(errorMessage);
  });
}

function clearNewOrderFields() {
  $('#new-order-description').val('');
  $('#new-order-price').val('');
}

buildOrderBlockInFeed = function (data) {
  var html = buildBaseOrderBlock(data, false);
  var doneTime = data['done_time'];
  var presentableDoneTime = doneTime ? new Date(doneTime * 1000).toLocaleString() : '';
  var executor = data['executor'];

  if (doneTime && executor) {
    html += '<div>' + msg('executor') + ': ' + executor + '</div>';
    html += '<div>' + msg('order.execution.time') + ': ' + presentableDoneTime + '</div>';
  } else {
    html +=
      '<div>' +
      '  <a class="cancel-order-link" data-order-id="' + data['order_id'] + '" href="#">' + msg('cancel.order') + '</a>' +
      '  <span class="error-placeholder"></span>' +
      '</div>';
  }
  return '<div>' + html + '</div>';
};

function updateRefreshWaitingOrdersButton() {
  if ($('#view-mode').val() == 'waiting') {
    $('#refresh-waiting-orders').show();
  } else {
    $('#refresh-waiting-orders').hide();
  }
}
$(document).ready(function () {
  var viewMode = initViewModeChooser('waiting', function () {
    loadOrdersForCustomer(true);
  });
  viewMode.change(function () {
    updateRefreshWaitingOrdersButton();
  });
  updateRefreshWaitingOrdersButton();

  $('#refresh-waiting-orders').click(function (e) {
    e.preventDefault();
    loadOrdersForCustomer(true);
  });

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
  $('#show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForCustomer(false);
  });
  loadOrdersForCustomer(false);
  scheduleCheckingUpdatesForCustomer();
});