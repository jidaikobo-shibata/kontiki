<?php

namespace Jidaikobo\Kontiki\Controllers\FileControllerTraits;

use Jidaikobo\Kontiki\Utils\MessageUtils;
use Jidaikobo\Kontiki\Services\FileLifecycleResult;
use Jidaikobo\Kontiki\Services\FileService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait CRUDTrait
{
    public function getCsrfToken(Request $request, Response $response): Response
    {
        $data = ['csrf_token' => $this->csrfManager->getToken()];
        return $this->jsonResponse($response, $data);
    }

    /**
     * Handles file upload via an AJAX request.
     * This method processes the uploaded file, moves it to the specified directory,
     * and returns a JSON response indicating the result of the operation.
     *
     * @return Response
     */
    public function handleFileUpload(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody() ?? [];

        // CSRF Token validation
        $errorResponse = $this->validateCsrfForJson($parsedBody, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        $uploadError = $this->uploadedFileAdapter->errorFromRequest($request);
        if ($uploadError !== null && $uploadError !== UPLOAD_ERR_OK) {
            return $this->uploadErrorResponse($response, $uploadError);
        }

        // prepare file
        $uploadedFile = $this->uploadedFileAdapter->fromRequest($request);
        if (!$uploadedFile) {
            return $this->errorResponse($response, $this->getMessages()['file_missing'], 400);
        }

        $result = $this->fileLifecycleService->upload(
            $this->model,
            $uploadedFile
        );
        if ($result->failure === FileLifecycleResult::FAILURE_VALIDATION) {
            return $this->messageResponse(
                $response,
                MessageUtils::errorHtml($result->errors, $this->model),
                405
            );
        }
        if ($result->failure === FileLifecycleResult::FAILURE_DATABASE) {
            return $this->errorResponse(
                $response,
                $this->getMessages()['database_update_failed'],
                500
            );
        }
        if ($result->failure === FileLifecycleResult::FAILURE_UPLOAD) {
            return $this->fileValidationErrorResponse($response, $result->errors);
        }
        if (!$result->success) {
            return $this->errorResponse($response, $this->getMessages()['upload_error'], 500);
        }

        // success
        return $this->jsonResponse($response, [
                'message' => $this->getMessages()['upload_success'],
                'data' => $result->data
            ]);
    }

    private function uploadErrorResponse(Response $response, int $error): Response
    {
        $messages = $this->getMessages();

        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $this->errorResponse(
                $response,
                $messages['file_too_large_server'],
                422
            ),
            UPLOAD_ERR_PARTIAL => $this->errorResponse(
                $response,
                $messages['file_upload_partial'],
                422
            ),
            UPLOAD_ERR_NO_FILE => $this->errorResponse(
                $response,
                $messages['file_missing'],
                400
            ),
            default => $this->errorResponse(
                $response,
                $messages['upload_error'],
                500
            ),
        };
    }

    /** @param array<mixed> $errors */
    private function fileValidationErrorResponse(Response $response, array $errors): Response
    {
        $messages = $this->getMessages();
        $details = [];

        if (in_array(FileService::ERROR_INVALID_TYPE, $errors, true)) {
            $details[] = __(
                'file_type_not_allowed',
                'This file type cannot be uploaded. Allowed types: :types.',
                ['types' => $this->allowedTypeLabels()]
            );
        }
        if (in_array(FileService::ERROR_TOO_LARGE, $errors, true)) {
            $details[] = __(
                'file_too_large',
                'The file exceeds the maximum size of :size.',
                ['size' => $this->formatBytes($this->fileService->getMaxSize())]
            );
        }

        if ($details === []) {
            return $this->errorResponse($response, $messages['upload_error'], 500);
        }

        return $this->errorResponse($response, implode(' ', $details), 422);
    }

    private function allowedTypeLabels(): string
    {
        $labels = [
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'application/pdf' => 'PDF',
        ];
        $allowed = array_map(
            static fn(string $type): string => $labels[$type] ?? $type,
            $this->fileService->getAllowedTypes()
        );

        return implode('、', $allowed);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1000000) {
            return rtrim(rtrim(number_format($bytes / 1000000, 2, '.', ''), '0'), '.') . ' MB';
        }

        return number_format($bytes / 1000, 0) . ' KB';
    }

    /**
     * Handles the AJAX request to update a file's data in the database.
     * Validates the CSRF token, retrieves the file details by ID,
     * updates the file information, and returns a JSON response indicating success or failure.
     *
     * @return Response
     */
    public function handleUpdate(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody() ?? [];

        // CSRF Token validation
        $errorResponse = $this->validateCsrfForJson($parsedBody, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        $fileId = $parsedBody['id'] ?? 0;
        $result = $this->fileLifecycleService->updateDescription(
            $this->model,
            $fileId,
            $parsedBody['description'] ?? null
        );

        if ($result->failure === FileLifecycleResult::FAILURE_NOT_FOUND) {
            $message = $this->getMessages()['file_not_found'];
            return $this->messageResponse($response, $message, 405);
        }
        if ($result->success) {
            $message = $this->getMessages()['update_success'];
            return $this->messageResponse($response, $message, 200);
        }

        $errors = $result->errors;
        if (isset($errors['description'])) {
            $errors['description']['htmlName'] = 'eachDescription_' . $fileId;
        }
        $message = MessageUtils::errorHtml($errors, $this->model);
        return $this->messageResponse($response, $message, 405);
    }

    /**
     * Handles the deletion of a file via AJAX.
     *
     * This method validates the CSRF token, checks the POST request for the file ID,
     * retrieves the file from the database, deletes the corresponding file from the server,
     * and updates the database to remove the file record.
     * If any of these steps fail, an appropriate error message is returned as a JSON response.
     *
     * @return Response
     */
    public function handleDelete(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody() ?? [];

        // CSRF Token validation
        $errorResponse = $this->validateCsrfForJson($parsedBody, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        // Get the file ID from the POST request
        $fileId = $parsedBody['id'] ?? 0; // Default to 0 if no ID is provided

        $result = $this->fileLifecycleService->delete($this->model, $fileId);
        if ($result->failure === FileLifecycleResult::FAILURE_NOT_FOUND) {
            $message = $this->getMessages()['file_not_found'];
            return $this->messageResponse($response, $message, 405);
        }
        if ($result->failure === FileLifecycleResult::FAILURE_STORAGE) {
            $message = $this->getMessages()['file_delete_failed'];
            return $this->messageResponse($response, $message, 500);
        }
        if (!$result->success) {
            $message = $this->getMessages()['db_update_failed'];
            return $this->messageResponse($response, $message, 500);
        }

        // Send a success response back
        $message = $this->getMessages()['file_delete_success'];
        return $this->messageResponse($response, $message, 200);
    }
}
