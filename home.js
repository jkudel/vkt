function reloadMyOrders(errorPlaceholder) {
  // todo: progress
  var ifDone = $('#if-done');
  var params = ifDone.length > 0 ? '?done=' + ifDone.val() : '';
  $('#my-orders').html('');

  $.ajax({
    url: 'get_my_orders' + params,
    type: "GET",
    dataType: "json",
    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        errorPlaceholder.text(errorMessage);
      }
      else {
        $('#my-orders').html(buildOrdersList(response));
      }
    },
    error: function (response) {
      errorPlaceholder.text(msg('internal.error'));
      console.error(response);
    }
  });
}

function buildOrdersList(list) {
  var builder = ['<div>'];

  for (var i = 0; i < list.length; i++) {
    builder.push(buildOrderBlock(list[i]));
  }
  builder.push('</div>');
  return builder.join('');
}

function buildOrderBlock(data) {
  var time = data['time'];
  var presentableTime = time ? new Date(time) : '';
  var html =
    '<div>' + data['description'] + '</div>' +
    '<div>' + msg('price') + ': ' + data['price'] + '</div>' +
    '<div>' + msg('order.publish.time') + ': ' + presentableTime + '</div>';
  var doneTime = data['done_time'];
  var presentableDoneTime = doneTime ? new Date(doneTime) : '';
  var executor = data['executor'];

  if (doneTime && executor) {
    html +=
      '<div>' + msg('executor') + ': ' + executor + '</div>' +
      '<div>' + msg('order.execution.time') + ': ' + presentableDoneTime + '</div>';
  }
  html +=
    '<div>' +
    '  <a class="cancel-order-link" data-order-id="' + data['id'] + '" href="#">' + msg('cancel.order') + '</a>' +
    '  <span class="error-placeholder"></span>' +
    '</div>';
  return html;
}

function cancelOrder(orderId, orderBlock, errorPlaceholder) {
  // todo: progress
  $.ajax({
    url: 'do_cancel_order',
    type: "POST",
    dataType: "json",
    data: {
      'order_id' : orderId
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

function clearErrors() {
  $('.error-placeholder').text('');
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
    url: 'do_create_order',
    type: "POST",
    dataType: "json",
    data: form.serialize(),

    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        $('#new-order-error-placeholder').text(errorMessage);
      }
      else {
        $('#my-orders').prepend(buildOrderBlock(response['order']));
      }
    },

    error: function (response) {
      $('#new-order-error-placeholder').text(msg('internal.error'));
      console.error(response);
    }
  });
}

$(document).ready(function () {
  $('#exit').click(function (e) {
    e.preventDefault();
    clearErrors();

    $.ajax({
      url: 'do_logout',
      type: "POST",
      dataType: "text",
      success: function () {
        location.reload();
      },
      error: function (response) {
        console.error(response);
      }
    });
  });
  var ifExecutedLink = $('#if-executed');

  ifExecutedLink.change(function() {
    clearErrors();
    reloadMyOrders($(this).next('span'));
  });

  $('.cancel-order-link').on('click', function (e) {
    e.preventDefault();
    clearErrors();
    var link = $(this);
    var orderBlock = link.parent().remove();
    var orderId = link.data('id');
    cancelOrder(orderId, orderBlock, link.next('span'));
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
  reloadMyOrders(ifExecutedLink.next('span'));
});