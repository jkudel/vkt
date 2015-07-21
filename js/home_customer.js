function cancelOrder(orderId, orderBlock, link) {
  var progress = link.prev('.progress');

  if (progress.length > 0) {
    return;
  }
  var errorPlaceholder = link.prevAll('.error-placeholder');
  link.before('<div class="progress"></div>');
  progress = initProgress(link.prev());

  scheduleFeedAction(function (runAfter, canceledFunc) {
    if (canceledFunc()) {
      return;
    }
    ajaxCancelOrder(orderId, function () {
      if (canceledFunc()) {
        return;
      }
      progress.remove();
      removeOrderBlock(orderBlock, orderId);
      loadUnderProgress(false, 1);
      runAfter();
    }, function (errorMessage, errorCode) {
      if (canceledFunc()) {
        return;
      }
      progress.remove();

      if (errorCode == ERROR_CODE_NO_OBJECT) {
        errorMessage = msg('already.executed.or.canceled.error');
      }
      errorPlaceholder.show();
      errorPlaceholder.text(errorMessage);
      runAfter();
    });
  }, function() {
    progress.remove();
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
  var maxPrice = getCommonConstant('order.max.price');

  if (floatPrice > maxPrice) {
    priceErrorPlaceholder.text(msg('max.price.error') + ' ' + maxPrice + ' ' + msg('currency'));
    priceErrorPlaceholder.show();
    return false;
  }
  return result;
}

function scheduleCheckingUpdatesForCustomer() {
  setTimeout(function () {
    scheduleFeedAction(function (runAfter, canceledFunc) {
      if (canceledFunc()) {
        return;
      }
      if (viewMode != 'done') {
        scheduleCheckingUpdatesForCustomer();
        runAfter();
        return;
      }
      var params = buildParamsNewerThanOrders('done_time');
      params['done'] = 1;

      ajaxGetMyOrders(params, function (response) {
        if (canceledFunc()) {
          return;
        }
        prependLoadedOrdersToFeed(response);
        scheduleCheckingUpdatesForCustomer();
        runAfter();
      }, function (errorMessage) {
        if (canceledFunc()) {
          return;
        }
        console.log(errorMessage);
        scheduleCheckingUpdatesForCustomer();
        runAfter();
      });
    }, function() {
      scheduleCheckingUpdatesForCustomer();
    });
  }, 5000);
}

function createOrder(button) {
  var progress = button.prev('.progress');

  if (progress.length > 0) {
    return;
  }
  button.before('<div class="progress"></div>');
  progress = initProgress(button.prev());
  var form = button.parents('form');

  ajaxSubmitForm(AJAX_CREATE_ORDER, form, function (response) {
    progress.remove();
    $('#new-order-form').parent().hide();
    clearNewOrderFields();

    if (viewMode == 'waiting') {
      prependOrdersToFeed([response['order']]);
    } else {
      chooseViewMode('waiting', true);
    }
  }, function (errorMessage) {
    progress.remove();
    var errorPlaceholder = $('#new-order-error-placeholder');
    errorPlaceholder.show();
    errorPlaceholder.text(errorMessage);
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
      'value="' + msg('cancel.order') + '"/>';
    addToBottomPanel = '<div class="action-panel""><span class="error-placeholder"></span>' +
    cancelButton + '</div>';
  }
  return buildBaseOrderBlock(data, false, true, addToBottomPanel);
};

loadOrders = function (reload, count, errorCallback, canceledFunc, callback) {
  var done = viewMode == 'done' ? 1 : 0;
  var params = buildParamsOlderThanOrders(done ? 'done_time' : 'time');
  params['done'] = done;

  if (count) {
    params['count'] = count;
  }
  ajaxGetMyOrders(params, function (response) {
    if (canceledFunc()) {
      return;
    }
    callback();
    appendLoadedOrdersToFeed(response);

    if (reload) {
      updateRefreshWaitingOrdersButton();
    }
  }, errorCallback);
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
    $('#refresh-waiting-orders').hide();
  });

  $('#refresh-waiting-orders').click(function (e) {
    e.preventDefault();
    clearErrors();
    var progress = $(this).next('.progress');

    if (progress.length > 0) {
      return;
    }
    $(this).after('<div class="progress"></div>');
    progress = initProgress($(this).next());
    fullReloadUnderProgress(progress);
  });

  $('#orders').on('click', '.cancel-order-button', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    cancelOrder(link.data('order-id'), link.parents('.order'), link);
  });
  var newOrderForm = $('#new-order-form');

  $('#new-order-button').click(function (e) {
    e.preventDefault();
    newOrderForm.parent().slideDown('fast', function() {
      $('#new-order-description').focus();
    });
    clearErrors();
  });
  var newOrderOk = $('#new-order-ok');
  var newOrderCancel = $('#new-order-cancel');
  newOrderCancel.prop('title', 'Esc');

  newOrderCancel.click(function () {
    newOrderForm.parent().slideUp('fast');
    clearNewOrderFields();
    clearErrors();
  });
  newOrderForm.keydown(function (e) {
    if (e.keyCode == 27) {
      newOrderCancel.click();
    } else if ((e.ctrlKey || e.metaKey) && e.keyCode == 13) {
      newOrderOk.click();
    }
  });
  newOrderOk.prop('title', isMac() ? 'Command+Enter' : 'Ctrl+Enter');
  newOrderOk.click(function (e) {
    e.preventDefault();
    clearErrors();

    if (validateNewOrderForm()) {
      createOrder($(this));
    }
  });
  scheduleCheckingUpdatesForCustomer();
});