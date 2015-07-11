var loadedOrders = {}; // { orderId_customerId -> time }
var lastLoadedOrder = null;

function buildUntilParamsByOrder(order) {
  return {
    'until_time': order['time'],
    'until_customer_id': order['customer_id'],
    'until_order_id': order['order_id']
  };
}

function appendLoadedOrders(response, block, buildBlockFunc) {
  var list = response['list'];

  if (!list) {
    return;
  }
  var filteredList = [];

  for (var i = 0; i < list.length; i++) {
    var order = list[i];
    var orderId = order['order_id'];
    var customerId = order['customer_id'];
    var orderTime = order['time'];

    if (orderId && customerId && orderTime) {
      var key = orderId + '_' + customerId;

      if (!loadedOrders[key]) {
        loadedOrders[key] = orderTime;
        filteredList.push(order);
        lastLoadedOrder = order;
      }
    }
  }
  block.append(buildOrdersListBlock(filteredList, buildBlockFunc));
  var showMoreButton = block.next().children('.show-more');

  if (response['has_more'] == 'true') {
    showMoreButton.show();
  } else {
    showMoreButton.hide();
  }
}

function buildOrdersListBlock(list, func) {
  var builder = ['<div>'];

  for (var i = 0; i < list.length; i++) {
    builder.push(func(list[i]));
  }
  builder.push('</div>');
  return builder.join('');
}

function buildBaseOrderBlock(data, showProfit) {
  var time = data['time'];
  var presentableTime = time ? new Date(time) : '';
  var html = '<div>' + data['description'] + '</div>';

  if (data['price']) {
    html += '<div>' + msg('price') + ': ' + data['price'] + '</div>';
  }
  if (showProfit && data['profit']) {
    html += '<div>' + msg('profit') + ': ' + data['profit'] + '</div>';
  }
  return html + '<div>' + msg('order.publish.time') + ': ' + presentableTime + '</div>';

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

function initViewModeChooser(defaultViewMode, reloadFunc) {
  var viewMode = $('#view-mode');

  viewMode.change(function () {
    history.pushState({}, '', '?view-mode=' + viewMode.val());
    clearErrors();
    reloadFunc();
  });

  $(window).bind('popstate', function () {
    updateSelectedViewMode(viewMode, defaultViewMode);
    clearErrors();
    reloadFunc();
  });
  updateSelectedViewMode(viewMode, defaultViewMode);
}