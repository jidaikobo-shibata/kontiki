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
        this.pendingForms = new WeakSet();
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
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const link = event.target.closest('.pagination .page-link-ajax');
            if (!link) return;

            event.preventDefault(); // Prevent the default link behavior
            const page = Number(link.dataset.page) || 1;
            this.fetchFiles(page); // Fetch files for the selected page
        });
    }

    /**
     * Fetch the list of uploaded files from the server.
     * @param {number} page - The page number to fetch.
     * @returns {void}
     */
    async fetchFiles(page = 1) {
        // Find the file-list element where we'll append the files
        const fileListContainer = document.getElementById('file-list');
        if (!fileListContainer) return;

        const loadingMessage = document.createElement('p');
        loadingMessage.setAttribute('role', 'status');
        loadingMessage.textContent = '<?= $get_file_list ?>';
        fileListContainer.replaceChildren(loadingMessage);
        // clear upload status
        document.getElementById('fileUploadStatus')?.replaceChildren();

        try {
            const url = new URL(`${this.ajaxUrl}filelist`, window.location.href);
            url.searchParams.set('page', String(page));
            const response = await fetch(url, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error(`File list request failed with status ${response.status}`);
            }
            const html = await response.text();
            if (html.length > 0) {
                fileListContainer.innerHTML = html;
                this.csrf.refresh();
                return;
            }

            const emptyMessage = document.createElement('p');
            emptyMessage.setAttribute('role', 'status');
            emptyMessage.textContent = '<?= $couldnt_find_file ?>';
            fileListContainer.replaceChildren(emptyMessage);
        } catch (error) {
            console.error('Failed to obtain file list.', error);
            const errorMessage = document.createElement('p');
            errorMessage.setAttribute('role', 'status');
            errorMessage.textContent = '<?= $couldnt_get_file_list ?>';
            fileListContainer.replaceChildren(errorMessage);
        }
    }

    /**
     * Handles the click event for copying the URL to the clipboard.
     * @param {Event} e - The click event triggered by clicking the 'copy url' link.
     * @returns {void}
     */
    setupCopyUrl() {
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const copyButton = event.target.closest('.fileCopyUrl');
            if (!copyButton) return;
            event.preventDefault();

            // Find the preceding <td> within the same <tr>
            const buttonCell = copyButton.closest('td');
            const textField = buttonCell?.previousElementSibling?.querySelector('.fileUrl');
            if (!textField) return;
            const textToCopy = textField.textContent.trim();

            // Remove existing messages before adding a new one
            textField.parentElement?.querySelectorAll('.copy-status').forEach((status) => status.remove());

            // Use the Clipboard API to copy the text
            navigator.clipboard.writeText(textToCopy).then(() => {
                const status = document.createElement('span');
                status.setAttribute('role', 'status');
                status.className = 'copy-status ms-2 text-success';
                status.textContent = '<?= $copied ?>';
                textField.after(status);
            }).catch((error) => {
                console.error('Failed to copy file URL.', error);
                const status = document.createElement('span');
                status.setAttribute('role', 'status');
                status.className = 'copy-status ms-2 text-danger';
                status.textContent = '<?= $copy_failed ?>';
                textField.after(status);
            });
        });
    }

    /**
     * Toggles the visibility of an edit form within a table row.
     *
     * @returns {void}
     */
    setupShowEdit() {
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const editButton = event.target.closest('.fileEditBtn');
            if (!editButton) return;
            event.preventDefault();

            const formId = editButton.getAttribute('data-description-id');
            const form = formId ? document.getElementById(formId) : null;
            if (!form) return;

            if (form.classList.contains('d-none')) {
                form.classList.remove('d-none');
                editButton.textContent = '<?= $close ?>';
            } else {
                form.classList.add('d-none');
                editButton.textContent = '<?= $edit ?>';
            }
        });
    }

    /**
     * Handles the click event on the delete link to remove a file.
     *
     * @param {Event} e - The event object representing the click event.
     */
    setupDeleteFile() {
        document.addEventListener('click', async (event) => {
            if (!(event.target instanceof Element)) return;
            const link = event.target.closest('a.file-delete-link');
            if (!link) return;
            event.preventDefault();

            const deleteId = link.dataset.deleteId || '';
            const csrfToken = link.getAttribute('data-csrf_token') || '';
            if (!confirm("<?= $confirm_delete_message ?>")) {
                return;
            }

            link.setAttribute('aria-disabled', 'true');
            try {
                const response = await this.requestJson(`${this.ajaxUrl}delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: new URLSearchParams({
                    id: deleteId,
                    _csrf_value: csrfToken
                    })
                });
                alert(response.message);
                this.fetchFiles();
            } catch (error) {
                const response = error.payload;
                if (response && response.message) {
                    alert(response.message);
                } else {
                    const uploadStatus = document.getElementById('uploadStatus');
                    if (uploadStatus) uploadStatus.textContent = '<?= $couldnt_delete_file ?>';
                }
                this.csrf.refresh();
            } finally {
                link.removeAttribute('aria-disabled');
            }
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
        document.addEventListener('submit', async (event) => {
            if (!(event.target instanceof HTMLFormElement) || !event.target.matches('.fileEdit')) return;
            event.preventDefault();
            const form = event.target;
            if (this.pendingForms.has(form)) return;

            // Get the textarea content and CSRF token
            const descriptionInput = form.querySelector('.eachDescription');
            if (!descriptionInput) return;
            const description = descriptionInput.value;
            const csrfToken = descriptionInput.getAttribute('data-csrf_token') || '';
            const fileId = descriptionInput.getAttribute('data-file-id') || '';

            // Prepare the data to be sent
            const formData = new URLSearchParams({
                description,
                _csrf_value: csrfToken,
                id: fileId
            });

            this.pendingForms.add(form);
            try {
                const response = await this.requestJson(`${this.ajaxUrl}update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: formData
                });
                alert(response.message);
                this.fetchFiles();
            } catch (error) {
                    const response = error.payload;

                    // reset
                    descriptionInput.removeAttribute('aria-invalid');
                    descriptionInput.removeAttribute('aria-errormessage');
                    descriptionInput.classList.remove('is-invalid');

                    // Check if the response contains a message
                    if (response && response.message) {
                        // Add aria-invalid and aria-errormessage to input#eachDescription_<id>
                        if (response.message.includes('errormessage_eachDescription_'+fileId)) {
                            descriptionInput.setAttribute('aria-invalid', 'true');
                            descriptionInput.setAttribute('aria-errormessage', 'errormessage_eachDescription_'+fileId);
                            descriptionInput.classList.add('is-invalid');
                        }

                        const updateStatus = form.querySelector('.updateStatus');
                        if (updateStatus) updateStatus.innerHTML = response.message;
                    } else {
                        const updateStatus = form.querySelector('.updateStatus');
                        if (updateStatus) updateStatus.textContent = 'Update failed.';
                    }

                    this.csrf.refresh();
            } finally {
                this.pendingForms.delete(form);
            }
        });
    }

    /**
     * Handles the "Insert" button click to insert a file reference
     * into the targetField and display success status.
     */
    setupInsertFile() {
        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const insertButton = event.target.closest('.fileInsertBtn');
            if (!insertButton) return;
            event.preventDefault();

            // Find the <code> element in the same row
            const fileRow = insertButton.closest('tr');
            const codeElement = fileRow?.querySelector('td.text-break code');
            if (!codeElement) return;
            const codeContent = codeElement.textContent.trim();
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
        const modal = document.getElementById(this.modalSelector);
        if (!modal) return;
        let openerEl = null;

        // Remember who opened the modal (to restore focus later)
        modal.addEventListener('show.bs.modal', () => {
            openerEl = document.activeElement;
        });

        // Before Bootstrap applies aria-hidden="true", ensure no focus remains inside the modal
        modal.addEventListener('hide.bs.modal', () => {
            const active = document.activeElement;
            if (active && modal.contains(active)) {
                active.blur();
            }
        });

        // After fully hidden: optionally restore focus to the opener, unless insert flow handled it
        modal.addEventListener('hidden.bs.modal', () => {
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
        modal.addEventListener('shown.bs.modal', () => {
            const controls = document.querySelectorAll(
                '#file-list button, #file-list [href], #file-list input, #file-list select, '
                + '#file-list textarea, #file-list [tabindex]:not([tabindex="-1"])'
            );
            const firstVisible = Array.from(controls).find((element) => element.getClientRects().length > 0);
            (firstVisible || modal).focus({ preventScroll: true });
        });
    }
}
