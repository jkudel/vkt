<form id="form" action="#" method="POST">
  <div class="field">
    <label for="user-name">Логин:</label>
    <input id="user-name" name="user-name" type="text"/>
    <span></span>
  </div>
  <div class="field">
    <label for="password">Пароль:</label>
    <input id="password" name="password" type="password"/>
    <span></span>
  </div>
  <div><input type="submit" value="Войти"/></div>
  <div id="error-placeholder"></div>
</form>

<script type="text/javascript">
  var onTheFlyValidationEnabled = false;

  function validateForm() {
    var result = true;
    result = validateField($('#user-name'), 'Введите имя пользователя') && result;
    result = validateField($('#password'), 'Введите пароль') && result;
    return result;
  }

  function validateField(element, errorMessage) {
    if (element.val()) {
      element.find('+span').text('');
      return true;
    } else {
      element.find('+span').text(errorMessage);
      return false;
    }
  }

  $(document).ready(function () {
    $('#form').submit(function (e) {
      e.preventDefault();

      if (validateForm()) {
        submitFormAndGoHome('do_login', $('#form'), $('#error-placeholder'));
      }
      if (!onTheFlyValidationEnabled) {
        onTheFlyValidationEnabled = true;

        $('#user-name, #password').on('input', function () {
          validateForm();
        });
      }
    });
  });
</script>