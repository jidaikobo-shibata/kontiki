<?php
/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $title
  * @var string $h1
  * @var string $content
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
    $basePath = env('BASEPATH', '');
    $faviconPath = env('ADMIN_FAVICON_PATH', '');
    if (!empty($faviconPath)) :
        echo '  <link rel="shortcut icon" href="' . $basePath . '/' . $faviconPath . '">';
    endif;
    ?>

  <!-- AdminLTE CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc4/dist/css/adminlte.min.css">

  <!-- AdminLTE JavaScript -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc4/dist/js/adminlte.min.js"></script>

  <!-- Scripts -->
  <script src="<?= $basePath ?>/kontiki-file-csrf.js"></script>
  <script src="<?= $basePath ?>/kontiki-file-utils.js"></script>
  <script src="<?= $basePath ?>/kontiki-file-lightbox.js"></script>
  <script src="<?= $basePath ?>/kontiki-file-uploader.js"></script>
  <script src="<?= $basePath ?>/kontiki-file-index.js"></script>
  <script src="<?= $basePath ?>/kontiki-file.js"></script>
  <script src="<?= $basePath ?>/kontiki-admin.js"></script>

  <!-- CSS -->
  <link rel="stylesheet" href="<?= $basePath ?>/kontiki-admin.css">
  <link rel="stylesheet" href="<?= $basePath ?>/kontiki-file.css">

  <title><?= e($title ?? $pageTitle) ?></title>
</head>

<body class="layout-fixed sidebar-expand-lg">
<!-- .app-wrapper -->
<div class="app-wrapper">

  <!-- .app-header -->
  <nav class="app-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="#" role="button" data-lte-toggle="sidebar" aria-controls="main-sidebar">
          <span class="fas fa-bars" aria-hidden="true"></span>
        </a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto flex-row flex-wrap">
      <li class="nav-item">
        <a href="<?= $basePath ?>/account/settings" class="nav-link"><?= __('account_settings') ?></a>
      </li>
      <li class="nav-item">
        <a href="<?= $basePath ?>/help" class="nav-link" target="helpWindow"><?= __('help') ?></a>
      </li>
      <li class="nav-item">
        <a href="<?= $basePath ?>/logout" class="nav-link"><?= __('logout') ?></a>
      </li>
    </ul>
  </nav><!-- /.app-header -->

  <!-- sidebar -->
  <?php require 'sidebar.php'; ?>

  <!-- .app-main -->
  <main class="app-main">
    <section class="content-header" id="content-header">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 class="h4 my-4 lh-sm"><?= e($h1 ?? $pageTitle) ?></h1>
      </div>
    </section>

    <div class="content pb-5">
      <div class="container-fluid" id="kontiki-main">
        <?= $content ?>
      </div>
    </div>
  </main><!-- /.app-main -->

  <!-- .app-footer -->
  <footer class="app-footer">
    <?= env('COPYRIGHT', '') ?>
  </footer><!-- /.app-footer -->

</div><!-- /.app-wrapper -->
</body>
</html>
