<div class="modal fade" id="kontikiFileUploadModal" tabindex="-1" aria-labelledby="kontikiFileUploadModalLabel" aria-hidden="true" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="kontikiFileUploadModalLabel"><?= __('image_and_file_insert') ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('close') ?>"></button>
      </div>
      <div class="modal-body">

        <form class="pt-4 px-4 w-100" id="uploadForm" enctype="multipart/form-data">
          <div class="mt-4 mb-3 row">
            <label for="fileAttachment" class="visually-hidden"><?= __('file') ?></label>
            <div class="col-sm-8 mx-auto">
              <input type="file" name="attachment" id="fileAttachment" required class="form-control fs-5" aria-describedby="attachmentHelp">
              <small id="attachmentHelp" class="form-text text-muted text-center"><?= __('multi_bytes_warn', 'Multi-Bytes characters will be changed or deleted.') ?></small>
            </div>
          </div>
          <input type="hidden" name="_csrf_value" class="js-csrf-token" value="">
          <div class="text-center">
            <button type="submit" id="fileUploadButton" disabled class="btn btn-light"><?= __('upload') ?></button>
          </div>
        </form>

        <div id="fileUploadStatus" class="mt-3"></div>

        <!-- save data by ajax and insert to field -->
        <form class="pt-4 px-4 w-100 d-none" id="insertUploadedFile" class="fileEdit">
          <input type="hidden" name="_csrf_value" class="js-csrf-token" value="">
          <label class="form-label" for="uploadedDescription">画像説明</label>
          <textarea name="uploadedDescription" id="uploadedDescription" class="form-control" aria-describedby="uploadedDescriptionHelp" data-file-id="" data-file-path="" data-csrf_token=""></textarea>
          <small id="uploadedDescriptionHelp" class="form-text text-muted">画像説明を入力の上、挿入ボタンを押してください。画像に特に意味がない、装飾目的の画像の場合は、空のまま挿入してください。</small>
          <div id="insertStatusMsg"></div>
          <div class="text-center">
            <button type="submit" class="btn btn-primary">挿入</button>
          </div>
        </form>

      </div><!-- /modal-body -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
      </div>
    </div><!-- /modal-content -->
  </div><!-- /modal-dialog -->
</div>
