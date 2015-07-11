var lastLoadedOrderId = 0;

function doLoadOrders(url, block, errorPlaceholder, buildBlockFunc, runIfSuccess) {
  $.ajax({
    url: url,
    type: "GET",
    dataType: "json",
    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        errorPlaceholder.text(errorMessage);
      }
      else {

      }
    },
    error: function (response) {
      errorPlaceholder.text(msg('internal.error'));
      console.error(response);
    }
  });
}

function appendHtmlForOrders(response, block, buildBlockFunc) {
  var list = response['list'];
  block.append(buildOrdersListBlock(list, buildBlockFunc));
  var showMoreButton = block.next().children('.show-more');

  if (response['has_more'] == 'true') {
    showMoreButton.show();
  } else {
    showMoreButton.hide();
  }
  var lastOrder = list[list.length - 1];
  return lastOrder ? lastOrder['id'] : null;
}

function buildOrdersListBlock(list, func) {
  var builder = ['<div>'];

  for (var i = 0; i < list.length; i++) {
    builder.push(func(list[i]));
  }
  builder.push('</div>');
  return builder.join('');
}

function buildBaseOrderBlock(data) {
  var time = data['time'];
  var presentableTime = time ? new Date(time) : '';
  return '<div>' + data['description'] + '</div>' +
    '<div>' + msg('price') + ': ' + data['price'] + '</div>' +
    '<div>' + msg('order.publish.time') + ': ' + presentableTime + '</div>';

}

function clearErrors() {
  $('.error-placeholder').text('');
}

function updateSelectedViewMode(selector, defaultMode) {
  var defaultIfExecutedVal = getUrlParameters()['view-mode'];

  if (!defaultIfExecutedVal) {
    defaultIfExecutedVal = defaultMode;
  }
  selector.val(defaultIfExecutedVal);
}