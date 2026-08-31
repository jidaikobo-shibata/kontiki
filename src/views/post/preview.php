<?php

/**
  * @var string $lang
  * @var array $data
  * @var string $copyright
  * @var string $basePath
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- bootstrap CSS -->
  <link
    rel="stylesheet"
    href="<?= e($basePath) ?>/vendor/bootstrap.min.css"
  >
  <title><?= e($data['title']) ?></title>
</head>

<body>
<div class="wrapper">

  <!-- .content-wrapper -->
  <main class="content-wrapper">
<?php
echo '<header><h1>' . e($data['title']) . '</h1></header>';
echo '<main>' . Jidaikobo\MarkdownExtra::defaultTransform($data['content']) . '</main>';
?>
  </main><!-- /.content-wrapper -->

  <!-- .main-footer -->
  <footer class="main-footer">
    <?= e($copyright) ?>
  </footer><!-- /.main-footer -->
</div>
</body>
</html>
