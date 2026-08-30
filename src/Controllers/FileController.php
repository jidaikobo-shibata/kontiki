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
use Jidaikobo\Kontiki\Services\FileLifecycleService;
use Jidaikobo\Kontiki\Services\UploadPathMapper;

class FileController extends BaseController
{
    use FileControllerTraits\CRUDTrait;
    use FileControllerTraits\AssetTrait;
    use FileControllerTraits\ListTrait;
    use FileControllerTraits\MessagesTrait;

    private FileModel $model;
    private FileLifecycleService $fileLifecycleService;
    private UploadPathMapper $uploadPathMapper;

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
        $this->uploadPathMapper = new UploadPathMapper(
            $this->uploadBaseUrl(),
            $this->uploadDir()
        );
        $this->fileLifecycleService = new FileLifecycleService(
            $fileService,
            $this->uploadPathMapper
        );
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
        return $this->uploadPathMapper->pathToUrl($path);
    }

    /**
     * Convert URL under uploadBaseUrl to filesystem path under uploadDir.
     * Assumptions:
     *  - $url starts with uploadBaseUrl
     *  - URL may have query/fragment; they are ignored
     */
    protected function urlToPath(string $url): string
    {
        return $this->uploadPathMapper->urlToPath($url);
    }
}
