<?php
/**
 * @var string $uploading
 * @var string $couldnt_upload
 */
?>/**
 * File uploader Class
 */
class KontikiFileUploader {
    /**
     * @param {Object} opts
     * @param {string} opts.ajaxUrl - Base URL like "/admin/"
     * @param {KontikiFileCsrf} opts.csrf - CSRF helper instance (already created in main)
     * @param {string} opts.targetFieldId - ID of textarea to insert into
     */
    constructor(opts) {
        this.ajaxUrl = opts.ajaxUrl || '/admin/';
        this.csrf = opts.csrf;
        this.targetFieldId = opts.targetFieldId || 'content';
        this.utils = opts.utils || new KontikiFileUtils(); // default instance
        this.modalSelector = 'kontikiFileUploadModal';
    }

    /** Public entry to bind all handlers */
    mount() {
        this.setupFileUploadButton();
        this.setupFileUpload();
        this.setupUpdateDescAndInsert();
        this.bindModalReset();
        this.csrf.refresh();
    }

    /**
     * Handles file upload button.
     * - Toggle disabled/style based on whether a file is chosen.
     * - Bind only once with namespaced event to avoid duplicate handlers.
     */
    setupFileUploadButton() {
        const $input = $('#fileAttachment');
        const $button = $('#fileUploadButton');

        // Bind once (remove previous same-namespaced handler)
        $(document)
            .off('change.kfmUploader', '#fileAttachment')
            .on('change.kfmUploader', '#fileAttachment', function() {
                const hasFile = this.files && this.files.length > 0;
                $button.prop('disabled', !hasFile)
                    .toggleClass('btn-light', !hasFile)
                    .toggleClass('btn-info', hasFile);
            });

        // Initialize state (important if input already has a value or after back/forward cache)
        const hasFile = $input[0]?.files?.length > 0;
        $button.prop('disabled', !hasFile)
            .toggleClass('btn-light', !hasFile)
            .toggleClass('btn-info', hasFile);
    }

    /**
     * Handles the file upload process.
     * @param {Event} event - The event object from the submit event.
     */
    setupFileUpload() {
        $('#uploadForm').on('submit', (event) => {
            event.preventDefault();

            const $status = $('#fileUploadStatus');
            const $fileBtn = $('#fileUploadButton');

            // Reset status
            $status.removeClass('alert alert-success alert-danger').empty();
            $status.text('<?= $uploading ?>').attr('role', 'status');

            // Disable button during upload
            $fileBtn.prop('disabled', true);

            const formData = new FormData(event.target);

            $.ajax({
                url: `${this.ajaxUrl}upload`,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: (response) => {
                    $status.removeClass('alert-danger')
                        .addClass('alert alert-success')
                        .html(response.message);

                    // Clear file input
                    $('#fileAttachment').val('').focus();

                    // Save returned meta to description field
                    $('#uploadedDescription')
                        .attr('data-file-id', response.data.id)
                        .attr('data-file-path', response.data.path)
                        .val('');

                    // Transition to "insert" view with soft reveal
                    const $insert = $('#insertUploadedFile');
                    const $upload = $('#uploadForm');

                    // Hide the upload form first
                    $upload.addClass('d-none'); // remove fadeOut to avoid double animations

                    // Prepare target for reveal: ensure it's displayed, then animate
                    $insert.removeClass('d-none').addClass('kf-reveal');

                    // Let the browser paint the initial state, then trigger the end state
                    requestAnimationFrame(() => {
                        $insert.addClass('is-in');

                        // After the transition ends, move focus to the textarea
                        const onDone = () => {
                            $insert.off('transitionend', onDone);
                            const $desc = $('#uploadedDescription');
                            $desc.trigger('focus');
                        };
                        $insert.on('transitionend', onDone);
                    });

                    this.csrf.refresh();
                },
                error: (xhr) => {
                    const response = xhr.responseJSON;
                    $status.removeClass('alert-success').addClass('alert alert-danger');
                    if (response && response.message) {
                        $status.html(response.message);
                    } else {
                        $status.text('<?= $couldnt_upload ?>');
                    }
                    this.csrf.refresh();
                },
                complete: () => {
                    // Re-enable button after request finishes
                    $fileBtn.prop('disabled', $('#fileAttachment').val().length === 0);
                }
            });
        });
    }

    /**
     * Save file description via ajax.
     * Insert markdown format/file path into input field.
     *
     * @event submit
     * @param {Event} e - The event object for the form submission.
     */
    setupUpdateDescAndInsert() {
        $(document).on('submit', '#insertUploadedFile', (e) => {
            e.preventDefault(); // Prevent the default form submission

            const form = $(e.target);

            const description = $('#uploadedDescription').val();
            const csrfToken = $('#uploadedDescription').attr('data-csrf_token');
            const fileId = $('#uploadedDescription').attr('data-file-id');
            const fileUrl = $('#uploadedDescription').attr('data-file-path');

            // Prepare the data to be sent
            const formData = {
                description: description,
                _csrf_value: csrfToken,
                id: fileId
            };

            // Make the AJAX request
            $.ajax({
                url: `${this.ajaxUrl}update`, // The URL to handle the request
                type: 'POST',
                data: formData,
                success: (response) => {
                    const markdown = `![${description}](${fileUrl})`;
                    const caret = this.utils.insertAtCaret(this.targetFieldId, markdown);

                    this.utils.closeModal(this.modalSelector, () => {
                        const target = document.getElementById(this.targetFieldId);
                        if (target) {
                            // Focus after modal is gone so Bootstrap won't steal it
                            target.focus();
                            if (typeof caret === 'number') {
                                target.setSelectionRange(caret, caret);
                            }
                        }
                    });
                },
                error: (xhr, status, error) => {
                    // Handle upload error
                    var response = xhr.responseJSON; // Get the JSON response

                    // reset
                    $('#uploadedDescription').removeAttr('aria-invalid');
                    $('#uploadedDescription').removeAttr('aria-errormessage');
                    $('#uploadedDescription').removeClass('is-invalid');

                    // Check if the response contains a message
                    if (response && response.message) {
                        // Add aria-invalid and aria-errormessage to input#eachDescription_<id>
                        if (response.message.includes('errormessage_eachDescription_' + fileId)) {
                            $('#uploadedDescription').attr('aria-invalid', 'true');
                            $('#uploadedDescription').attr('aria-errormessage', 'insertStatusMsg');
                            $('#uploadedDescription').addClass('is-invalid');
                        }
                        const replacedMessage = response
                            .message
                            .replace('#eachDescription_' + fileId, '#uploadedDescription');
                        $('#insertStatusMsg').html(replacedMessage);
                    } else {
                        $('#insertStatusMsg').text('Update failed.');
                    }
                    this.csrf.refresh();
                }
            });
        });
    }

    /** Reset modal to the initial "upload" view whenever it closes/opens */
    bindModalReset() {
        const modalId = this.modalSelector; // "kontikiFileUploadModal"
        const $modal  = $('#' + modalId);

        // Helper to reset UI state
        const resetUI = () => {
            const $upload = $('#uploadForm');
            const $insert = $('#insertUploadedFile');
            const $status = $('#fileUploadStatus');
            const $file   = $('#fileAttachment');
            const $btn    = $('#fileUploadButton');
            const $desc   = $('#uploadedDescription');

            // Hide insert view and clear its state
            $insert.addClass('d-none').removeClass('kf-reveal is-in');
            $('#insertStatusMsg').empty();
            $desc.val('')
                .removeAttr('data-file-id data-file-path data-csrf_token aria-invalid aria-errormessage')
                .removeClass('is-invalid');

            // Show upload form and reset controls
            $upload.removeClass('d-none');
            $status.removeClass('alert alert-success alert-danger').empty().removeAttr('role');
            $file.val('');                 // clear selected file
            $btn.prop('disabled', true)    // disable until a file is chosen
                .addClass('btn-light')
                .removeClass('btn-info');
        };

        // before aria-hidden=true is set, blur anything inside
        $modal.on('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && $modal[0].contains(active)) {
                active.blur(); // remove focus from modal descendants
            }
        });

        // When the modal is completely hidden (closed by ×, ESC, backdrop, or JS)
        $modal.on('hidden.bs.modal', () => {
            resetUI(); // prepare for the next open
        });

        // When the modal is shown, ensure initial state and focus the file input
        $modal.on('shown.bs.modal', () => {
            // In case it was opened without having been hidden before
            if ($('#insertUploadedFile').is(':visible')) resetUI();
            // Focus the file input for quicker flow
            $('#fileAttachment').trigger('focus');
        });
    }
}
