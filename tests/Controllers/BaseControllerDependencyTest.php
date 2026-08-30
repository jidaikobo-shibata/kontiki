<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Controllers;

use Jidaikobo\Kontiki\Controllers\BaseController;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\RoutesService;
use PHPUnit\Framework\TestCase;
use Slim\Views\PhpRenderer;

final class BaseControllerDependencyTest extends TestCase
{
    public function testUsesInjectedCsrfValidationService(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $flashManager = $this->createMock(FlashManager::class);
        $service = new CsrfValidationService($csrfManager, $flashManager);
        $view = $this->createMock(PhpRenderer::class);
        $routes = $this->createMock(RoutesService::class);
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
}
