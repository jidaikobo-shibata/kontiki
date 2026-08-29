<?php
/**
  * @var string $pagination
  * @var array $items
  */
?>
<ul class="mt-4">
    <li><?= __('file_desc_copy_url', 'You can copy the URL by clicking <code>Copy URL</code>') ?></li>
    <li><?= __('file_desc_edit', 'You can edit the description by clicking <code>Edit</code>.') ?></li>
    <li id="eachDescriptionHelp"><?= __('desc_for_description', 'For images, it is used as the <code>alt attribute value</code>, and for PDFs, etc., it is used as the <code>link text</code>.') ?></li>
</ul>

<!-- table-responsive -->
<div class="table-responsive">
<?= $pagination; ?>
<table class="table table-bordered table-hover table-striped">
    <thead class="table-light">
        <tr>
            <th class="text-center">ID</th>
            <th class="w-25"><?= __('file') ?></th>
            <th><?= __('value') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $file) : ?>
            <?php $fileId = intval($file['id']) ?>
            <tr>
                <th class="text-center"><?= $fileId; ?></th>
                <td class="align-middle">
                    <div class="text-center">
                    <?php
                    echo $file['imageOrLink'];
                    ?>
                    </div>
                    <div class="text-center text-nowrap"><a href="#" class="text-danger file-delete-link" data-confirm="critical" data-delete-id="<?= $fileId ?>" data-csrf_token=""><?= __('delete_it_completely') ?></a></div>
                </td>
                <td class="eachFile">

                    <table class="table table-bordered m-0">
                    <tr>
                        <th class="align-middle" scope="row">URL</th>
                        <td class="text-break"><span class="fileUrl"><?= e($file['url']); ?></span></td>
                        <td class="text-nowrap align-middle"><a href="#" class="fileCopyUrl"><?= __('copy_url') ?></a></td>
                    </tr>
                    <tr>
                        <th class="text-nowrap align-middle" scope="row"><?= __('code') ?></th>
                        <td class="text-break"><code>
                        <?php
                        if ($file['isImage']) :
                            echo '!';
                        endif;
                            echo '[' . e($file['description']) . '](' . e($file['url']) . ')';
                        ?></code>
                        </td>
                        <td class="text-nowrap align-middle"><a href="#" class="fileInsertBtn"><?= __('insert') ?></a></td>
                    </tr>
                    <tr>
                        <th class="align-middle" scope="row"><?= __('description') ?></th>
                        <td class="text-break"><?= e($file['description']); ?></td>
                        <td class="text-nowrap align-middle"><a href="#" data-description-id="target-description-id-<?= $fileId ?>" class="fileEditBtn"><?= __('edit') ?></a></td>
                    </tr>
                    </table>

                    <?php
                        echo '<form class="fileEdit d-none border p-3" id="target-description-id-' . $fileId . '">';
                        echo '<div class="updateStatus"></div>';
                        echo '<div class="mb-3">';
                        echo '<label for="eachDescription_' . $fileId . '" class="form-label">' . __('description') . '</label>';
                        echo '<textarea name="eachDescription_' . $fileId . '" id="eachDescription_' . $fileId . '" class="eachDescription form-control" aria-describedby="eachDescriptionHelp" data-file-id="' . $fileId . '" data-csrf_token="">' . e($file['description']) . '</textarea>';
                        echo '</div>';
                        echo '<div class="d-flex justify-content-end">';
                        echo '<button type="submit" class="btn btn-primary">' . __('update') . '</button>';
                        echo '</form>';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div> <!-- /table-responsive -->

<!-- Modal -->
<div class="modal fade" id="expandedImageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel"><?= __('enlarge_image') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('close') ?>"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" alt="<?= __('enlarge_image') ?>">
            </div>
        </div>
    </div>
</div>
