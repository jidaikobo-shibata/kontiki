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
use Jidaikobo\Kontiki\Services\AdminUrlGenerator;
use Jidaikobo\Kontiki\Services\ApplicationClock;
use Jidaikobo\Kontiki\Services\FileLifecycleService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\ConfirmationFormService;
use Jidaikobo\Kontiki\Services\FormPageService;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\HelpContentService;
use Jidaikobo\Kontiki\Services\ModelValidationService;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\RecordMutationService;
use Jidaikobo\Kontiki\Services\RecordMutationFeedbackService;
use Jidaikobo\Kontiki\Services\RequestOriginService;
use Jidaikobo\Kontiki\Services\PreviewRendererFactory;
use Jidaikobo\Kontiki\Services\UploadPathMapper;
use Jidaikobo\Kontiki\Services\UploadedFileAdapter;
use Jidaikobo\Kontiki\Services\ValidationService;
use Jidaikobo\Kontiki\Services\SaveMessageService;
use Jidaikobo\Kontiki\Services\SaveRedirectService;
use Jidaikobo\Kontiki\Controllers\FileController;
use Jidaikobo\Kontiki\Controllers\HelpController;
use Jidaikobo\Kontiki\Controllers\AccountController;
use Jidaikobo\Kontiki\Controllers\AuthController;
use Jidaikobo\Kontiki\Controllers\CategoryController;
use Jidaikobo\Kontiki\Controllers\PostController;
use Jidaikobo\Kontiki\Controllers\UserController;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Middleware\AuthMiddleware;
use Jidaikobo\Kontiki\Models\FileModel;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Models\UserModel;
use Jidaikobo\Kontiki\Renderers\TableRenderer;

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
        $container->set(
            AdminUrlGenerator::class,
            fn() => new AdminUrlGenerator(env('BASEPATH', ''))
        );
        $container->set(
            ApplicationClock::class,
            fn() => new ApplicationClock(env('TIMEZONE', 'UTC'))
        );
        $container->set(
            AuthMiddleware::class,
            \DI\autowire()
                ->constructorParameter(
                    'adminUrlGenerator',
                    \DI\get(AdminUrlGenerator::class)
                )
                ->constructorParameter(
                    'requestOriginService',
                    \DI\get(RequestOriginService::class)
                )
        );
        $container->set(RequestOriginService::class, fn() => new RequestOriginService());
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
        $container->set(
            FormPageService::class,
            fn($c) => new FormPageService($c->get(FormService::class))
        );
        $container->set(
            FormService::class,
            \DI\autowire()->constructorParameter(
                'adminUrlGenerator',
                \DI\get(AdminUrlGenerator::class)
            )
        );
        $container->set(
            TableRenderer::class,
            \DI\autowire()->constructorParameter(
                'adminUrlGenerator',
                \DI\get(AdminUrlGenerator::class)
            )->constructorParameter(
                'applicationClock',
                \DI\get(ApplicationClock::class)
            )
        );
        $container->set(
            ConfirmationFormService::class,
            fn($c) => new ConfirmationFormService($c->get(FormService::class))
        );
        $container->set(
            ModelValidationService::class,
            fn($c) => new ModelValidationService($c->get(FlashManager::class))
        );
        $container->set(SaveRedirectService::class, fn() => new SaveRedirectService());
        $container->set(
            SaveMessageService::class,
            fn($c) => new SaveMessageService($c->get(FlashManager::class))
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
        $container->set(
            RoutesService::class,
            fn($c) => $this->createRoutesService($c)
        );
        $container->set(
            RecordPersistenceService::class,
            fn($c) => new RecordPersistenceService($c->get(Database::class))
        );
        $container->set(
            RecordMutationService::class,
            fn() => new RecordMutationService()
        );
        $container->set(
            RecordMutationFeedbackService::class,
            fn($c) => new RecordMutationFeedbackService(
                $c->get(FlashManager::class)
            )
        );
        $container->set(
            PreviewRendererFactory::class,
            fn() => new PreviewRendererFactory(env('PROJECT_PATH', ''))
        );
        $container->set(
            HelpContentService::class,
            fn($c) => new HelpContentService(
                __DIR__ . '/../locale',
                env('APPLANG', 'en'),
                $c->get(AdminUrlGenerator::class)
            )
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
            $c->get(UserModel::class),
            $c->get(AdminUrlGenerator::class),
            $c->get(ApplicationClock::class)
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
            HelpController::class,
            PostController::class,
            UserController::class,
        ];
        $saveControllers = [
            AccountController::class,
            PostController::class,
            UserController::class,
        ];
        $mutationControllers = [
            PostController::class,
            UserController::class,
        ];
        foreach ($controllers as $controller) {
            $definition = \DI\autowire()->constructorParameter(
                'csrfValidationService',
                \DI\get(CsrfValidationService::class)
            );
            if (in_array($controller, $saveControllers, true)) {
                $definition
                    ->constructorParameter(
                        'formPageService',
                        \DI\get(FormPageService::class)
                    )
                    ->constructorParameter(
                        'modelValidationService',
                        \DI\get(ModelValidationService::class)
                    )
                    ->constructorParameter(
                        'saveRedirectService',
                        \DI\get(SaveRedirectService::class)
                    )
                    ->constructorParameter(
                        'saveMessageService',
                        \DI\get(SaveMessageService::class)
                    );
            }
            if (in_array($controller, $mutationControllers, true)) {
                $definition->constructorParameter(
                    'recordMutationService',
                    \DI\get(RecordMutationService::class)
                )->constructorParameter(
                    'confirmationFormService',
                    \DI\get(ConfirmationFormService::class)
                )->constructorParameter(
                    'recordMutationFeedbackService',
                    \DI\get(RecordMutationFeedbackService::class)
                );
            }
            if ($controller === PostController::class) {
                $definition->constructorParameter(
                    'previewRendererFactory',
                    \DI\get(PreviewRendererFactory::class)
                );
            }
            if ($controller === HelpController::class) {
                $definition->constructorParameter(
                    'helpContentService',
                    \DI\get(HelpContentService::class)
                );
            }
            $container->set($controller, $definition);
        }
    }

    private function createRoutesService(Container $container): RoutesService
    {
        return new RoutesService(
            $this->app->getRouteCollector(),
            $container->get(AdminUrlGenerator::class)
        );
    }
}
