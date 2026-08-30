/**
 * Sends an AJAX request to retrieve a new CSRF token from the server.
 */
class KontikiFileCsrf {
    constructor(ajaxUrl) {
        this.ajaxUrl = ajaxUrl;
    }

    async refresh() {
        try {
            const response = await fetch(`${this.ajaxUrl}get_csrf_token`, {
                method: 'GET',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error(`CSRF request failed with status ${response.status}`);
            }

            const payload = await response.json();
            if (typeof payload.csrf_token !== 'string' || payload.csrf_token.length === 0) {
                throw new Error('CSRF response did not contain a token');
            }

            document.querySelectorAll('.js-csrf-token').forEach((element) => {
                element.value = payload.csrf_token;
            });
            document.querySelectorAll('[data-csrf_token]').forEach((element) => {
                element.setAttribute('data-csrf_token', payload.csrf_token);
            });

            const uploadedDescription = document.getElementById('uploadedDescription');
            if (uploadedDescription) {
                uploadedDescription.setAttribute('data-csrf_token', payload.csrf_token);
            }
        } catch (error) {
            console.error('Failed to obtain CSRF token.', error);
            alert('Failed to obtain CSRF token.');
        }
    }
}
