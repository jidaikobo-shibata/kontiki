<?php
/**
  * @var array $sidebarItems
  */
?>
<aside id="main-sidebar" class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= env('BASEURL', '#') ?>" class="brand-link" target="homepage"><span class="brand-text fw-bold"><?= env('COPYRIGHT', '') ?></span></a>
  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
      <li class="nav-item">
        <a class="nav-link fw-bold" href="<?= env('BASEPATH', '') ?>/dashboard">
          <span class="nav-icon fas fa-house"></span>
          <p><?= __('management_portal') ?></p>
        </a>
      </li>
      <?php
        foreach ($sidebarItems as $controller => $links) :
            $dataPath = env('BASEPATH') . '/' . $controller;
            ?>
          <li class="nav-item" data-path="<?= e($dataPath) ?>">
            <a href="#" class="nav-link fw-bold" aria-expanded="false">
              <span class="nav-icon fas fa-folder"></span>
              <p>
              <?= e(__("x_management", ':name Management', ['name' => __($controller)])) ?>
                <i class="nav-arrow fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
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
