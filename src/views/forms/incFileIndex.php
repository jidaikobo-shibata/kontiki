<!-- lightbox -->
<div id="KontikiLightbox" class="kontiki-lightbox" hidden aria-hidden="true" role="dialog" tabindex="-1" aria-label="Image preview">
  <div class="kontiki-lightbox-content">
    <img id="KontikiLightboxImg" alt="">
    <div id="KontikiLightboxCaption" class="kontiki-lightbox-caption"></div>
    <button type="button" class="kontiki-lightbox-close" aria-label="Close preview">&times;</button>
  </div>
</div>

<!-- file manager -->
<div class="modal fade" id="kontikiFileIndexModal" tabindex="-1" aria-labelledby="kontikiFileIndexModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="kontikiFileIndexModalLabel"><?= __('file_image_manage', 'File / Image Manage') ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('close') ?>"></button>
      </div>
      <div class="modal-body">

        <div id="file-list">
          <p role="status"><?= __('prepare_file_list', 'Preparing file list.') ?></p>
        </div>

      </div><!-- /modal-body -->

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('close') ?></button>
      </div>

    </div><!-- /modal-content -->
  </div><!-- /modal-dialog -->
</div>
