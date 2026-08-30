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
        this.uploadInProgress = false;
        this.updateInProgress = false;
    }

    async requestJson(url, options) {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', ...(options.headers || {}) },
            credentials: 'same-origin'
        });

        let payload = null;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error(`Invalid JSON response from ${url}`, { cause: error });
        }

        if (!response.ok) {
            const error = new Error(`Request failed with status ${response.status}`);
            error.payload = payload;
            throw error;
        }

        return payload;
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
        const input = document.getElementById('fileAttachment');
        const button = document.getElementById('fileUploadButton');
        if (!input || !button) return;

        const syncButtonState = () => {
            const hasFile = input.files && input.files.length > 0;
            button.disabled = !hasFile;
            button.classList.toggle('btn-light', !hasFile);
            button.classList.toggle('btn-info', hasFile);
        };

        input.addEventListener('change', syncButtonState);

        // Initialize state (important if input already has a value or after back/forward cache)
        syncButtonState();
    }

    /**
     * Handles the file upload process.
     * @param {Event} event - The event object from the submit event.
     */
    setupFileUpload() {
        const uploadForm = document.getElementById('uploadForm');
        if (!uploadForm) return;

        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (this.uploadInProgress) return;

            const status = document.getElementById('fileUploadStatus');
            const fileButton = document.getElementById('fileUploadButton');
            if (!status || !fileButton) return;

            // Reset status
            status.classList.remove('alert', 'alert-success', 'alert-danger');
            status.replaceChildren();
            status.textContent = '<?= $uploading ?>';
            status.setAttribute('role', 'status');

            // Disable button during upload
            this.uploadInProgress = true;
            fileButton.disabled = true;

            const formData = new FormData(event.target);

            try {
                const response = await this.requestJson(`${this.ajaxUrl}upload`, {
                    method: 'POST',
                    body: formData
                });
                    status.classList.remove('alert-danger');
                    status.classList.add('alert', 'alert-success');
                    status.innerHTML = response.message;

                    // Clear file input
                    const fileInput = document.getElementById('fileAttachment');
                    if (fileInput) {
                        fileInput.value = '';
                        fileInput.focus();
                    }

                    // Save returned meta to description field
                    const description = document.getElementById('uploadedDescription');
                    if (description) {
                        description.setAttribute('data-file-id', response.data.id);
                        description.setAttribute('data-file-path', response.data.path);
                        description.value = '';
                    }

                    // Transition to "insert" view with soft reveal
                    const insertForm = document.getElementById('insertUploadedFile');
                    if (!insertForm) return;

                    // Hide the upload form first
                    uploadForm.classList.add('d-none');

                    // Prepare target for reveal: ensure it's displayed, then animate
                    insertForm.classList.remove('d-none');
                    insertForm.classList.add('kf-reveal');

                    // Let the browser paint the initial state, then trigger the end state
                    requestAnimationFrame(() => {
                        insertForm.classList.add('is-in');

                        // After the transition ends, move focus to the textarea
                        const onDone = () => {
                            description?.focus();
                        };
                        insertForm.addEventListener('transitionend', onDone, { once: true });
                    });

                    this.csrf.refresh();
            } catch (error) {
                    const response = error.payload;
                    status.classList.remove('alert', 'alert-success', 'alert-danger');
                    if (response && response.message) {
                        status.innerHTML = response.message;
                    } else {
                        status.classList.add('alert', 'alert-danger');
                        status.textContent = '<?= $couldnt_upload ?>';
                    }
                    this.csrf.refresh();
            } finally {
                this.uploadInProgress = false;
                const fileInput = document.getElementById('fileAttachment');
                fileButton.disabled = !fileInput || fileInput.value.length === 0;
            }
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
        const insertForm = document.getElementById('insertUploadedFile');
        const descriptionInput = document.getElementById('uploadedDescription');
        if (!insertForm || !descriptionInput) return;

        insertForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (this.updateInProgress) return;

            const description = descriptionInput.value;
            const csrfToken = descriptionInput.getAttribute('data-csrf_token');
            const fileId = descriptionInput.getAttribute('data-file-id');
            const fileUrl = descriptionInput.getAttribute('data-file-path');

            // Prepare the data to be sent
            const formData = new URLSearchParams({
                description,
                _csrf_value: csrfToken || '',
                id: fileId || ''
            });

            const submitButton = insertForm.querySelector('button[type="submit"], input[type="submit"]');
            this.updateInProgress = true;
            if (submitButton) submitButton.disabled = true;

            try {
                await this.requestJson(`${this.ajaxUrl}update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: formData
                });
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
            } catch (error) {
                    // Handle upload error
                    const response = error.payload;

                    // reset
                    descriptionInput.removeAttribute('aria-invalid');
                    descriptionInput.removeAttribute('aria-errormessage');
                    descriptionInput.classList.remove('is-invalid');

                    // Check if the response contains a message
                    if (response && response.message) {
                        // Add aria-invalid and aria-errormessage to input#eachDescription_<id>
                        if (response.message.includes('errormessage_eachDescription_' + fileId)) {
                            descriptionInput.setAttribute('aria-invalid', 'true');
                            descriptionInput.setAttribute('aria-errormessage', 'insertStatusMsg');
                            descriptionInput.classList.add('is-invalid');
                        }
                        const replacedMessage = response
                            .message
                            .replace('#eachDescription_' + fileId, '#uploadedDescription');
                        const insertStatus = document.getElementById('insertStatusMsg');
                        if (insertStatus) insertStatus.innerHTML = replacedMessage;
                    } else {
                        const insertStatus = document.getElementById('insertStatusMsg');
                        if (insertStatus) insertStatus.textContent = 'Update failed.';
                    }
                    this.csrf.refresh();
            } finally {
                this.updateInProgress = false;
                if (submitButton) submitButton.disabled = false;
            }
        });
    }

    /** Reset modal to the initial "upload" view whenever it closes/opens */
    bindModalReset() {
        const modalId = this.modalSelector; // "kontikiFileUploadModal"
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Helper to reset UI state
        const resetUI = () => {
            const upload = document.getElementById('uploadForm');
            const insert = document.getElementById('insertUploadedFile');
            const status = document.getElementById('fileUploadStatus');
            const file = document.getElementById('fileAttachment');
            const button = document.getElementById('fileUploadButton');
            const description = document.getElementById('uploadedDescription');
            const insertStatus = document.getElementById('insertStatusMsg');
            if (!upload || !insert || !status || !file || !button || !description) return;

            // Hide insert view and clear its state
            insert.classList.add('d-none');
            insert.classList.remove('kf-reveal', 'is-in');
            insertStatus?.replaceChildren();
            description.value = '';
            [
                'data-file-id',
                'data-file-path',
                'data-csrf_token',
                'aria-invalid',
                'aria-errormessage'
            ].forEach((attribute) => description.removeAttribute(attribute));
            description.classList.remove('is-invalid');

            // Show upload form and reset controls
            upload.classList.remove('d-none');
            status.classList.remove('alert', 'alert-success', 'alert-danger');
            status.replaceChildren();
            status.removeAttribute('role');
            file.value = '';
            button.disabled = true;
            button.classList.add('btn-light');
            button.classList.remove('btn-info');
        };

        // before aria-hidden=true is set, blur anything inside
        modal.addEventListener('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && modal.contains(active)) {
                active.blur(); // remove focus from modal descendants
            }
        });

        // When the modal is completely hidden (closed by ×, ESC, backdrop, or JS)
        modal.addEventListener('hidden.bs.modal', () => {
            resetUI(); // prepare for the next open
        });

        // When the modal is shown, ensure initial state and focus the file input
        modal.addEventListener('shown.bs.modal', () => {
            // In case it was opened without having been hidden before
            const insertForm = document.getElementById('insertUploadedFile');
            if (insertForm && !insertForm.classList.contains('d-none')) resetUI();
            // Focus the file input for quicker flow
            document.getElementById('fileAttachment')?.focus();
        });
    }
}
