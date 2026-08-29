<?php
/**
  * @var string $viewUrl
  * @var string $actionAttribute
  * @var string $csrfToken
  * @var string $formHtml
  * @var array $formVars
  * @var string $buttonPosition
  */

$slug = $formVars['data']['slug'] ?? '';
if (!empty($viewUrl) && !empty($slug)) :
    $publishedUrl = e($viewUrl) . '/' . e($slug);
    $html = '';
    $html .= '<p id="publishedUrl" class="form-text"><span id="publishedUrlLabel">';
    $html .= __('published_url') . '</span>: ';
    $html .= '<span id="publishedUrlText">';
    $html .= $publishedUrl . ' ';
    $html .= '</span></p>';
    echo $html;
endif; ?>

<?php if (isset($formVars['description'])) : ?>
<p class="alert alert-primary"><?= e($formVars['description']) ?></p>
<?php endif; ?>

<!-- this <div> is for prevent DOMDocument from expanding a group of form elements -->
<div class="dontExpandForm">
<form action="<?= e($actionAttribute) ?>" method="post">
    <input type="hidden" name="_csrf_value" value="<?= e($csrfToken) ?>">
    <div class="row">
        <?= $formHtml ?>
    </div>

    <?php
    if ($buttonPosition == 'main') :
        include(__DIR__ . '/buttons.php');
    endif;
    ?>
</form>
</div>

<?php
include(__DIR__ . '/incFileUploader.php');
include(__DIR__ . '/incFileIndex.php');
