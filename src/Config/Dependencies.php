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
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\ValidationService;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Models\UserModel;

class Dependencies
{
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
        $container->set(Auth::class, fn($c) => $this->createAuth($c));
        $container->set(ValidationService::class, fn($c) => $this->createValidationService($c));
        $container->set(PhpRenderer::class, fn() => $this->createPhpRenderer());
        $container->set(FileService::class, fn() => $this->createFileService());
        $container->set(RoutesService::class, fn() => $this->createRoutesService());
        $container->set(RouteParser::class, fn() => $this->app->getRouteCollector()->getRouteParser());
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

    private function createRoutesService(): RoutesService
    {
        return new RoutesService($this->app->getRouteCollector());
    }
}
