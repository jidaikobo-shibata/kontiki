<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\AdminUrlGenerator;
use Jidaikobo\Kontiki\Services\RoutesService;
use PHPUnit\Framework\TestCase;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteInterface;

final class RoutesServiceTest extends TestCase
{
    public function testItPrefixesCollectedRoutesWithTheAdminBasePath(): void
    {
        $route = $this->createMock(RouteInterface::class);
        $route->method('getPattern')->willReturn('/post/create');
        $route->method('getName')->willReturn('post|x_create|sidebar');
        $route->method('getMethods')->willReturn(['GET']);

        $collector = $this->createMock(RouteCollectorInterface::class);
        $collector->method('getRoutes')->willReturn([$route]);

        $routes = (new RoutesService(
            $collector,
            new AdminUrlGenerator('/cms/admin/')
        ))->getRoutesByController('post');

        self::assertSame('/cms/admin/post/create', $routes[0]['path']);
        self::assertSame('GET', $routes[0]['methods']);
        self::assertSame(['sidebar'], $routes[0]['type']);
    }
}
