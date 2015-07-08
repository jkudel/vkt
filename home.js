function doReloadOrders(url, block, errorPlaceholder, buildBlockFunc) {
  // todo: progress
  block.html('');

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
        block.html(buildDivListBlock(response, buildBlockFunc));
      }
    },
    error: function (response) {
      errorPlaceholder.text(msg('internal.error'));
      console.error(response);
    }
  });
}

function buildDivListBlock(list, func) {
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