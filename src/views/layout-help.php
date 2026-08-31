<?php

/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $content
  * @var string $basePath
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link
    rel="stylesheet"
    href="<?= e($basePath) ?>/vendor/bootstrap.min.css"
  >
  <title><?= e($pageTitle) ?></title>
</head>
<body>

<header class="container">
     <h1><?= e($pageTitle) ?></h1>
</header>

<main class="container">

<?= $content ?>

</main>

</body>
</html>
