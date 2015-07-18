var onTheFlyValidationEnabled = false;
var newUserViewMode = false;

function validateLoginForm(onTheFly) {
  var result = true;
  result = validateRequiredField($('#login-user-name'), msg('no.username.error'), onTheFly) && result;
  result = validateRequiredField($('#login-password'), msg('no.password.error'), onTheFly) && result;
  return result;
}

function validateRequiredField(element, errorMessage, onTheFly) {
  var errorPlaceholder = getErrorPlaceholder(element);

  if (element.val()) {
    errorPlaceholder.text('');
    errorPlaceholder.hide();
    return true;
  } else {
    errorPlaceholder.text(errorMessage);

    if (!onTheFly) {
      errorPlaceholder.show();
    }
    return false;
  }
}

function getErrorPlaceholder(field) {
  return field.next('.error-placeholder');
}

function validateRegisterForm(onTheFly) {
  var userName = $('#register-user-name');
  var password = $('#register-password');
  var repeatPassword = $('#register-repeat-password');
  var result = true;
  var userVal = userName.val();
  var userNameMaxLength = getCommonConstant('user.name.max.length');
  var userNameErrorPlaceholder = getErrorPlaceholder(userName);

  if (!onTheFly) {
    userNameErrorPlaceholder.show();
  }
  if (!userVal) {
    userNameErrorPlaceholder.text(msg('no.username.error'));
    result = false;
  } else if (userVal.length > userNameMaxLength) {
    userNameErrorPlaceholder.text(msg('user.name.length.error', userNameMaxLength));
    result = false;
  }  else if (!userVal.match(/^\w+$/)) {
    userNameErrorPlaceholder.text(msg('invalid.char.in.username.error'));
    result = false;
  } else {
    userNameErrorPlaceholder.text('');
    userNameErrorPlaceholder.hide();
  }
  var validateRepeatPassword = false;
  var passwordVal = password.val();
  var passwordMinLength = getCommonConstant('password.min.length');
  var passwordMaxLength = getCommonConstant('password.max.length');
  var passwordErrorPlaceholder = getErrorPlaceholder(password);

  if (!onTheFly) {
    passwordErrorPlaceholder.show();
  }
  if (!passwordVal) {
    passwordErrorPlaceholder.text(msg('no.password.error'));
    result = false;
  } else if (!onTheFly && passwordVal.length < passwordMinLength || passwordVal.length > passwordMaxLength) {
    passwordErrorPlaceholder.text(msg('password.length.error', passwordMinLength, passwordMaxLength));
    result = false;
  } else {
    passwordErrorPlaceholder.text('');
    passwordErrorPlaceholder.hide();
    validateRepeatPassword = true;
  }
  var repeatPasswordErrorPlaceholder = getErrorPlaceholder(repeatPassword);

  if (!onTheFly) {
    repeatPasswordErrorPlaceholder.show();
  }
  if (validateRepeatPassword) {
    var error = '';

    if (!repeatPassword.val()) {
      error = msg('no.repeat.password');
      result = false;
    }
    else if (passwordVal != repeatPassword.val()) {
      error = msg('passwords.matching.error');
      result = false;
    }
    repeatPasswordErrorPlaceholder.text(error);
  } else {
    repeatPasswordErrorPlaceholder.text('');
    repeatPasswordErrorPlaceholder.hide();
  }
  return result;
}

function submitFormAndGoHome(url, form) {
  ajaxSubmitForm(url, form, function () {
    window.location.href = 'index.php';
  }, function (errorMessage, errorCode, response) {
    var fieldName = response['field_name'];
    var errorPlaceholder = fieldName ? getErrorPlaceholder(form.find('input[name="' + fieldName + '"]')) : null;

    if (errorPlaceholder && errorPlaceholder.length) {
      errorPlaceholder.text(errorMessage);
      errorPlaceholder.show();
    } else {
      var globalErrorPlaceholder = $('#global-error-placeholder');
      globalErrorPlaceholder.text(errorMessage);
      globalErrorPlaceholder.show();
    }
  });
}

function updateFormShown() {
  $('#login-form').parent().toggle(!newUserViewMode);
  $('#register-form').parent().toggle(newUserViewMode);
}

function setViewMode(value) {
  newUserViewMode = value;
  $('#already-registered').toggleClass('checked', !newUserViewMode);
  $('#new-user').toggleClass('checked', newUserViewMode);
}
function resetPageState() {
  setViewMode(getUrlParameters()['new'] == 'true');
  updateFormShown();
}

function modeSwitched() {
  onTheFlyValidationEnabled = false;
  var allErrorPlaceholders = $('.error-placeholder');
  allErrorPlaceholders.text('');
  allErrorPlaceholders.hide();
  updateFormShown();
  history.pushState({}, '', '?new=' + newUserViewMode);
}

$(document).ready(function () {
  $('#login-form').submit(function (e) {
    e.preventDefault();
    $('#global-error-placeholder').text('');

    if (validateLoginForm(false)) {
      submitFormAndGoHome(AJAX_LOGIN, $('#login-form'));
    }
    onTheFlyValidationEnabled = true;
  });
  $('#login-user-name, #login-password').on('input', function () {
    if (onTheFlyValidationEnabled) {
      validateLoginForm(true);
    }
  });
  $('#register-form').submit(function (e) {
    e.preventDefault();
    $('#global-error-placeholder').text('');

    if (validateRegisterForm(false)) {
      submitFormAndGoHome(AJAX_REGISTER, $('#register-form'));
    }
    onTheFlyValidationEnabled = true;
  });
  $('#register-user-name, #register-password, #register-repeat-password').on('input', function() {
    if (onTheFlyValidationEnabled) {
      validateRegisterForm(true);
    }
  });
  $('#already-registered').mousedown(function () {
    setViewMode(false);
    modeSwitched();
  });
  $('#new-user').mousedown(function () {
    setViewMode(true);
    modeSwitched();
  });
  $(window).bind('popstate', resetPageState);
  resetPageState();
});