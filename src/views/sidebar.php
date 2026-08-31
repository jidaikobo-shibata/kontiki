<?php
/**
  * @var array $sidebarItems
  * @var string $basePath
  * @var string $homeUrl
  * @var string $copyright
  */
?>
<aside id="main-sidebar" class="kontiki-sidebar shadow" data-bs-theme="dark">
  <div class="kontiki-sidebar-brand">
    <a href="<?= e($homeUrl) ?>" class="kontiki-brand-link" target="homepage">
      <span class="kontiki-brand-text fw-bold"><?= e($copyright) ?></span>
    </a>
  </div>
  <div class="kontiki-sidebar-content">
    <nav class="mt-2" id="navigation" aria-label="<?= e(__('management_portal')) ?>">
      <ul class="nav kontiki-sidebar-menu flex-column">
      <li class="nav-item">
        <a class="nav-link fw-bold" href="<?= e($basePath) ?>/dashboard">
          <?= icon('house', 'nav-icon') ?>
          <p><?= __('management_portal') ?></p>
        </a>
      </li>
      <?php
        foreach ($sidebarItems as $controller => $links) :
            $dataPath = $basePath . '/' . $controller;
            $submenuId = 'kontiki-submenu-' . preg_replace('/[^a-z0-9_-]/i', '-', $controller);
            ?>
          <li class="nav-item" data-path="<?= e($dataPath) ?>">
            <button
              type="button"
              class="nav-link fw-bold w-100"
              aria-expanded="false"
              aria-controls="<?= e($submenuId) ?>"
            >
              <?= icon('folder', 'nav-icon') ?>
              <p>
              <?= e(__("x_management", ':name Management', ['name' => __($controller)])) ?>
                <?= icon('chevron-left', 'nav-arrow') ?>
              </p>
            </button>
            <ul class="nav kontiki-submenu" id="<?= e($submenuId) ?>" hidden>
              <?php foreach ($links as $link) : ?>
                <li class="nav-item">
                  <a href="<?= e($link['path']) ?>" class="nav-link">
                    <p><?= e($link['name']); ?></p>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</aside>
