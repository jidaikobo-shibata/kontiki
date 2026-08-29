<?php
/**
  * @var array $dashboardItems
  */
?>

<?php foreach ($dashboardItems as $controller => $links) : ?>
<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title">
            <span class="nav-icon fas fa-folder"></span>
            <?= e(__("x_management", ':name Management', ['name' => __($controller)])) ?>
        </h2>
    </div>
    <div class="card-body">
        <ul>
        <?php foreach ($links as $link) : ?>
          <li>
            <a href="<?= e($link['path']) ?>">
              <?= __($link['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endforeach; ?>
