<?php
/**
  * @var string $get_file_list
  * @var string $couldnt_find_file
  * @var string $couldnt_get_file_list
  * @var string $copied
  * @var string $copy_failed
  * @var string $close
  * @var string $edit
  * @var string $confirm_delete_message
  * @var string $couldnt_delete_file
  * @var string $insert_success
  */
?>/**
 * File List Class
 */
class KontikiFileIndex {
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
        this.modalSelector = 'kontikiFileIndexModal';
        this.lightbox = opts.lightbox || new KontikiLightbox();
        this.lightbox.bindTriggers('#file-list');
    }

    /** Public entry to bind all handlers */
    mount() {
        this.setupPagination();
        this.setupCopyUrl();
        this.setupShowEdit();
        this.setupDeleteFile();
        this.setupFileEdit();
        this.setupInsertFile();
        this.bindModalA11y();
        this.csrf.refresh();
    }

    /**
     * Handles pagination link clicks and calls fetchFiles with the selected page.
     * @returns {void}
     */
    setupPagination() {
        // Listen for clicks on pagination links
        $(document).off('click', '.pagination .page-link-ajax'); // ensure reset click event
        $(document).on('click', '.pagination .page-link-ajax', (event) => {
            event.preventDefault(); // Prevent the default link behavior
            const page = $(event.currentTarget).data('page'); // Get the page number from data attribute
            this.fetchFiles(page); // Fetch files for the selected page
        });
    }

    /**
     * Fetch the list of uploaded files from the server.
     * @param {number} page - The page number to fetch.
     * @returns {void}
     */
    fetchFiles(page = 1) {
        // Find the file-list element where we'll append the files
        var fileListContainer = $('#file-list');

        fileListContainer.html('<p role="status"><?= $get_file_list ?></p>'); // Show loading message
        // clear upload status
        $('#fileUploadStatus').html('');

        // AJAX request to get the files
        $.ajax({
            url: `${this.ajaxUrl}filelist`,
            method: 'GET',
            data: { page: page },
            success: (response) => {
                // Successfully received the files list
                fileListContainer.empty(); // Clear the loading message

                // Check if there are any files
                if (response.length > 0) {
                    // Successfully received HTML content
                    fileListContainer.empty(); // Clear the loading message

                    // Directly insert the returned HTML into the file-list container
                    fileListContainer.html(response);

                    this.csrf.refresh();
                } else {
                    // If no files are found, display a message
                    fileListContainer.html('<p role="status"><?= $couldnt_find_file ?></p>');
                }
            },
            error: () => {
                // Handle error
                fileListContainer.html('<p role="status"><?= $couldnt_get_file_list ?></p>');
            }
        });
    }

    /**
     * Handles the click event for copying the URL to the clipboard.
     * @param {Event} e - The click event triggered by clicking the 'copy url' link.
     * @returns {void}
     */
    setupCopyUrl() {
        $(document).off('click', '.fileCopyUrl'); // ensure reset click event
        $(document).on('click', '.fileCopyUrl', (e) => {
            e.preventDefault(); // Prevent default anchor behavior

            // Find the preceding <td> within the same <tr>
            const copyButton = $(e.target);
            const textField = copyButton.closest('td').prev('td').find('.fileUrl');
            const textToCopy = textField.text().trim(); // Extract the text to copy

            // Remove existing messages before adding a new one
            textField.siblings('.copy-status').remove();

            // Use the Clipboard API to copy the text
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Append a success message
                textField.after('<span role="status" class="copy-status ms-2 text-success"><?= $copied ?></span>');
            }).catch((error) => {
                // Append an error message
                textField.after('<span role="status" class="copy-status ms-2 text-danger"><?= $copy_failed ?></span>');
            });
        });
    }

    /**
     * Toggles the visibility of an edit form within a table row.
     *
     * @returns {void}
     */
    setupShowEdit() {
        $(document).off('click', '.fileEditBtn'); // ensure reset click event
        $(document).on('click', '.fileEditBtn', function (e) {
            e.preventDefault(); // Prevent the default anchor behavior

            const editBtn = $(this);
            const formId = editBtn.attr('data-description-id');
            const form = $('#' + formId);

            if (form.hasClass('d-none')) {
                form.removeClass('d-none');
                editBtn.text('<?= $close ?>');
            } else {
                form.addClass('d-none');
                editBtn.text('<?= $edit ?>');
            }
        });
    }

    /**
     * Handles the click event on the delete link to remove a file.
     *
     * @param {Event} e - The event object representing the click event.
     */
    setupDeleteFile() {
        $(document).off('click', 'a.file-delete-link'); // ensure reset click event
        $(document).on('click', 'a.file-delete-link', (e) => {
            e.preventDefault(); // Prevent default anchor behavior

            const $link = $(e.currentTarget);
            const deleteId = $link.data('delete-id');
            const csrfToken = $link.attr('data-csrf_token'); // Use attr() to get the latest value
            if (!confirm("<?= $confirm_delete_message ?>")) {
                return;
            }

            // AJAX request to delete the file
            $.ajax({
                url: `${this.ajaxUrl}delete`,
                type: 'POST',
                data: {
                    id: deleteId,
                    _csrf_value: csrfToken
                },
                success: (response) => {
                    alert(response.message);
                    this.fetchFiles(); // Function to reload or refresh the table
                },
                error: (xhr, status, error) => {
                    var response = xhr.responseJSON; // Get the JSON response

                    // Check if the response contains a message
                    if (response && response.message) {
                        alert(response.message);
                    } else {
                        $('#uploadStatus').text('<?= $couldnt_delete_file ?>');
                    }
                    this.csrf.refresh();
                }
            });
        });
    }

    /**
     * Handles form submission and sends the data via AJAX.
     * Prevents the default form submission, retrieves form data,
     * and sends it to the server using AJAX.
     *
     * @event submit
     * @param {Event} e - The event object for the form submission.
     */
    setupFileEdit() {
        $(document).on('submit', '.fileEdit', (e) => {
            e.preventDefault(); // Prevent the default form submission

            // Save the reference to the form element
            const form = $(e.target);

            // Get the textarea content and CSRF token
            const description = form.find('.eachDescription').val(); // Get the text from the textarea
            const csrfToken = form.find('.eachDescription').attr('data-csrf_token'); // Get the CSRF token from data attribute
            const fileId = form.find('.eachDescription').attr('data-file-id'); // Get the file ID from data attribute

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
                    alert(response.message);
                    this.fetchFiles(); // Function to reload or refresh the table
                },
                error: (xhr, status, error) => {
                    // Handle upload error
                    var response = xhr.responseJSON; // Get the JSON response

                    // reset
                    form.find('.eachDescription').removeAttr('aria-invalid');
                    form.find('.eachDescription').removeAttr('aria-errormessage');
                    form.find('.eachDescription').removeClass('is-invalid');

                    // Check if the response contains a message
                    if (response && response.message) {
                        // Add aria-invalid and aria-errormessage to input#eachDescription_<id>
                        if (response.message.includes('errormessage_eachDescription_'+fileId)) {
                            form.find('.eachDescription').attr('aria-invalid', 'true');
                            form.find('.eachDescription').attr('aria-errormessage', 'errormessage_eachDescription_'+fileId);
                            form.find('.eachDescription').addClass('is-invalid');
                        }

                        form.find('.updateStatus').html(response.message); // Display the error message from response
                    } else {
                        form.find('.updateStatus').text('Update failed.'); // Default error message
                    }

                    this.csrf.refresh();
                }
            });
        });
    }

    /**
     * Handles the "Insert" button click to insert a file reference
     * into the targetField and display success status.
     */
    setupInsertFile() {
        $(document).off('click', '.fileInsertBtn'); // ensure reset click event
        $(document).on('click', '.fileInsertBtn', (e) => {
            e.preventDefault(); // Prevent default anchor behavior

            // Find the <code> element in the same row
            const fileRow = $(e.target).closest('tr'); // The row containing the button
            const codeContent = fileRow.find('td.text-break code').text().trim();
            const caret = this.utils.insertAtCaret(this.targetFieldId, codeContent);

            this._closingByInsert = true;

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
        });
    }

    // Keep ARIA clean by blurring focus before aria-hidden is set, and restore focus after.
    bindModalA11y() {
        const $modal = $('#' + this.modalSelector); // "kontikiFileIndexModal"
        let openerEl = null;

        // Remember who opened the modal (to restore focus later)
        $modal.on('show.bs.modal', () => {
            openerEl = document.activeElement;
        });

        // Before Bootstrap applies aria-hidden="true", ensure no focus remains inside the modal
        $modal.on('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && $modal[0].contains(active)) {
                active.blur();
            }
        });

        // After fully hidden: optionally restore focus to the opener, unless insert flow handled it
        $modal.on('hidden.bs.modal', () => {
            // If insert flow already focused the target textarea, skip restoring opener
            if (this._closingByInsert) {
                this._closingByInsert = false;
                return;
            }
            if (openerEl && document.contains(openerEl)) {
                openerEl.focus();
            }
        });

        // When shown: move focus to the first meaningful control in the modal
        $modal.on('shown.bs.modal', () => {
            const $first = $('#file-list')
                .find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
                .filter(':visible')
                .first();
            ($first[0] || $modal[0]).focus({ preventScroll: true });
        });
    }
}
