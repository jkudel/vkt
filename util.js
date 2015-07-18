const ERROR_CODE_NO_OBJECT = 3;

function msg() {
  var key = arguments[0];
  var args = Array.prototype.slice.call(arguments, 1);
  var format = messages[key];

  return format.replace(/{(\d+)}/g, function (match, number) {
    return number in args ? args[number] : match;
  });
}

function getCommonConstant(key) {
  return commonConstants[key];
}

function getUrlParameters() {
  var parametersStr = window.location.search.substr(1);
  return parametersStr != null && parametersStr != "" ? paramsStrToObject(parametersStr) : {};
}

function paramsStrToObject(s) {
  if (s.length == 0) {
    return {};
  }
  if (s.charAt(0) == '?') {
    s = s.substr(1);
  }
  var result = {};
  var params = s.split("&");

  for (var i = 0; i < params.length; i++) {
    var t = params[i].split("=");
    result[t[0]] = t[1];
  }
  return result;
}

function buildParamsStr(paramsMap) {
  var builder = [];
  for (var key in paramsMap) {
    if (paramsMap.hasOwnProperty(key)) {
      var value = paramsMap[key];

      if (value) {
        builder.push(encodeURIComponent(key) + "=" + encodeURIComponent(value));
      }
    }
  }
  var s = builder.join("&");
  return s.length == 0 ? s : '?' + s;
}

const ENTITY_MAP = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  '\'': '&#39;',
  '/': '&#x2F;'
};

function escapeHtml(string) {
  return String(string).replace(/[&<>"'\/]/g, function (s) {
    return ENTITY_MAP[s];
  });
}

function escapeMultiLineString(s) {
  return escapeHtml(s).replace(/(?:\r\n|\r|\n)/g, '<br/>');
}