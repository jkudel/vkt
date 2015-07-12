const ORDER_LIST_PART_SIZE = 3;

var feedData = {
  buildBlockFunc: null,
  keyFunc: null
};

var feedOrdersIdSet = {};
var feedOrders = [];

function buildUntilParamsByLastOrder() {
  if (feedOrders.length == 0) {
    return {};
  }
  var order = feedOrders[feedOrders.length - 1];
  return {
    'until_time': order['time'],
    'until_customer_id': order['customer_id'],
    'until_order_id': order['order_id']
  };
}

function buildSinceParamsByFirstOrder() {
  if (feedOrders.length == 0) {
    return {};
  }
  var order = feedOrders[0];
  return {
    'since_time': order['time'],
    'since_customer_id': order['customer_id'],
    'since_order_id': order['order_id']
  };
}

function addOrdersToFeedSet(response) {
  var list = response['list'];

  if (!list) {
    return null;
  }
  var filteredList = [];

  for (var i = 0; i < list.length; i++) {
    var order = list[i];
    var key = feedData.keyFunc(order);

    if (!feedOrdersIdSet[key]) {
      feedOrdersIdSet[key] = true;
      filteredList.push(order);
    }
  }
  return filteredList;
}

function prependLoadedOrdersToFeed(response) {
  var filteredList = addOrdersToFeedSet(response);

  if (filteredList) {
    prependOrdersToFeed(filteredList);
  }
}

function prependOrdersToFeed(list) {
  feedOrders = list.concat(feedOrders);
  var ordersBlock = $('#orders');
  ordersBlock.prepend(buildHtmlForOrdersList(list));

  if (feedOrders.length > ORDER_LIST_PART_SIZE) {
    var count = Math.min(list.length, feedOrders.length - ORDER_LIST_PART_SIZE);

    if (count > 0) {
      var start = feedOrders.length - count;

      for (var i = start; i < feedOrders.length; i++) {
        var key = feedData.keyFunc(feedOrders[i]);
        delete feedOrdersIdSet[key];
      }
      feedOrders = feedOrders.slice(0, start);
      ordersBlock.children().slice(start).remove();
    }
  }
}

function appendLoadedOrdersToFeed(response) {
  var filteredList = addOrdersToFeedSet(response);

  if (filteredList) {
    feedOrders = feedOrders.concat(filteredList);
    $('#orders').append(buildHtmlForOrdersList(filteredList));
    var showMoreButton = $('#show-more');

    if (response['has_more'] == 'true') {
      showMoreButton.show();
    } else {
      showMoreButton.hide();
    }
  }
}

function removeOrderBlock(selector, key) {
  selector.remove();
  var indexToRemove = -1;

  for (var i = 0; i < feedOrders.length; i++) {
    var order = feedOrders[i];

    if (feedData.keyFunc(order) == key) {
      indexToRemove = i;
      break;
    }
  }
  if (indexToRemove >= 0) {
    feedOrders.splice(indexToRemove, 1);
  }
  delete feedOrdersIdSet[key];
}

function removeAllFromFeed() {
  $('#orders').html('');
  feedOrders = [];
  feedOrdersIdSet = {};
}

function removeOrdersFromFeed(orders) {
  var ordersToRemove = {};

  for (var i = 0; i < orders.length; i++) {
    var order = orders[i];
    var orderId = order['order_id'];
    var customerId = order['customer_id'];

    if (orderId && customerId) {
      ordersToRemove[customerId + '_' + orderId] = order;
    }
  }
  var result = 0;

  $('#orders').children().each(function () {
    var executeLink = $(this).find('.execute-order-link');
    var orderId = executeLink.data('order-id');
    var customerId = executeLink.data('customer-id');
    var order = ordersToRemove[customerId + '_' + orderId];

    if (order) {
      removeOrderBlock($(this), feedData.keyFunc(order));
      result++;
    }
  });
  return result;
}

function buildHtmlForOrdersList(list) {
  var builder = [];

  for (var i = 0; i < list.length; i++) {
    builder.push(feedData.buildBlockFunc(list[i]));
  }
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