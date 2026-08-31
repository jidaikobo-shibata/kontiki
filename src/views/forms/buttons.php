<?php
/**
  * @var bool $is_previewable
  * @var array $formVars
  */
?>

<?php
$buttonGroupClass = $is_previewable === true
    ? 'd-flex flex-column align-items-center gap-2 mt-3'
    : 'd-flex gap-2 mt-3';

if ($is_previewable === true) :
    ?>
<div class="<?= $buttonGroupClass ?>">
<button type="submit" class="btn btn-info" name="preview" value="1" formtarget="preview">
    <?= __('preview') ?>
    <?= icon('box-arrow-up-right') ?>
</button>
<?php endif; ?>

<button id="<?= $formVars['buttonID'] ?? '' ?>" type="submit" class="btn <?= $formVars['buttonClass'] ?? 'btn-primary' ?>"><?= $formVars['buttonText'] ?? '' ?></button>
</div>
