<?php

/**
  * @var array $data
  * @var string $copyright
  * @var string $csrfToken
  */
?>
<div class="login-box">
  <div class="login-logo">
    <?= e($copyright) ?>
  </div>

  <div class="card">
    <div class="card-body login-card-body">

      <form action="./login" method="post">

        <input type="hidden" name="_csrf_value" value="<?= e($csrfToken) ?>">

        <label for="username"><?= __('username', 'Username') ?></label>
        <div class="input-group mb-3">
          <input
            type="text"
            name="username"
            id="username"
            class="form-control"
            required
            value="<?= e($data['username']) ?>"
          >
          <span class="input-group-text">
            <span class="fas fa-user" aria-hidden="true"></span>
          </span>
        </div>

        <label for="password"><?= __('password', 'Password') ?></label>
        <div class="input-group mb-3">
          <input type="password" name="password" id="password" class="form-control" required>
          <span class="input-group-text">
            <span class="fas fa-lock" aria-hidden="true"></span>
          </span>
        </div>

        <div class="d-grid">
          <input type="hidden" name="redirectUrl" value="<?= e($data['redirectUrl']) ?>">
          <button type="submit" class="btn btn-primary"><?= __('login', 'Login') ?></button>
        </div>

      </form>
    </div>
  </div>
</div>
