const ORDER_LIST_PART_SIZE = 4;
const VIEW_MODE_PARAM = 'view-mode';

const ERROR_CODE_NO_OBJECT = 3;

var buildOrderBlockInFeed = null; // function
var loadOrders = null; // function

var feedOrdersIdSet = {};
var newestOrder = null;
var feedOrders = [];
var viewMode = null;
var feedActionsQueue = [];
var executingFeedActionInfo = null;

function buildParamsOlderThanOrders(timeFieldName) {
  var result = {count: ORDER_LIST_PART_SIZE};
  var oldestOrder = feedOrders.length > 0 ? feedOrders[feedOrders.length - 1] : null;

  if (!oldestOrder) {
    return result;
  }
  result['up_time'] = oldestOrder[timeFieldName];
  result['up_order_id'] = oldestOrder['order_id'];
  return result;
}

function buildParamsNewerThanOrders(timeFieldName) {
  var result = {count: ORDER_LIST_PART_SIZE};

  if (!newestOrder) {
    return result;
  }
  result['lw_time'] = newestOrder[timeFieldName];
  result['lw_order_id'] = newestOrder['order_id'];
  return result;
}

function addOrdersToFeedSet(response, filter) {
  var list = response['list'];

  if (!list) {
    return null;
  }
  var filteredList = [];

  for (var i = 0; i < list.length; i++) {
    var order = list[i];
    var orderId = order['order_id'];

    if (!feedOrdersIdSet[orderId] && (!filter || filter(orderId))) {
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
  newestOrder = feedOrders[0];
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

function appendLoadedOrdersToFeed(response, filter) {
  var filteredList = addOrdersToFeedSet(response, filter);

  if (filteredList) {
    feedOrders = feedOrders.concat(filteredList);

    if (!newestOrder) {
      newestOrder = feedOrders[0];
    }
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
    return msg('just.now');
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
  cancelAllFeedActions(true);

  if (progress == null) {
    var ordersPanel = $('.orders-panel');
    ordersPanel.before('<div class="progress big top"></div>');
    progress = initProgress(ordersPanel.prev());
  }
  loadUnderProgress(true, null, null, progress, function() {
    cancelAllFeedActions(false);
  });
}

function loadUnderProgress(reload, count, errorPlaceholder, progress, callback) {
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
    if (callback) {
      callback();
    }
  });
}

function scheduleLoadingOrders(reload, count, errorPlaceholder, finished) {
  var clearUiIfReload = function() {
    if (reload) {
      $('#orders').html('');
    }
  };
  var canceledCallback = function () {
    clearUiIfReload();
    finished();
  };
  scheduleFeedAction(function (runAfter, canceledFunc) {
    if (canceledFunc()) {
      return;
    }
    var errorCallback = function (errorMessage) {
      if (canceledFunc()) {
        return;
      }
      clearUiIfReload();

      if (!errorPlaceholder) {
        errorPlaceholder = $('#bottom-error-placeholder');
      }
      errorPlaceholder.text(errorMessage);
      errorPlaceholder.show();
      finished();
      runAfter();
    };
    if (reload) {
      feedOrders = [];
      feedOrdersIdSet = {};
      newestOrder = null;
    }
    loadOrders(reload, count, errorCallback, canceledFunc, function () {
      if (canceledFunc()) {
        return;
      }
      clearUiIfReload();
      finished();
      runAfter();
    });
  }, canceledCallback);
}

function cancelAllFeedActions(currentlyExecuted) {
  if (executingFeedActionInfo) {
    if (currentlyExecuted) {
      executingFeedActionInfo.canceled = true;
      executingFeedActionInfo.cancel();
      executingFeedActionInfo = null;
    }
    for (var i = 0; i < feedActionsQueue.length; i++) {
      var actionInfo = feedActionsQueue[i];
      actionInfo.canceled = true;
      actionInfo.cancel();
    }
    feedActionsQueue = [];
  }
}

function executeActionLater(actionInfo) {
  executingFeedActionInfo = actionInfo;

  setTimeout(function () {
    actionInfo.action(function () {
      if (feedActionsQueue.length > 0) {
        executeActionLater(feedActionsQueue.shift());
      } else {
        executingFeedActionInfo = null;
      }
    }, function () {
      return actionInfo.canceled;
    });
  }, 100);
}

function scheduleFeedAction(action, cancel) {
  var actionInfo = {action: action, cancel: cancel};

  if (!executingFeedActionInfo) {
    executeActionLater(actionInfo);
  } else {
    feedActionsQueue.push(actionInfo);
  }
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