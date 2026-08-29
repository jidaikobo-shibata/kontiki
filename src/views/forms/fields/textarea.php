<?php

/**
  * @var string $name
  * @var string $id
  * @var string $attributes
  * @var string $ariaDescribedbyAttribute
  * @var string $value
  * @var string $description
  */
?>

<textarea name="<?= $name; ?>" id="<?= $id; ?>" <?= $attributes ?><?= $ariaDescribedbyAttribute ?>><?= $value; ?></textarea>
<?= $description ?>
