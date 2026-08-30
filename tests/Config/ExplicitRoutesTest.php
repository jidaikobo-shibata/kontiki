<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use DI\Container;
use Jidaikobo\Kontiki\Config\PostRoutes;
use Jidaikobo\Kontiki\Config\UserRoutes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Interfaces\RouteInterface;
use Slim\Psr7\Factory\ResponseFactory;

final class ExplicitRoutesTest extends TestCase
{
    /** @return iterable<string, array{class-string, array<string, list<string>>}> */
    public static function routeProvider(): iterable
    {
        yield 'post routes' => [PostRoutes::class, [
            '/post/index' => ['GET'],
            '/post/index/published' => ['GET'],
            '/post/index/pending' => ['GET'],
            '/post/index/draft' => ['GET'],
            '/post/index/reserved' => ['GET'],
            '/post/index/expired' => ['GET'],
            '/post/create' => ['GET', 'POST'],
            '/post/edit/{id}' => ['GET', 'POST'],
            '/post/index/trash' => ['GET'],
            '/post/trash/{id}' => ['GET', 'POST'],
            '/post/restore/{id}' => ['GET', 'POST'],
            '/post/delete/{id}' => ['GET', 'POST'],
            '/post/preview' => ['GET'],
            '/post/preview/{id}' => ['GET'],
        ]];
        yield 'user routes' => [UserRoutes::class, [
            '/user/index' => ['GET'],
            '/user/create' => ['GET', 'POST'],
            '/user/edit/{id}' => ['GET', 'POST'],
            '/user/delete/{id}' => ['GET', 'POST'],
        ]];
    }

    /**
     * @param class-string $routesClass
     * @param array<string, list<string>> $expected
     */
    #[DataProvider('routeProvider')]
    public function testRegistersExplicitRoutes(string $routesClass, array $expected): void
    {
        $app = $this->createApp();
        (new $routesClass())->register($app);

        self::assertSame($expected, $this->routeMap($app));
    }

    /** @return App<Container> */
    private function createApp(): App
    {
        return new App(new ResponseFactory(), new Container());
    }

    /**
     * @param App<Container> $app
     * @return array<string, list<string>>
     */
    private function routeMap(App $app): array
    {
        $routes = [];
        foreach ($app->getRouteCollector()->getRoutes() as $route) {
            $routes[$route->getPattern()] ??= [];
            $routes[$route->getPattern()] = array_values(array_unique([
                ...$routes[$route->getPattern()],
                ...$this->httpMethods($route),
            ]));
        }

        return $routes;
    }

    /** @return list<string> */
    private function httpMethods(RouteInterface $route): array
    {
        return array_values(array_filter(
            $route->getMethods(),
            static fn(string $method): bool => $method !== 'HEAD'
        ));
    }
}
