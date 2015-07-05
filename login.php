<script type="text/javascript" src="login.js"></script>

<div class="field">
  <input id="already-registered" type="radio" name="login_or_register"/>
  <label for="already-registered">Уже зарегистрирован</label>
  <input id="new-user" type="radio" name="login_or_register"/>
  <label for="new-user">Новый пользователь</label>
</div>
<form id="login-form" action="#" method="POST">
  <div class="field">
    <label for="login-user-name">Имя пользователя:</label>
    <input id="login-user-name" name="user-name" type="text"/>
    <span></span>
  </div>
  <div class="field">
    <label for="login-password">Пароль:</label>
    <input id="login-password" name="password" type="password"/>
    <span></span>
  </div>
  <div><input type="submit" value="Войти"/></div>
</form>
<form id="register-form" class="hidden" action="#" method="POST" autocomplete="off" aria-autocomplete="">
  <div class="field">
    <label for="register-user-name">Логин:</label>
    <input id="register-user-name" name="user-name" type="text" autocomplete="off"/>
    <span></span>
  </div>
  <div class="field">
    <label for="register-password">Пароль:</label>
    <input id="register-password" name="password" type="password" autocomplete="off"/>
    <span></span>
  </div>
  <div class="field">
    <label for="register-repeat-password">Повтор пароля:</label>
    <!--suppress HtmlFormInputWithoutLabel -->
    <!--workaround of ignoring autocomplete="off" by Chrome-->
    <input type="password" name='password' class="hidden" autocomplete="off" disabled="disabled">
    <input id="register-repeat-password" name="repeat-password" type="password" autocomplete="off"/>
    <span></span>
  </div>
  <div class="field">
    <label for="register-role">Роль:</label>
    <select id="register-role" name="role">
      <?php foreach (ROLES_NAMES as $roleId => $roleName) { ?>
        <option value="<?= $roleId ?>" selected><?= $roleName ?></option>
      <?php } ?>
    </select>
    <span></span>
  </div>
  <div><input type="submit" value="Зарегистрироваться"/></div>
</form>
<div id="error-placeholder"></div>