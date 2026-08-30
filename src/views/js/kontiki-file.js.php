<?php

/**
  * @var string $basepath
  */
?>document.addEventListener('DOMContentLoaded', () => {
    /**
     * add button when input||textarea has `kontiki-file-upload`
     */
    document.querySelectorAll('input.kontiki-file-upload, textarea.kontiki-file-upload').forEach((element) => {
        const buttonClass = element.dataset.buttonClass || '';
        const targetComponentId = element.id || '';

        const createModalButton = (label, modalId) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `btn btn-secondary ${buttonClass}`.trim();
            button.dataset.bsToggle = 'modal';
            button.dataset.bsTarget = `#${modalId}`;
            button.dataset.targetComponentId = targetComponentId;
            button.textContent = label;
            return button;
        };

        const manageButton = createModalButton(
            <?= json_encode(__('file_image_manage', 'File / Image Manage'), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            'kontikiFileIndexModal'
        );
        manageButton.dataset.tabTarget = 'view';
        element.insertAdjacentElement('afterend', manageButton);

        if (element instanceof HTMLTextAreaElement) {
            const uploadButton = createModalButton(
                <?= json_encode(__('image_insert', 'Insert Image'), JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                'kontikiFileUploadModal'
            );
            element.insertAdjacentElement('afterend', uploadButton);
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

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;

        const uploadTrigger = event.target.closest('[data-bs-target="#kontikiFileUploadModal"]');
        if (uploadTrigger) {
            kontikiUploader.targetFieldId = uploadTrigger.dataset.targetComponentId || 'content';
            return;
        }

        const indexTrigger = event.target.closest('[data-bs-target="#kontikiFileIndexModal"]');
        if (indexTrigger) {
            kontikiIndex.targetFieldId = indexTrigger.dataset.targetComponentId || 'content';
            kontikiIndex.fetchFiles();
        }
    });
});
