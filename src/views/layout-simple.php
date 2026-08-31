<?php
/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $content
  * @var string $faviconPath
  * @var string $basePath
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
    if (!empty($faviconPath)) :
        echo '  <link rel="shortcut icon" href="' . $faviconPath . '">';
    endif;
    ?>

  <title><?= e($pageTitle) ?></title>

  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="<?= e($basePath) ?>/vendor/bootstrap.min.css"
  >
  <link rel="stylesheet" href="<?= e($basePath) ?>/kontiki-admin.css">
</head>
<body class="kontiki-login-page bg-body-secondary">
<?php require 'images/kontiki-icons.svg.php'; ?>

<main class="kontiki-login-main container">

<?= $content ?>

</main>
</body>
</html>
