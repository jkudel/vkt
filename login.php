<script type="text/javascript" src="login.js"></script>

<div class="login-panel">
  <div class="toggle-panel">
    <input id="already-registered" class="toggle" type="button" value="<?= msg('already.registered') ?>"/>
    <input id="new-user" class="toggle" type="button" value="<?= msg('new.user') ?>"/>
  </div>
  <form id="login-form" class="hidden" action="#" method="POST">
    <div class="field">
      <label for="login-user-name"><?= msg('user.name') ?>:</label>

      <div class="input-element">
        <input id="login-user-name" class="fill" name="user-name" type="text"/>

        <div class="error-placeholder"></div>
      </div>
    </div>
    <div class="field">
      <label for="login-password"><?= msg('password') ?>:</label>

      <div class="input-element">
        <input id="login-password" class="fill" name="password" type="password"/>

        <div class="error-placeholder"></div>
      </div>
    </div>
    <div class="field last"><input class="button" type="submit" value="<?= msg('sign.in') ?>"/></div>
  </form>
  <form id="register-form" class="hidden" action="#" method="POST" autocomplete="off" aria-autocomplete="">
    <div class="field">
      <label for="register-user-name"><?= msg('user.name') ?>:</label>

      <div class="input-element">
        <input id="register-user-name" class="fill" name="user-name" type="text" autocomplete="off"
               maxlength="<?= getCommonConstant('user.name.max.length') ?>"/>

        <div class="error-placeholder"></div>
      </div>
    </div>
    <div class="field">
      <label for="register-password"><?= msg('password') ?>:</label>

      <div class="input-element">
        <input id="register-password" class="fill" name="password" type="password" autocomplete="off"
          maxlength="<?= getCommonConstant('user.name.max.length') ?>"/>

        <div class="error-placeholder"></div>
      </div>
    </div>
    <div class="field">
      <label for="register-repeat-password"><?= msg('repeat.password') ?>:</label>

      <div class="input-element">
        <!--suppress HtmlFormInputWithoutLabel -->
        <!--workaround of ignoring autocomplete="off" by Chrome-->
        <input type="password" name='password' class="hidden" autocomplete="off" disabled="disabled">
        <input id="register-repeat-password" class="fill" name="repeat-password" type="password" autocomplete="off"/>

        <div class="error-placeholder"></div>
      </div>
    </div>
    <div class="field">
      <label for="register-role"><?= msg('role') ?>:</label>

      <div class="fill">
        <?php $i = 0;
        foreach ($allRoleNames as $roleId => $roleName) { ?>
          <input type="radio" id="role-button-<?= $i ?>" value="<?= $roleId ?>"
                 name="role" <?= $i == 0 ? 'checked' : '' ?>/>
          <label for="role-button-<?= $i ?>" class="toggle"><?= $roleName ?></label>
          <?php $i++;
        } ?>
      </div>
    </div>
    <div class="field last"><input class="button" type="submit" value="<?= msg('sign.up') ?>"/></div>
  </form>
  <div id="global-error-placeholder" class="error-placeholder"></div>
</div>