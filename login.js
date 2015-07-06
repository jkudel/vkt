var onTheFlyValidationEnabled = false;

function validateLoginForm() {
  var result = true;
  result = validateRequiredField($('#login-user-name'), msg('no.username.error')) && result;
  result = validateRequiredField($('#login-password'), msg('no.password.error')) && result;
  return result;
}

function validateRequiredField(element, errorMessage) {
  if (element.val()) {
    element.next('span').text('');
    return true;
  } else {
    element.next('span').text(errorMessage);
    return false;
  }
}

function validateRegisterForm() {
  var userName = $('#register-user-name');
  var password = $('#register-password');
  var repeatPassword = $('#register-repeat-password');
  var result = true;
  var userVal = userName.val();

  if (!userVal) {
    userName.next('span').text(msg('no.username.error'));
    result = false;
  } else if (userVal.length > 20) {
    password.next('span').text(msg('user.name.length.error'));
    result = false;
  }  else if (!userVal.match(/^\w+$/)) {
    userName.next('span').text(msg('invalid.char.in.username.error'));
    result = false;
  } else {
    userName.next('span').text('');
  }
  var validateRepeatPassword = false;
  var passwordVal = password.val();

  if (!passwordVal) {
    password.next('span').text(msg('no.password.error'));
    result = false;
  } else if (passwordVal.length < 4 || passwordVal.length > 20) {
    password.next('span').text(msg('password.length.error'));
    result = false;
  } else {
    password.next('span').text('');
    validateRepeatPassword = true;
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
    repeatPassword.next('span').text(error);
  } else {
    repeatPassword.next('span').text('');
  }
  return result;
}

function submitFormAndGoHome(url, form) {
  $.ajax({
    url: url,
    type: "POST",
    dataType: "json",
    data: form.serialize(),

    success: function (response) {
      var errorMessage = response['error_message'];

      if (errorMessage) {
        var fieldName = response['field_name'];
        var field = fieldName ? form.find('input[name="' + fieldName + '"]+span') : null;

        if (field && field.length) {
          field.text(errorMessage)
        } else {
          $('#error-placeholder').text(errorMessage);
        }
      }
      else {
        window.location.href = '/';
      }
    },

    error: function (response) {
      console.error(response);
    }
  });
}

$(document).ready(function () {
  $('#login-form').submit(function (e) {
    e.preventDefault();

    if (validateLoginForm()) {
      submitFormAndGoHome('do_login', $('#login-form'));
    }
    onTheFlyValidationEnabled = true;
  });
  $('#login-user-name, #login-password').on('input', function () {
    if (onTheFlyValidationEnabled) {
      validateLoginForm();
    }
  });
  $('#register-form').submit(function (e) {
    e.preventDefault();

    if (validateRegisterForm()) {
      submitFormAndGoHome('do_register', $('#register-form'));
    }
    onTheFlyValidationEnabled = true;
  });
  $('#register-user-name, #register-password, #register-repeat-password').on('input', function() {
    if (onTheFlyValidationEnabled) {
      validateRegisterForm();
    }
  });
  $('#already-registered').prop('checked', true);
  $('#new-user').prop('checked', false);

  $('#already-registered, #new-user').change(function() {
    onTheFlyValidationEnabled = false;
    $('input+span').text('');
    $('#error-placeholder').text('');

    if ($('#already-registered').is(':checked')) {
      $('#login-form').fadeIn('fast');
      $('#register-form').hide();
    } else {
      $('#login-form').hide();
      $('#register-form').fadeIn('fast');
    }
  });
});