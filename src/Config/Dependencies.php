<?php

namespace Jidaikobo\Kontiki\Config;

use Aura\Session\SessionFactory;
use Aura\Session\Session;
use DI\Container;
use Slim\App;
use Slim\Routing\RouteParser;
use Slim\Views\PhpRenderer;
use Valitron\Validator;
use Jidaikobo\Kontiki\Services\FileService;
use Jidaikobo\Kontiki\Services\FileLifecycleService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\UploadPathMapper;
use Jidaikobo\Kontiki\Services\UploadedFileAdapter;
use Jidaikobo\Kontiki\Services\ValidationService;
use Jidaikobo\Kontiki\Controllers\FileController;
use Jidaikobo\Kontiki\Controllers\AccountController;
use Jidaikobo\Kontiki\Controllers\AuthController;
use Jidaikobo\Kontiki\Controllers\CategoryController;
use Jidaikobo\Kontiki\Controllers\PostController;
use Jidaikobo\Kontiki\Controllers\UserController;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\FileModel;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Models\UserModel;

class Dependencies
{
    /** @param App<Container> $app */
    public function __construct(private App $app)
    {
    }

    public function register(): void
    {
        /** @var Container $container */
        $container = $this->app->getContainer();

        $container->set(App::class, $this->app);
        $container->set(Database::class, fn() => $this->createDatabase());
        $container->set(Session::class, fn() => $this->createSession());
        $container->set(PostModel::class, fn($c) => $this->createPostModel($c));
        $container->set(UserModel::class, fn($c) => $this->createUserModel($c));
        $container->set(FileModel::class, fn($c) => $this->createFileModel($c));
        $container->set(Auth::class, fn($c) => $this->createAuth($c));
        $container->set(
            CsrfValidationService::class,
            fn($c) => new CsrfValidationService(
                $c->get(CsrfManager::class),
                $c->get(FlashManager::class)
            )
        );
        $container->set(ValidationService::class, fn($c) => $this->createValidationService($c));
        $container->set(PhpRenderer::class, fn() => $this->createPhpRenderer());
        $container->set(FileService::class, fn() => $this->createFileService());
        $container->set(UploadPathMapper::class, fn() => $this->createUploadPathMapper());
        $container->set(UploadedFileAdapter::class, fn() => new UploadedFileAdapter());
        $container->set(
            FileLifecycleService::class,
            fn($c) => new FileLifecycleService(
                $c->get(FileService::class),
                $c->get(UploadPathMapper::class)
            )
        );
        $container->set(RoutesService::class, fn() => $this->createRoutesService());
        $container->set(
            RecordPersistenceService::class,
            fn($c) => new RecordPersistenceService($c->get(Database::class))
        );
        $container->set(RouteParser::class, fn() => $this->app->getRouteCollector()->getRouteParser());
        $this->registerControllerDefinitions($container);
        $container->set(FileController::class, fn($c) => $this->createFileController($c));
    }

    private function createDatabase(): Database
    {
        return new Database([
            'driver' => 'sqlite',
            'database' => env('PROJECT_PATH', '') . '/' . env('DB_DATABASE', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
    }

    private function createSession(): Session
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (
            str_contains($uri, '.js') ||
            str_contains($uri, '.css') ||
            str_contains($uri, '.ico')
        ) {
            session_cache_limiter('private_no_expire');
        }
        return (new SessionFactory())->newInstance($_COOKIE);
    }

    private function createPostModel(Container $c): PostModel
    {
        return new PostModel(
            $c->get(Database::class),
            $c->get(ValidationService::class),
            $c->get(Auth::class),
            $c->get(UserModel::class)
        );
    }

    private function createUserModel(Container $c): UserModel
    {
        return new UserModel(
            $c->get(Database::class),
            $c->get(ValidationService::class)
        );
    }

    private function createFileModel(Container $c): FileModel
    {
        return new FileModel(
            $c->get(Database::class),
            $c->get(ValidationService::class)
        );
    }

    private function createAuth(Container $c): Auth
    {
        return new Auth(
            $c->get(Session::class),
            $c->get(UserModel::class)
        );
    }

    private function createValidationService(Container $c): ValidationService
    {
        $validator = new Validator([], [], env('APPLANG', 'en'));
        return new ValidationService($c->get(Database::class), $validator);
    }

    private function createPhpRenderer(): PhpRenderer
    {
        return new PhpRenderer(__DIR__ . '/../../src/views');
    }

    private function createFileService(): FileService
    {
        $uploadDir = env('PROJECT_PATH', '') . env('UPLOADDIR', '');
        $allowedTypes = json_decode(env('ALLOWED_MIME_TYPES', '[]'), true);
        $maxSize = env('MAXSIZE', 5000000);
        return new FileService($uploadDir, $allowedTypes, $maxSize);
    }

    private function createUploadPathMapper(): UploadPathMapper
    {
        $baseUrl = rtrim(env('BASEURL', ''), '/')
            . rtrim(env('BASEURL_UPLOAD_DIR', ''), '/');
        $uploadDir = rtrim(
            env('PROJECT_PATH', '') . env('UPLOADDIR', ''),
            DIRECTORY_SEPARATOR
        );

        return new UploadPathMapper($baseUrl, $uploadDir);
    }

    private function createFileController(Container $c): FileController
    {
        return new FileController(
            $c->get(CsrfManager::class),
            $c->get(FlashManager::class),
            $c->get(PhpRenderer::class),
            $c->get(RoutesService::class),
            $c->get(FileModel::class),
            $c->get(FileService::class),
            $c->get(UploadPathMapper::class),
            $c->get(FileLifecycleService::class),
            $c->get(UploadedFileAdapter::class),
            $c->get(CsrfValidationService::class)
        );
    }

    private function registerControllerDefinitions(Container $container): void
    {
        $controllers = [
            AccountController::class,
            AuthController::class,
            CategoryController::class,
            PostController::class,
            UserController::class,
        ];
        foreach ($controllers as $controller) {
            $container->set(
                $controller,
                \DI\autowire()->constructorParameter(
                    'csrfValidationService',
                    \DI\get(CsrfValidationService::class)
                )
            );
        }
    }

    private function createRoutesService(): RoutesService
    {
        return new RoutesService($this->app->getRouteCollector());
    }
}
