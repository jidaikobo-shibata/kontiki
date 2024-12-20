<div class="login-box">
  <div class="login-logo">
    <?php echo \jidaikobo\kontiki\Utils\Env::get('COPYRIGHT') ?>
  </div>

  <div class="card">
    <div class="card-body login-card-body">

      <form action="./login" method="post">

        <label for="username"><?php echo \jidaikobo\kontiki\Utils\Lang::get('username', 'Username') ?></label>
        <div class="input-group mb-3">
          <input type="text" name="username" id="username" class="form-control" required value="<?php echo $input['username'] ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>

        <label for="password"><?php echo \jidaikobo\kontiki\Utils\Lang::get('password', 'Password') ?></label>
        <div class="input-group mb-3">
          <input type="password" name="password" id="password" class="form-control" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <div class="col-4">
          <button type="submit" class="btn btn-primary btn-block"><?php echo \jidaikobo\kontiki\Utils\Lang::get('login', 'Login') ?></button>
        </div>

      </form>
    </div>
  </div>
</div>
