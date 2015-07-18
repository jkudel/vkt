function loadOrdersForCustomer(reload, count) {
  if (reload) {
    removeAllFromFeed();
  }
  var done = viewMode == 'done' ? 1 : 0;
  var params = buildParamsOlderThanLastOrder(done ? 'done_time' : 'time');
  params['done'] = done;

  if (count) {
    params['count'] = count;
  }
  ajaxGetMyOrders(params, function (response) {
    appendLoadedOrdersToFeed(response);
  }, function (errorMessage) {
    var errorPlaceholder = $('#main-error-placeholder');
    errorPlaceholder.text(errorMessage);
    errorPlaceholder.show();
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
  var result = true;

  if (!description.val().trim()) {
    var descriptionErrorPlaceholder = description.next('.error-placeholder');
    descriptionErrorPlaceholder.text(msg('no.description'));
    descriptionErrorPlaceholder.show();
    result = false;
  }
  var price = $('#new-order-price');
  var priceErrorPlaceholder = price.next('.error-placeholder');

  if (!price.val()) {
    priceErrorPlaceholder.text(msg('no.price'));
    priceErrorPlaceholder.show();
    return false;
  }
  var floatPrice = parseFloat(price.val());
  var decPrice = isNaN(floatPrice) ? '' : floatPrice.toFixed(2);

  if (decPrice.indexOf(price.val()) != 0) {
    priceErrorPlaceholder.text(msg('price.must.be.number'));
    priceErrorPlaceholder.show();
    return false;
  }
  if (decPrice.indexOf('0') == 0) {
    priceErrorPlaceholder.text(msg('min.price.error') + ' 1 ' + msg('currency'));
    priceErrorPlaceholder.show();
    return false;
  }
  return result;
}

function scheduleCheckingUpdatesForCustomer() {
  setTimeout(function () {
    if (viewMode == 'done') {
      loadNewDoneOrders(scheduleCheckingUpdatesForCustomer);
    } else {
      scheduleCheckingUpdatesForCustomer();
    }
  }, 5000);
}

function loadNewDoneOrders(runAfter) {
  var params = buildParamsNewerThanFirstOrder('done_time');
  params['done'] = 1;

  ajaxGetMyOrders(params, function (response) {
    prependLoadedOrdersToFeed(response);
    runAfter();
  }, function (errorMessage) {
    $('#main-error-placeholder').text(errorMessage);
    runAfter();
  });
}

function createOrder(form) {
  ajaxSubmitForm(AJAX_CREATE_ORDER, form, function (response) {
    $('#new-order-form').parent().hide();
    clearNewOrderFields();

    if (viewMode == 'waiting') {
      prependOrdersToFeed([response['order']]);
    } else {
      chooseViewMode('waiting', true);
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
  var doneTime = data['done_time'];
  var addToBottomPanel = '';

  if (!doneTime) {
    var cancelButton =
      '<input class="button cancel-order-button" type="button" ' +
      'data-order-id="' + data['order_id'] + '" ' +
      'value="'+ msg('cancel.order') +'"/>';
    addToBottomPanel = '<div class="action-panel""><span class="error-placeholder"></span>' +
      cancelButton + '</div>';
  }
  return buildBaseOrderBlock(data, false, true, addToBottomPanel);
};

reloadAll = function () {
  loadOrdersForCustomer(true);
};

function updateRefreshWaitingOrdersButton() {
  if (viewMode == 'waiting') {
    $('#refresh-waiting-orders').show();
  } else {
    $('#refresh-waiting-orders').hide();
  }
}

$(document).ready(function () {
  var viewModeButtons = init('waiting');

  viewModeButtons.click(function () {
    updateRefreshWaitingOrdersButton();
  });
  updateRefreshWaitingOrdersButton();

  $('#refresh-waiting-orders').click(function (e) {
    e.preventDefault();
    loadOrdersForCustomer(true);
  });

  $('#orders').on('click', '.cancel-order-button', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    cancelOrder(link.data('order-id'), link.parents('.order'),
      link.prev('.error-placeholder'));
  });

  $('#new-order-button').click(function (e) {
    e.preventDefault();
    $('#new-order-form').parent().slideDown('fast');
    clearErrors();
  });
  $('#new-order-cancel').click(function () {
    $('#new-order-form').parent().slideUp('fast');
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
  scheduleCheckingUpdatesForCustomer();
});