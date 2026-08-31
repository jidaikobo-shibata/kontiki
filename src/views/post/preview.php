<?php

/**
  * @var string $lang
  * @var array $data
  * @var string $copyright
  */
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" media="all" href="https://dev.jidaikobo.dev/kontikip/assets/css/style.css">

  <!-- bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
