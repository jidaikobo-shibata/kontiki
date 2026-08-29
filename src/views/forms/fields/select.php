<?php
/**
  * @var string $name
  * @var string $id
  * @var string $attributes
  * @var string $ariaDescribedbyAttribute
  * @var string $value
  * @var string $description
  * @var array $options
  */
?>

<select name="<?= $name; ?>" id="<?= $id; ?>" <?= $attributes ?><?= $ariaDescribedbyAttribute ?>>
<?php
foreach ($options as $optionKey => $optionValue) :
    $selected = $optionKey == $value ? ' selected="selected"' : '' ;
    ?>
<option <?= $selected ?>value="<?= $optionKey ?>"><?= $optionValue ?></option>
    <?php
endforeach;
?>
</select>
<?= $description ?>
