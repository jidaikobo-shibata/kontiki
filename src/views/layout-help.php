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
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
    crossorigin="anonymous"
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
