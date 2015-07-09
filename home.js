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
        var list = response['list'];
        block.append(buildOrdersListBlock(list, buildBlockFunc));
        var showMoreButton = block.next().children('.show-more');

        if (response['has_more'] == 'true') {
          showMoreButton.show();
        } else {
          showMoreButton.hide();
        }
        if (runIfSuccess) {
          var lastOrder = list[list.length - 1];
          runIfSuccess(lastOrder ? lastOrder['id'] : null);
        }
      }
    },
    error: function (response) {
      errorPlaceholder.text(msg('internal.error'));
      console.error(response);
    }
  });
}

function buildOrdersListBlock(list, func) {
  var builder = ['<div>'];

  for (var i = 0; i < list.length; i++) {
    builder.push('<div>');
    builder.push(func(list[i]));
    builder.push('</div>');
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