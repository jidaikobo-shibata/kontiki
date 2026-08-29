<?php

namespace Jidaikobo\Kontiki\Controllers\FileControllerTraits;

use Jidaikobo\Kontiki\Utils\MessageUtils;
use Psr\Http\Message\ResponseInterface as Response;

trait MessagesTrait
{
    protected function getMessages(): array
    {
        return [
            'invalid_request' => __(
                'invalid_request',
                'Invalid request. Please try again.'
            ),
            'validation_failed' => __(
                'validation_failed',
                'Data validation failed. Please check your input.'
            ),
            'upload_success' => __(
                'upload_success',
                'The file has been successfully uploaded. Input description and Insert.</a>.'
            ),
            'upload_error' => __(
                'upload_error',
                'The file could not be uploaded. Please try again.'
            ),
            'database_update_failed' => __(
                'database_update_failed',
                'Failed to update the database. Please try again.'
            ),
            'file_missing' => __(
                'file_missing',
                'No file uploaded or the file is corrupted.'
            ),
            'method_not_allowed' => __(
                'method_not_allowed',
                'Method not allowed.'
            ),
            'file_not_found' => __(
                'file_not_found',
                'File not found.'
            ),
            'update_success' => __(
                'update_success',
                'Your file has been successfully uploaded. You can now insert it into your post or add a description in the <a href="#" class="link-primary" id="switchToViewTab">Files List</a>.'
            ),
            'update_failed' => __(
                'update_failed',
                'Failed to update the database. Please try again.'
            ),
            'file_id_required' => __(
                'file_id_required',
                'File ID is required.'
            ),
            'file_delete_failed' => __(
                'file_delete_failed',
                'Failed to delete the file.'
            ),
            'db_update_failed' => __(
                'db_update_failed',
                'Failed to update the database.'
            ),
            'file_delete_success' => __(
                'file_delete_success',
                'File has been deleted successfully.'
            ),
            'unexpected_error' => __(
                'unexpected_error',
                'An unexpected error occurred. Please try again later.'
            ),
        ];
    }

    protected function messageResponse(
        Response $response,
        string $message,
        int $status
    ): Response {
        $data = ['message' => $message];
        return $this->jsonResponse($response, $data, $status);
    }

    protected function errorResponse(
        Response $response,
        string $message,
        int $status
    ): Response {
        return $this->messageResponse(
            $response,
            MessageUtils::alertHtml($message, 'warning'),
            $status
        );
    }

    protected function successResponse(
        Response $response,
        string $message
    ): Response {
        return $this->messageResponse(
            $response,
            MessageUtils::alertHtml($message),
            200
        );
    }

    protected function successResponseHtml(
        Response $response,
        string $message
    ): Response {
        return $this->messageResponse(
            $response,
            MessageUtils::alertHtml($message, 'success', false),
            200
        );
    }
}
