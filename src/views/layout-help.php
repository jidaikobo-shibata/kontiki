<?php

/**
  * @var string $lang
  * @var string $pageTitle
  * @var string $content
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
