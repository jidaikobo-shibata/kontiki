<?php
/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $title
  * @var string $h1
  * @var string $content
  * @var string $basePath
  * @var string $faviconPath
  * @var string $copyright
  * @var string $csrfToken
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
    if (!empty($faviconPath)) :
        echo '  <link rel="shortcut icon" href="' . $basePath . '/' . $faviconPath . '">';
    endif;
    ?>

  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
  >

  <!-- Bootstrap JavaScript -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
  ></script>

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

<body class="kontiki-admin-page">
<?php require 'images/kontiki-icons.svg.php'; ?>
<div class="kontiki-shell">

  <header class="kontiki-header navbar navbar-expand bg-body">
    <ul class="navbar-nav">
      <li class="nav-item">
        <button
          class="nav-link btn btn-link"
          type="button"
          data-kontiki-toggle="sidebar"
          aria-controls="main-sidebar"
        >
          <?= icon('list') ?>
        </button>
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
        <form action="<?= $basePath ?>/logout" method="post" class="d-inline">
          <input type="hidden" name="_csrf_value" value="<?= e($csrfToken) ?>">
          <button type="submit" class="nav-link btn btn-link"><?= __('logout') ?></button>
        </form>
      </li>
    </ul>
  </header>

  <?php require 'sidebar.php'; ?>
  <button class="kontiki-sidebar-backdrop" type="button" tabindex="-1" aria-hidden="true"></button>

  <main class="kontiki-main">
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
  </main>

  <footer class="kontiki-footer">
    <?= e($copyright) ?>
  </footer>

</div>
</body>
</html>
