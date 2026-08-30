<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Controllers;

use Jidaikobo\Kontiki\Controllers\BaseController;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\AdminUrlGenerator;
use Jidaikobo\Kontiki\Services\RoutesService;
use PHPUnit\Framework\TestCase;
use Slim\Views\PhpRenderer;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class BaseControllerDependencyTest extends TestCase
{
    public function testUsesInjectedCsrfValidationService(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $flashManager = $this->createMock(FlashManager::class);
        $service = new CsrfValidationService($csrfManager, $flashManager);
        $view = $this->createMock(PhpRenderer::class);
        $routes = $this->createMock(RoutesService::class);
        $routes->method('getAdminUrlGenerator')
            ->willReturn(new AdminUrlGenerator(''));
        $routes->method('getRoutesByController')->willReturn([]);
        $routes->method('getRoutesByType')->willReturn([]);

        $controller = new class (
            $csrfManager,
            $flashManager,
            $view,
            $routes,
            $service
        ) extends BaseController {
            public function csrfValidationService(): CsrfValidationService
            {
                return $this->csrfValidationService;
            }
        };

        self::assertSame($service, $controller->csrfValidationService());
    }

    public function testAbsolutePathRedirectUsesInjectedAdminBasePath(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $flashManager = $this->createMock(FlashManager::class);
        $routes = $this->createMock(RoutesService::class);
        $routes->method('getAdminUrlGenerator')
            ->willReturn(new AdminUrlGenerator('/cms/admin'));
        $routes->method('getRoutesByController')->willReturn([]);
        $routes->method('getRoutesByType')->willReturn([]);

        $controller = new class (
            $csrfManager,
            $flashManager,
            $this->createMock(PhpRenderer::class),
            $routes
        ) extends BaseController {
            public function redirectTo(
                \Psr\Http\Message\ServerRequestInterface $request,
                \Psr\Http\Message\ResponseInterface $response,
                string $target
            ): \Psr\Http\Message\ResponseInterface {
                return $this->redirectResponse($request, $response, $target);
            }
        };

        $response = $controller->redirectTo(
            (new ServerRequestFactory())->createServerRequest('GET', '/'),
            (new ResponseFactory())->createResponse(),
            '/post/index'
        );

        self::assertSame('/cms/admin/post/index', $response->getHeaderLine('Location'));
    }
}
