<?php
/**
  * @var string $fields_html
  * @var string $buttonPosition
  */
?>
<div class="col-lg-4 col-12">
<div class="card">
  <h2 class="card-header fs-6 fw-bold">
    <?= __('publishing_settings') ?>
  </h2>
  <div class="card-body">
    <p class="form-text"><?= __('publishing_settings_exp') ?></p>
    <?php echo $fields_html; ?>
  </div>
</div>
<?php
if ($buttonPosition == 'meta') :
    include(__DIR__ . '/../buttons.php');
endif;
?>
</div>
