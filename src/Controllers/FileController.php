<?php

namespace Jidaikobo\Kontiki\Controllers;

use Aura\Session\Session;
use Jidaikobo\Kontiki\Middleware\AuthMiddleware;
use Jidaikobo\Kontiki\Models\FileModel;
use Jidaikobo\Kontiki\Services\FileService;
use Jidaikobo\Kontiki\Utils\CsrfManager;
use Jidaikobo\Kontiki\Utils\MessageUtils;
use Jidaikobo\Kontiki\Utils\Pagination;
use Jidaikobo\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

class FileController extends BaseController
{
    protected PhpRenderer $view;
    protected FileModel $fileModel;
    protected FileService $fileService;
    protected CsrfManager $csrfManager;

    public function __construct(Session $session, PhpRenderer $view, FileModel $fileModel, FileService $fileService)
    {
        parent::__construct($view, $session, $fileModel);
        $this->fileService = $fileService;
    }

    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->group(
            '/admin',
            function (RouteCollectorProxy $group) {
                $group->get('/get_csrf_token', [FileController::class, 'getCsrfToken']);
                $group->get('/filelist', [FileController::class, 'filelist']);
                $group->post('/upload', [FileController::class, 'handleFileUpload']);
                $group->post('/update', [FileController::class, 'handleUpdate']);
                $group->post('/delete', [FileController::class, 'handleDelete']);
                $group->get('/fileManager.js', [FileController::class, 'serveJs']);
                $group->get('/fileManagerInstance.js', [FileController::class, 'serveInstanceJs']);
            }
        )->add(AuthMiddleware::class);
    }

    protected function getMessages(): array
    {
        return [
            'invalid_request' => __('invalid_request', 'Invalid request. Please try again.'),
            'validation_failed' => __('validation_failed', 'Data validation failed. Please check your input.'),
            'upload_success' => __('upload_success', 'The file has been successfully uploaded.'),
            'upload_error' => __('upload_error', 'The file could not be uploaded. Please try again.'),
            'database_update_failed' => __('database_update_failed', 'Failed to update the database. Please try again.'),
            'file_missing' => __('file_missing', 'No file uploaded or the file is corrupted.'),
            'method_not_allowed' => __('method_not_allowed', 'Method not allowed.'),
            'file_not_found' => __('file_not_found', 'File not found.'),
            'update_success' => __('update_success', 'The database has been updated successfully.'),
            'update_failed' => __('update_failed', 'Failed to update the database. Please try again.'),
            'file_id_required' => __('file_id_required', 'File ID is required.'),
            'file_delete_failed' => __('file_delete_failed', 'Failed to delete the file.'),
            'db_update_failed' => __('db_update_failed', 'Failed to update the database.'),
            'file_delete_success' => __('file_delete_success', 'File has been deleted successfully.'),
            'unexpected_error' => __('unexpected_error', 'An unexpected error occurred. Please try again later.'),
        ];
    }

    public function getCsrfToken(Request $request, Response $response): Response
    {
        $data = ['csrf_token' => $this->csrfManager->getToken()];
        return $this->jsonResponse($response, $data);
    }

    protected function messageResponse(Response $response, string $message, int $status): Response
    {
        $data = ['message' => $message];
        return $this->jsonResponse($response, $data, $status);
    }

    protected function errorResponse(Response $response, string $message, int $status): Response
    {
        return $this->messageResponse(
          $response,
          MessageUtils::alertHtml($message, 'warning'),
          $status
        );
    }

    protected function successResponse(Response $response, string $message): Response
    {
        return $this->messageResponse(
          $response,
          MessageUtils::alertHtml($message),
          200
        );
    }

    /**
     * Handles file upload via an AJAX request.
     * This method processes the uploaded file, moves it to the specified directory,
     * and returns a JSON response indicating the result of the operation.
     *
     * @return Response
     *
     * @throws Exception If there is an issue with moving the uploaded file or invalid request method.
     */
    public function handleFileUpload(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody() ?? [];

        // CSRF Token validation
        $errorResponse = $this->validateCsrfForJson($parsedBody, $response);
        if ($errorResponse) {
            return $errorResponse;
        }

        // prepare file
        $uploadedFile = $this->prepareUploadedFile($request);
        if (!$uploadedFile) {
            return $this->errorResponse($response, $this->getMessages()['file_missing'], 400);
        }

        // upload file
        $uploadResult = $this->fileService->upload($uploadedFile);
        if (!$uploadResult['success']) {
            return $this->errorResponse($response, $this->getMessages()['upload_error'], 500);
        }

        // update database
        $fileData = ['path' => $uploadResult['path']];
        if ($validationError = $this->validateAndSave($fileData, $response)) {
            return $validationError;
        }

        // success
        return $this->successResponse($response, $this->getMessages()['upload_success']);
    }

    protected function prepareUploadedFile(Request $request): ?array
    {
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['attachment'] ?? null;

        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK) {
            return [
                'name' => $uploadedFile->getClientFilename(),
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $uploadedFile->getStream()->getMetadata('uri'),
                'size' => $uploadedFile->getSize(),
            ];
        }

        return null;
    }

    private function validateAndSave(array $fileData, Response $response): ?Response
    {
        $fields = $this->model->getFieldDefinitions();
        $fields = $this->model->processFieldDefinitions('create', $fields);
        $validationResult = $this->model->validateByFields($fileData, $fields);

        if (!$validationResult['valid']) {
            return $this->messageResponse(
                $response,
                MessageUtils::errorHtml($validationResult['errors'], $this->model),
                405
            );
        }
        if (!$this->model->create($fileData)) {
            return $this->errorResponse(
                $response,
                $this->getMessages()['database_update_failed'],
                500
            );
        }
        return null;
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

        // Get the file ID from the POST request
        $fileId = $parsedBody['id'] ?? 0; // Default to 0 if no ID is provided

        // Retrieve the file details from the database using the file ID
        $data = $this->model->getById($fileId);

        if (!$data) {
            $message = $this->getMessages()['file_not_found'];
            return $this->messageResponse($response, $message, 405);
        }

        // Update the description field
        $data['description'] = $parsedBody['description'] ?? $data['description'];

        // Update the main item
        $result = $this->update($data, $fileId);

        if ($result['success']) {
            $message = $this->getMessages()['update_success'];
            return $this->messageResponse($response, $message, 200);
        } else {
            $message = MessageUtils::errorHtml($result['errors'], $this->model);
            return $this->messageResponse($response, $message, 405);
        }
    }

    /**
     * Validate and process data for create or edit actions.
     *
     * @param array $data The input data.
     * @param int|null $id The ID of the item to update (required for edit).
     * @return array The result containing 'success' (bool) and 'errors' (array).
     */
    protected function update(array $data, int $id = null)
    {
        $fields = $this->model->getFieldDefinitions();
        $fields = $this->model->processFieldDefinitions('edit', $fields);
        $results = $this->model->validateByFields($data, $fields);

        if ($results['valid'] !== true) {
            if (isset($results['errors']['description'])) {
                $newKey = 'eachDescription_' . $id;
                $results['errors']['description']['htmlName'] = $newKey;
            }

            return [
                'success' => false,
                'errors' => $results['errors']
            ];
        }

        // Process if valid
        $success = $this->model->update($id, $data);

        return [
            'success' => $success,
            'errors' => $success ? [] : ["Failed to update item."]
        ];
    }

    /**
     * Handles the AJAX request to fetch the file list.
     *
     * This method retrieves a list of files from the model, applies security headers
     * to the response, and then renders a view to display the file list.
     *
     * @return Response
     */
    public function filelist(Request $request, Response $response): Response
    {
        // Initialize Pagination and set total items
        $page = $request->getQueryParams()['page'] ?? 1;
        $itemsPerPage = 10;
        $pagination = new Pagination($page, $itemsPerPage);

        $keyword = $request->getQueryParams()['s'] ?? '';
        $query = $this->model->buildSearchConditions($keyword);
        $totalItems = $query->count();

        $pagination->setTotalItems($totalItems);
        $paginationHtml = $pagination->render(env('BASEPATH', '') . "/admin/filelist");

        $items = $query->limit($pagination->getLimit())
                  ->offset($pagination->getOffset())
                  ->orderBy('created_at', 'desc')
                  ->get()
                  ->map(fn($item) => (array) $item)
                  ->toArray();

        $items = $this->processItemsForList($request, $items);

        $content = $this->view->fetch(
            'forms/incFilelist.php',
            [
                'items' => $items,
                'pagination' => $paginationHtml
            ]
        );

        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'text/html')->withStatus(200);
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
     * @throws ResponseException If there is an error during the deletion process.
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

        // Retrieve the file details from the database using the file ID
        $data = $this->model->getById($fileId);

        if (!$data) {
            $message = $this->getMessages()['file_not_found'];
            return $this->messageResponse($response, $message, 405);
        }

        // Delete the file from the server
        $filePath = $data['path'];

        if ($this->deleteFileFromSystem($filePath)) {
            Log::write("File deleted: " . $filePath);
        } else {
            $message = $this->getMessages()['file_delete_failed'];
            return $this->messageResponse($response, $message, 500);
        }

        // Remove the file record from the database
        $deleteSuccess = $this->model->delete($fileId);
        if (!$deleteSuccess) {
            $message = $this->getMessages()['db_update_failed'];
            return $this->messageResponse($response, $message, 500);
        }

        // Send a success response back
        $message = $this->getMessages()['file_delete_success'];
        return $this->messageResponse($response, $message, 200);
    }

    private function deleteFileFromSystem(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/fileManager.js.php',
            [
                'uploading' => __('uploading'),
                'couldnt_upload' => __('couldnt_upload', "Could not upload"),
                'get_file_list' => __('get_file_list'),
                'couldnt_find_file' => __('couldnt_find_file'),
                'couldnt_get_file_list' => __('couldnt_get_file_list'),
                'copied' => __('copied'),
                'copy_failed' => __('copy_failed'),
                'close' => __('close'),
                'edit' => __('edit'),
                'couldnt_delete_file' => __('couldnt_delete_file'),
                'insert_success' => __('insert_success'),
            ]
        );
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'application/javascript; charset=utf-8')->withStatus(200);
    }

    /**
     * Serve the requested JavaScript file.
     *
     * @return Response
     */
    public function serveInstanceJs(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'js/fileManagerInstance.js.php',
            [
                'basepath' => env('BASEPATH', '')
            ]
        );
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'application/javascript; charset=utf-8')->withStatus(200);
    }

    private function processItemsForList(Request $request, array $items): array
    {
        foreach ($items as $key => $value) {
            $url = $this->pathToUrl($request, $items[$key]['path']);
            $items[$key]['imageOrLink'] = $this->renderImageOrLink($url, $items[$key]['description'] ?? '');
            $items[$key]['url'] = $url;
            $items[$key]['description'] = $items[$key]['description'] ?? ''; // don't use null
        }
        return $items;
    }

    /**
     * Render an image or a link based on the provided URL.
     *
     * @param string $url The input URL, either an image URL or a standard URL.
     * @param string|null $desc description text.
     * @return string The generated HTML.
     */
    private function renderImageOrLink(string $url, string $desc): string
    {
      // Check if the URL is an image URL (basic check based on file extension)
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));

        if (isset($pathInfo['extension']) && in_array(strtolower($pathInfo['extension']), $imageExtensions)) {
          // Return an <img> tag for images
            $descText = e($desc);
            $imgSrc = e($url);
            return '<img src="' . $imgSrc . '" alt="' . __('enlarge_x', 'Enlarge :name', ['name' => $descText]) . '" class="clickable-image img-thumbnail" tabindex="0">';
        }

      // Otherwise, return an <a> tag for links
        $linkHref = e($url);

        $extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : null;

        switch ($extension) {
            case 'pdf':
                $class = 'bi-filetype-pdf';
                break;
            case 'zip':
                $class = 'bi-file-zip';
                break;
            default:
                $class = 'bi-file-text';
                break;
        }

        return '<a href="' . $linkHref . '" target="_blank" aria-label="' . __('downlaod') . '" download class="bi ' . $class . ' display-3"><span class="visually-hidden">' . __('downlaod_x', 'Download :name', ['name' => $desc]) . '</span></a>';
    }

    protected function pathToUrl($request, $path)
    {
        $baseUrl = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost();
        return str_replace(dirname(__DIR__, 3), $baseUrl, $path);
    }
}
