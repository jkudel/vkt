function loadOrdersForCustomer(reload) {
  var ordersBlock = $('#orders');

  if (reload) {
    ordersBlock.html('');
    lastLoadedOrderId = 0;
  }
  var viewMode = $('#view-mode');
  var done = viewMode.val() == 'done' ? 1 : 0;

  ajaxGetMyOrders(0, lastLoadedOrderId, done, function (response) {
    var lastOrderId = appendHtmlForOrders(response, ordersBlock, buildOrderBlockForCustomer);

    if (lastOrderId) {
      lastLoadedOrderId = lastOrderId;
    }
  }, function (errorMessage) {
    viewMode.next('.error-placeholder').text(errorMessage);
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
  return '<div>' + html + '</div>';
}

function cancelOrder(orderId, orderBlock, errorPlaceholder) {
  ajaxCancelOrder(orderId, function () {
    orderBlock.remove();
  }, function (errorMessage) {
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
  ajaxSubmitForm(AJAX_CREATE_ORDER, form, function (response) {
    $('#new-order-form').hide();
    clearNewOrderFields();
    var viewModeCombo = $('#view-mode');

    if (viewModeCombo.val() == 'waiting') {
      $('#orders').prepend(buildOrderBlockForCustomer(response['order']));
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

$(document).ready(function () {
  initViewModeChooser('waiting', function () {
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
  $('.show-more').click(function (e) {
    e.preventDefault();
    loadOrdersForCustomer(false);
  });
  loadOrdersForCustomer(false);
  //setInterval(loadOrdersForCustomer, 5000);
});