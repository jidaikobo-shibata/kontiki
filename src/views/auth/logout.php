<?php

/** @var string $csrfToken */
?>
<div class="login-box">
  <div class="card">
    <div class="card-body login-card-body">
      <p><?= __('logout_confirmation', 'Do you want to log out?') ?></p>
      <form action="./logout" method="post">
        <input type="hidden" name="_csrf_value" value="<?= e($csrfToken) ?>">
        <button type="submit" class="btn btn-primary btn-block">
          <?= __('logout', 'Logout') ?>
        </button>
      </form>
    </div>
  </div>
</div>
