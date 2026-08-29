<?php

namespace Jidaikobo\Kontiki\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Controllers\FileControllerTraits;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\FileModel;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\FileService;

class FileController extends BaseController
{
    use FileControllerTraits\CRUDTrait;
    use FileControllerTraits\AssetTrait;
    use FileControllerTraits\ListTrait;
    use FileControllerTraits\MessagesTrait;

    private FileModel $model;
    private FileService $fileService;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        FileModel $model,
        FileService $fileService
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService
        );
        $this->model = $model;
        $this->fileService = $fileService;
    }

    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->get('/get_csrf_token', FileController::class . ':callGetCsrfToken');
        $app->get('/filelist', FileController::class . ':callFilelist');
        $app->post('/upload', FileController::class . ':callHandleFileUpload');
        $app->post('/update', FileController::class . ':callHandleUpdate');
        $app->post('/delete', FileController::class . ':callHandleDelete');
        $app->get('/kontiki-file.js', FileController::class . ':callServeJs');
        $app->get('/kontiki-file-csrf.js', FileController::class . ':callServeCsrfJs');
        $app->get('/kontiki-file-utils.js', FileController::class . ':callServeUtilsJs');
        $app->get('/kontiki-file-lightbox.js', FileController::class . ':callServeLightboxJs');
        $app->get('/kontiki-file-index.js', FileController::class . ':callServeIndexJs');
        $app->get('/kontiki-file-uploader.js', FileController::class . ':callServeUploaderJs');
        $app->get('/kontiki-file.css', FileController::class . ':callServeCss');
    }

    public function callGetCsrfToken(Request $request, Response $response): Response
    {
        return $this->getCsrfToken($request, $response);
    }

    public function callFilelist(Request $request, Response $response): Response
    {
        return $this->filelist($request, $response);
    }

    public function callHandleFileUpload(Request $request, Response $response): Response
    {
        return $this->handleFileUpload($request, $response);
    }

    public function callHandleUpdate(Request $request, Response $response): Response
    {
        return $this->handleUpdate($request, $response);
    }

    public function callHandleDelete(Request $request, Response $response): Response
    {
        return $this->handleDelete($request, $response);
    }

    public function callServeJs(Request $request, Response $response): Response
    {
        return $this->serveJs($request, $response);
    }

    public function callServeCsrfJs(Request $request, Response $response): Response
    {
        return $this->serveCsrfJs($request, $response);
    }

    public function callServeUtilsJs(Request $request, Response $response): Response
    {
        return $this->serveUtilsJs($request, $response);
    }

    public function callServeLightboxJs(Request $request, Response $response): Response
    {
        return $this->serveLightboxJs($request, $response);
    }

    public function callServeIndexJs(Request $request, Response $response): Response
    {
        return $this->serveIndexJs($request, $response);
    }

    public function callServeUploaderJs(Request $request, Response $response): Response
    {
        return $this->serveUploaderJs($request, $response);
    }

    public function callServeCss(Request $request, Response $response): Response
    {
        return $this->serveCss($request, $response);
    }

    /**
     * Get base URL and upload dir. Keep them in one place.
     */
    private function uploadBaseUrl(): string
    {
        // e.g. https://example.com/uploads
        return rtrim(env('BASEURL'), '/') . rtrim(env('BASEURL_UPLOAD_DIR'), '/');
    }

    private function uploadDir(): string
    {
        // e.g. /var/www/app/public/uploads
        return rtrim(env('PROJECT_PATH', '') . env('UPLOADDIR'), DIRECTORY_SEPARATOR);
    }

    /**
     * Convert filesystem path under uploadDir to URL under uploadBaseUrl.
     * Assumptions:
     *  - $path is an absolute path inside uploadDir (no "..")
     *  - Inputs are already “clean” going forward
     */
    protected function pathToUrl(string $path): string
    {
        $baseUrl = $this->uploadBaseUrl();
        $baseDir = $this->uploadDir();

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, $baseUrl)) {
            $tail = ltrim(substr($path, strlen($baseUrl)), '/');
            return $baseUrl . ($tail !== '' ? '/' . $tail : '');
        }

        $normPath = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        if (str_starts_with($normPath, $baseDir . DIRECTORY_SEPARATOR) || $normPath === $baseDir) {
            $tail = ltrim(substr($normPath, strlen($baseDir)), DIRECTORY_SEPARATOR);
            return $baseUrl . ($tail !== '' ? '/' . str_replace(DIRECTORY_SEPARATOR, '/', $tail) : '');
        }

        return '';
    }

    /**
     * Convert URL under uploadBaseUrl to filesystem path under uploadDir.
     * Assumptions:
     *  - $url starts with uploadBaseUrl
     *  - URL may have query/fragment; they are ignored
     */
    protected function urlToPath(string $url): string
    {
        $baseUrl = $this->uploadBaseUrl();
        $baseDir = $this->uploadDir();

        if ($url === '') {
            return '';
        }

        $noQuery = strtok($url, '?#') ?: $url;

        if (str_starts_with($noQuery, $baseUrl)) {
            $tail = ltrim(substr($noQuery, strlen($baseUrl)), '/');
            return $baseDir . ($tail !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tail) : '');
        }

        $norm = str_replace('\\', DIRECTORY_SEPARATOR, $noQuery);
        if (str_starts_with($norm, $baseDir . DIRECTORY_SEPARATOR) || $norm === $baseDir) {
            return $norm;
        }

        return '';
    }
}
