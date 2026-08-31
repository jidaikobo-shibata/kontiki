<?php
/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $content
  * @var string $faviconPath
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

  <!-- AdminLTE CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
  >
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css"
    integrity="sha384-/22RbU9IW1QOMvhI7HxLRWesmfewMbZ0xN5fraeeNvBu5zb0XUea+arFfyvHQcwV"
    crossorigin="anonymous"
  >
</head>
<body class="login-page bg-body-secondary">
<?php require 'images/kontiki-icons.svg.php'; ?>

<main>

<?= $content ?>

</main>
</body>
</html>
