const ORDER_LIST_PART_SIZE = 3;
const VIEW_MODE_PARAM = 'view-mode';

const ERROR_CODE_NO_OBJECT = 3;

var buildOrderBlockInFeed = null;
var loadOrders = null;
var feedOrdersIdSet = {};
var feedOrders = [];
var viewMode = null;
var feedActionsQueue = [];
var executingFeedActionInfo = null;

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
  $('#show-more').hide();
  $('#orders').html('');
  feedOrders = [];
  feedOrdersIdSet = {};
}

function buildHtmlForOrdersList(list) {
  var builder = [];

  for (var i = 0; i < list.length; i++) {
    builder.push(buildOrderBlockInFeed(list[i]));
  }
  return builder.join('');
}

function doGetPresentableTime(date) {
  var now = moment.utc();

  if (date.isAfter(now)) {
    return "just now";
  }
  if (now.diff(date, 'days') > 13) {
    if (now.get('year') === date.get('year')) {
      return date.format("[on] D.MM");
    } else {
      return date.format("[on] D.MM.YYYY");
    }
  }
  return date.fromNow();
}

function getPresentableTime(time) {
  moment.locale('ru');
  var mt = time ? moment.unix(time) : '';
  var presentableTime = mt ? doGetPresentableTime(mt) : '';
  var timeTooltip = mt ? mt.format("H:mm D.MM.YYYY") : '';
  return [presentableTime, timeTooltip];
}

function buildBaseOrderBlock(data, showProfit, showExecutor, addToBottomPanel) {
  var time = data['time'];
  var pair = getPresentableTime(time);
  var presentableTime = pair[0];
  var timeTooltip = pair[1];

  if (timeTooltip == presentableTime) {
    timeTooltip = '';
  }
  var html = '<div class="description">' + escapeMultiLineString(data['description']) + '</div>';

  html += '<div class="bottom-panel"><div class="params">';
  if (data['price']) {
    html += '<div class="price">' + msg('price') + ': ' + data['price'] + ' ' + msg('currency') + '</div>';
  }
  if (showProfit && data['profit']) {
    html += '<div class="price">' + msg('profit') + ': ' + data['profit'] + ' ' + msg('currency') + '</div>';
  }
  html += '<div class="publish-time">' + msg('order.publish.time') + ': <span title="' +
  timeTooltip + '">' + presentableTime + '</span></div>';
  var doneTime = data['done_time'];

  if (doneTime) {
    pair = getPresentableTime(doneTime);
    presentableTime = pair[0];
    timeTooltip = pair[1];

    if (timeTooltip == presentableTime) {
      timeTooltip = '';
    }
    if (showExecutor) {
      var executor = data['executor'];
      html += '<div>' + msg('executor') + ': ' + executor + '</div>';
    }
    html += '<div>' + msg('order.execution.time') + ': <span title="' +
    timeTooltip + '">' + presentableTime + '</span></div>';
  }
  html += '</div>';

  if (addToBottomPanel) {
    html += addToBottomPanel;
  }
  return '<div class="order">' + html + '</div></div>';
}

function clearErrors() {
  var placeholders = $('.error-placeholder');
  placeholders.text('');
  placeholders.hide();
}

function getViewModeByLink(linkElement) {
  return paramsStrToObject(linkElement.attr('href'))[VIEW_MODE_PARAM];
}

function updateViewModeButtons() {
  $('#view-mode').find('a').each(function () {
    var mode = getViewModeByLink($(this));
    $(this).toggleClass('checked', mode == viewMode);
  });
}

function chooseViewModeFromUrl(defaultMode) {
  var mode = getUrlParameters()[VIEW_MODE_PARAM];

  if (!mode) {
    mode = defaultMode;
  }
  chooseViewMode(mode, false);
}

function chooseViewMode(mode, pushState) {
  if (viewMode == mode) {
    return;
  }
  viewMode = mode;
  updateViewModeButtons();
  clearErrors();

  if (pushState) {
    history.pushState({}, '', '?' + VIEW_MODE_PARAM + '=' + mode);
  }
  fullReloadUnderProgress();
}

function fullReloadUnderProgress(progress) {
  cancelAllFeedActions();
  loadUnderProgress(true, null, null, progress);
}

function loadUnderProgress(reload, count, errorPlaceholder, progress) {
  if (!progress) {
    var ordersPanel = $('.orders-panel');
    progress = ordersPanel.next('.progress');

    if (progress.length == 0) {
      ordersPanel.after('<div class="progress big bottom"></div>');
      progress = initProgress(ordersPanel.next());
    } else {
      var counter = progress.data('counter');
      progress.data('counter', (counter ? parseInt(counter) + 1 : 1));
    }
  }
  scheduleLoadingOrders(reload, count, errorPlaceholder, function () {
    var counter = progress.data('counter');
    var c = counter ? parseInt(counter) : 0;

    if (c == 0) {
      progress.remove();
    } else {
      progress.data('counter', c - 1);
    }
  });
}

function scheduleLoadingOrders(reload, count, errorPlaceholder, finished) {
  scheduleFeedAction(function (runAfter, canceledFunc) {
    if (canceledFunc()) {
      return;
    }
    var callback = function () {
      finished();
      runAfter();
    };
    var errorCallback = function (errorMessage) {
      if (canceledFunc()) {
        return;
      }
      if (!errorPlaceholder) {
        errorPlaceholder = $('#bottom-error-placeholder');
      }
      errorPlaceholder.text(errorMessage);
      errorPlaceholder.show();
      callback();
    };
    loadOrders(reload, count, errorCallback, canceledFunc, callback);
  }, finished);
}

function cancelAllFeedActions() {
  if (executingFeedActionInfo) {
    executingFeedActionInfo.canceled = true;
    executingFeedActionInfo.cancel();

    for (var i = 0; i < feedActionsQueue.length; i++) {
      var actionInfo = feedActionsQueue[i];
      actionInfo.canceled = true;
      actionInfo.cancel();
    }
    feedActionsQueue = [];
    executingFeedActionInfo = null;
  }
}

function executeAction(actionInfo) {
  actionInfo.action(function () {
    executeNextFeedActionLater();
  }, function () {
    return actionInfo.canceled;
  });
}

function executeNextFeedActionLater() {
  setTimeout(function () {
    if (feedActionsQueue.length > 0) {
      var actionInfo = feedActionsQueue.shift();
      executingFeedActionInfo = actionInfo;
      executeAction(actionInfo);
    } else {
      executingFeedActionInfo = null;
    }
  }, 100)
}

function scheduleFeedAction(action, cancel) {
  var actionInfo = {action: action, cancel: cancel};

  setTimeout(function () {
    if (!executingFeedActionInfo) {
      executingFeedActionInfo = actionInfo;
      executeAction(actionInfo);
    } else {
      feedActionsQueue.push(actionInfo);
    }
  }, 100);
}

function init(defaultViewMode) {
  var viewModeButtons = $('#view-mode').find('a');

  viewModeButtons.click(function (e) {
    e.preventDefault();
  });

  viewModeButtons.mousedown(function (e) {
    e.preventDefault();
    chooseViewMode(getViewModeByLink($(this)), true);
  });

  $(window).bind('popstate', function () {
    chooseViewModeFromUrl(defaultViewMode);
  });
  chooseViewModeFromUrl(defaultViewMode);
  return viewModeButtons;
}

$(document).ready(function () {
  $('#show-more').click(function (e) {
    e.preventDefault();
    clearErrors();
    $(this).prop('visibility', 'hidden');
    loadUnderProgress(false, null);
  });
});