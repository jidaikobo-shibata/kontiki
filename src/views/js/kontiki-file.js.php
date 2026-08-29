<?php

/**
  * @var string $basepath
  */
?>$(document).ready(function() {
    /**
     * add button when input||textarea has `kontiki-file-upload`
     */
    $("input.kontiki-file-upload, textarea.kontiki-file-upload").each(function () {
        const $element = $(this);
        const buttonClass = $(this).data("button-class") || "";
        const targetComponentId = $(this).attr("id") || "";
        const buttonHtml = `
            <button type="button" class="btn btn-secondary ${buttonClass}" data-bs-toggle="modal" data-bs-target="#kontikiFileIndexModal" data-target-component-id="${targetComponentId}" data-tab-target="view"><?= __('file_image_manage', 'File / Image Manage') ?></button>
        `;
        $element.after(buttonHtml);

        if ($element.is("textarea")) {
            const extraButtonHtml = `
            <button type="button" class="btn btn-secondary ${buttonClass}" data-bs-toggle="modal" data-bs-target="#kontikiFileUploadModal" data-target-component-id="${targetComponentId}"><?= __('image_insert', 'Insert Image') ?></button>
        `;
            $element.after(extraButtonHtml);
        }
    });

    /**
     * Listening to events using the Bootstrap modal JavaScript API
     */
    const kontikiUtils = new KontikiFileUtils();
    const kontikiCsrf = new KontikiFileCsrf('<?= $basepath ?>/');
    const KontikiLightbox = new KontikiFileLightbox({ rootSelector: '#kontiki-main' });

    const kontikiUploader = new KontikiFileUploader({
      ajaxUrl: '<?= $basepath ?>/',
      csrf: kontikiCsrf,
      utils: kontikiUtils,
      targetFieldId: 'content' // default
    });
    kontikiUploader.mount();

    const kontikiIndex = new KontikiFileIndex({
      ajaxUrl: '<?= $basepath ?>/',
      csrf: kontikiCsrf,
      utils: kontikiUtils,
      lightbox: KontikiLightbox,
      targetFieldId: 'content' // default
    });
    kontikiIndex.mount();

    $(document).on('click', '[data-bs-target="#kontikiFileUploadModal"]', function() {
        const targetId = $(this).data('target-component-id') || 'content';
        kontikiUploader.targetFieldId = targetId;
    });

    $(document).on('click', '[data-bs-target="#kontikiFileIndexModal"]', function() {
        const targetId = $(this).data('target-component-id') || 'content';
        kontikiIndex.targetFieldId = targetId;
        kontikiIndex.fetchFiles();
    });
});
