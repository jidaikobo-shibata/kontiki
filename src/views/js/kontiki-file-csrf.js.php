/**
 * Sends an AJAX request to retrieve a new CSRF token from the server.
 */
class KontikiFileCsrf {
    constructor(ajaxUrl) {
        this.ajaxUrl = ajaxUrl;
    }

    refresh() {
        $.ajax({
            url: `${this.ajaxUrl}get_csrf_token`,
            type: 'GET',
            success: (response) => {
                $('.js-csrf-token').val(response.csrf_token);
                $('[data-csrf_token]').attr('data-csrf_token', response.csrf_token);
                $('#uploadedDescription').attr('data-csrf_token', response.csrf_token);
            },
            error: () => {
                alert('Failed to obtain CSRF token.');
            }
        });
    }
}
