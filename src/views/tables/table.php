<?php
/**
  * @var array $createButton
  * @var array $displayModes
  * @var string $headers
  * @var string $rows
  */
?>

<?php
if (isset($createButton['path'])) :
    $html = '';
    $html .= '<a href="' . $createButton['path'] . '" class="btn btn-primary btn-sm" id="create_button_in_index">' . __('create') . '</a>';
    echo $html;
endif;
?>

<div class="container-fluid d-flex flex-wrap justify-content-between align-items-center bg-secondary-subtle py-0 p-2 mb-3">
    <nav class="navbar" aria-label="<?= __('display_filter') ?>">
        <ul class="nav nav-underline flex-row flex-wrap gap-3">
          <?php
          // set aria-current
          $reqPath = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
          $activeKey = null;
          $activeLen = -1;
          foreach ($displayModes as $key => $displayMode) {
              $path = rtrim($displayMode['path'], '/');
              if (preg_match('#^' . preg_quote($path, '#') . '(?:$|/)#', $reqPath)) {
                  $len = strlen($path);
                  if ($len > $activeLen) {
                      $activeLen = $len;
                      $activeKey = $key;
                  }
              }
          }

          $html = '';
          foreach ($displayModes as $key => $displayMode) :
              $isActive = ($key === $activeKey);
              $class = 'nav-link text-nowrap' . ($isActive ? ' active' : '');
              $aria  = $isActive ? ' aria-current="page"' : '';

              $html .= '<li class="nav-item me-3">';

              $html .= '<a href="' . $displayMode['path'];
              $html .= '" class="nav-link text-nowrap' . $class .'"' . $aria . '">';
              $html .= $displayMode['name'];
              $html .= '</a>';
              $html .= '</li>';
          endforeach;
          echo $html;
          ?>
        </ul>
    </nav>

    <nav class="navbar">
        <form method="get" action="" class="d-flex input-group" role="search">
            <label for="keywordSearch" class="input-group-text"><?= __('search_str', 'Search String')  ?></label>
            <input type="text" id="keywordSearch" name="s" value="<?= e(filter_input(INPUT_GET, 's') ?? '') ?>" class="form-control">
            <button type="submit" class="btn btn-outline-secondary"><?= __('search', 'Search')  ?></button>
        </form>
    </nav>
</div>

<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <?= $headers ?>
        </tr>
    </thead>
    <tbody>
        <?= $rows ?>
    </tbody>
</table>
