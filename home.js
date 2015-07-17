const ORDER_LIST_PART_SIZE = 3;

var buildOrderBlockInFeed = null;
var feedOrdersIdSet = {};
var feedOrders = [];

function buildParamsOlderThanLastOrder(timeFieldName) {
  var result = {count: ORDER_LIST_PART_SIZE};

  if (feedOrders.length == 0) {
    return result;
  }
  var order = feedOrders[feedOrders.length - 1];
  result['up_time'] = order[timeFieldName];
  result['up_order_id'] = order['order_id'];
  return result;
}

function buildParamsNewerThanFirstOrder(timeFieldName) {
  var result = {count: ORDER_LIST_PART_SIZE};

  if (feedOrders.length == 0) {
    return result;
  }
  var order = feedOrders[0];
  result['lw_time'] = order[timeFieldName];
  result['lw_order_id'] = order['order_id'];
  return result;
}

function addOrdersToFeedSet(response) {
  var list = response['list'];

  if (!list) {
    return null;
  }
  var filteredList = [];

  for (var i = 0; i < list.length; i++) {
    var order = list[i];
    var orderId = order['order_id'];

    if (!feedOrdersIdSet[orderId]) {
      feedOrdersIdSet[orderId] = true;
      filteredList.push(order);
    }
  }
  return filteredList;
}

function prependLoadedOrdersToFeed(response) {
  var filteredList = addOrdersToFeedSet(response);

  if (filteredList) {
    prependOrdersToFeed(filteredList, response['has_more'] == 'true');
  }
}

function prependOrdersToFeed(list, hasMore) {
  feedOrders = list.concat(feedOrders);
  var ordersBlock = $('#orders');
  ordersBlock.prepend(buildHtmlForOrdersList(list));

  if (feedOrders.length > ORDER_LIST_PART_SIZE) {
    var count = Math.min(list.length, feedOrders.length - ORDER_LIST_PART_SIZE);

    if (count > 0) {
      var start = feedOrders.length - count;

      for (var i = start; i < feedOrders.length; i++) {
        var orderId = feedOrders[i]['order_id'];
        delete feedOrdersIdSet[orderId];
      }
      feedOrders = feedOrders.slice(0, start);
      ordersBlock.children().slice(start).remove();
      hasMore = true;
    }
  }
  if (hasMore) {
    $('#show-more').show();
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

function removeOrderBlock(selector, orderId) {
  selector.remove();
  var indexToRemove = -1;

  for (var i = 0; i < feedOrders.length; i++) {
    var order = feedOrders[i];

    if (order['order_id'] == orderId) {
      indexToRemove = i;
      break;
    }
  }
  if (indexToRemove >= 0) {
    feedOrders.splice(indexToRemove, 1);
  }
  delete feedOrdersIdSet[orderId];
}

function removeAllFromFeed() {
  $('#orders').html('');
  feedOrders = [];
  feedOrdersIdSet = {};
}

function removeOrdersFromFeed(orderIds) {
  var ordersToRemove = {};

  for (var i = 0; i < orderIds.length; i++) {
    ordersToRemove[orderIds[i]] = true;
  }
  var result = 0;

  $('#orders').children().each(function () {
    var executeLink = $(this).find('.execute-order-link');
    var orderId = executeLink.data('order-id');

    if (ordersToRemove[orderId]) {
      removeOrderBlock($(this), orderId);
      result++;
    }
  });
  return result;
}

function buildHtmlForOrdersList(list) {
  var builder = [];

  for (var i = 0; i < list.length; i++) {
    builder.push(buildOrderBlockInFeed(list[i]));
  }
  return builder.join('');
}

function buildBaseOrderBlock(data, showProfit) {
  var time = data['time'];
  var presentableTime = time ? new Date(time * 1000).toLocaleString() : '';
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
  return viewMode;
}