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
  <div class="field">
    <label for="repeat-password">Повтор пароля:</label>
    <input id="repeat-password" name="repeat-password" type="password"/>
    <span></span>
  </div>
  <div class="field">
    <label for="role">Роль:</label>
    <select id="role" name="role">
      <?php foreach (get_all_roles() as $role) { ?>
        <option value="<?= $role ?>" selected><?= get_role_name($role) ?></option>
      <?php } ?>
    </select>
    <span></span>
  </div>
  <div><input type="submit" value="Зарегистрироваться"/></div>
  <div id="error-placeholder"></div>
</form>

<script type="text/javascript">
  var onTheFlyValidationEnabled = false;

  function validateForm() {
    var userName = $('#user-name');
    var password = $('#password');
    var repeatPassword = $('#repeat-password');
    var result = true;

    if (userName.val()) {
      userName.find('+span').text('');
    } else {
      userName.find('+span').text('Введите имя пользователя');
      result = false;
    }
    if (password.val()) {
      password.find('+span').text('');
    } else {
      password.find('+span').text('Введите пароль');
      result = false;
    }
    if (password.val()) {
      var error = '';

      if (!repeatPassword.val()) {
        error = 'Повторите пароль';
        result = false;
      }
      else if (password.val() != repeatPassword.val()) {
        error = 'Пароли не совпадают';
        result = false;
      }
      repeatPassword.find('+span').text(error);
    } else {
      repeatPassword.find('+span').text('');
    }
    return result;
  }

  $(document).ready(function () {
    $('#form').submit(function (e) {
      e.preventDefault();

      if (validateForm()) {
        submitFormAndGoHome('do_register', $('#form'), $('#error-placeholder'));
      }
      if (!onTheFlyValidationEnabled) {
        onTheFlyValidationEnabled = true;

        $('#user-name, #password, #repeat-password').on('input', function () {
          validateForm();
        });
      }
    });
  });
</script>