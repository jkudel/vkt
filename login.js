var onTheFlyValidationEnabled = false;

function validateLoginForm() {
  var result = true;
  result = validateRequiredField($('#login-user-name'), 'Введите имя пользователя') && result;
  result = validateRequiredField($('#login-password'), 'Введите пароль') && result;
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
  var userName = $('#user-name');
  var password = $('#password');
  var repeatPassword = $('#repeat-password');
  var result = true;

  if (!userName.val()) {
    userName.next('span').text('Введите имя пользователя');
    result = false;
  } else if (!userName.match(/[^A-Za-z0-9_]/)) {
    userName.next('span').text('Имя пользователя может содержать только буквы латинского алфавита, цифры и символ подчеркивания');
    result = false;
  } else {
    userName.next('span').text('');
  }
  var validateRepeatPassword = false;

  if (!password.val()) {
    password.next('span').text('Введите пароль');
    result = false;
  } else if (password.length < 4) {
    password.next('span').text('Пароль должен быть минимум 4 символа');
    result = false;
  } else {
    password.next('span').text('');
    validateRepeatPassword = true;
  }

  if (validateRepeatPassword) {
    var error = '';

    if (!repeatPassword.val()) {
      error = 'Повторите пароль';
      result = false;
    }
    else if (password.val() != repeatPassword.val()) {
      error = 'Пароли не совпадают';
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

    if ($('#already-registered').is(':checked')) {
      $('#login-form').fadeIn('fast');
      $('#register-form').hide();
    } else {
      $('#login-form').hide();
      $('#register-form').fadeIn('fast');
    }
  });
});