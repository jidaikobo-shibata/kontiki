/**
 * File Utils
 */
class KontikiFileUtils {
    /**
     * Close the modal and (optionally) run a callback after it's fully hidden.
     * @param {string} modalSelectorStr - Modal id or "#id"
     * @param {Function} [onHidden] - Callback executed after hidden.bs.modal
     */
    closeModal(modalSelectorStr, onHidden) {
        // Normalize: allow both "myModal" and "#myModal"
        const modalId = modalSelectorStr.startsWith('#')
            ? modalSelectorStr.slice(1)
            : modalSelectorStr;

        const modalElement = document.getElementById(modalId);
        if (!modalElement) return;

        const modalInstance = bootstrap.Modal.getInstance(modalElement);
        if (!modalInstance) return;

        if (typeof onHidden === 'function') {
            // Run once after the modal is completely hidden
            modalElement.addEventListener('hidden.bs.modal', () => {
                onHidden();
            }, { once: true });
        }

        modalInstance.hide();
    }

    /**
     * Insert text into a textarea at caret/selection, preserving native undo.
     * Returns new caret position (number) or null if not found.
     */
    insertAtCaret(textareaId, text) {
        const el = document.getElementById(textareaId);
        if (!el) return null;

        // Ensure focus & a valid selection (needed for undo)
        if (document.activeElement !== el) el.focus({ preventScroll: true });
        let s = el.selectionStart, e = el.selectionEnd;
        if (typeof s !== 'number' || typeof e !== 'number') {
            s = e = el.value.length;
            el.setSelectionRange(s, e);
        }

        // 1) Prefer execCommand('insertText') → best undo integration on Chromium
        try {
            if (document.execCommand && document.queryCommandSupported?.('insertText')) {
                if (document.execCommand('insertText', false, text)) {
                    // notify listeners without rewriting .value
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    return el.selectionStart;
                }
            }
        } catch { /* fallback */ }

        // 2) Fallback to setRangeText() (undo-friendly in modern browsers)
        if (typeof el.setRangeText === 'function') {
            el.setRangeText(text, s, e, 'end');
            el.dispatchEvent(new Event('input', { bubbles: true }));
            return s + text.length;
        }

        // 3) Last resort (not undo-friendly)
        const before = el.value.slice(0, s), after = el.value.slice(e);
        el.value = before + text + after;
        const pos = s + text.length;
        el.setSelectionRange(pos, pos);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        return pos;
    }
}
