<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Middleware;

use Jidaikobo\Kontiki\Config\GuestRouteRegistry;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Middleware\AuthMiddleware;
use Jidaikobo\Kontiki\Services\RequestOriginService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Slim\Routing\RouteParser;
use Slim\Routing\RoutingResults;
use Slim\Views\PhpRenderer;

final class AuthMiddlewareTest extends TestCase
{
    public function testAllowsOnlyTheExplicitlyRegisteredGuestRoute(): void
    {
        $guestRoute = $this->route('guest-login');
        $registry = new GuestRouteRegistry();
        $registry->allow($guestRoute);
        $auth = $this->createMock(Auth::class);
        $auth->expects(self::never())->method('refreshCurrentUser');
        $expected = new Response(204);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($expected);

        $actual = $this->middleware($auth, $registry)->process(
            $this->request($guestRoute, '/login'),
            $handler
        );

        self::assertSame($expected, $actual);
    }

    public function testDoesNotTreatAnotherRouteEndingInLoginAsGuest(): void
    {
        $guestRoute = $this->route('guest-login');
        $protectedRoute = $this->route('nested-login');
        $registry = new GuestRouteRegistry();
        $registry->allow($guestRoute);
        $auth = $this->createMock(Auth::class);
        $auth->method('refreshCurrentUser')->willReturn(false);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $actual = $this->middleware($auth, $registry)->process(
            $this->request($protectedRoute, '/nested/login'),
            $handler
        );

        self::assertSame(404, $actual->getStatusCode());
    }

    private function middleware(Auth $auth, GuestRouteRegistry $registry): AuthMiddleware
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->method('fetch')->willReturn('<p>Not found</p>');
        $view->method('render')->willReturnCallback(
            static fn(ResponseInterface $response): ResponseInterface => $response
        );
        $origin = $this->createMock(RequestOriginService::class);
        $origin->method('hasInternalReferer')->willReturn(false);

        return new AuthMiddleware(
            $view,
            $auth,
            $this->createMock(RouteParser::class),
            null,
            $origin,
            $registry
        );
    }

    private function route(string $identifier): RouteInterface
    {
        $route = $this->createMock(RouteInterface::class);
        $route->method('getIdentifier')->willReturn($identifier);

        return $route;
    }

    private function request(RouteInterface $route, string $path): ServerRequestInterface
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getPath')->willReturn($path);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $routeParser = $this->createMock(RouteParserInterface::class);
        $routingResults = $this->createMock(RoutingResults::class);
        $request->method('getAttribute')->willReturnCallback(
            static fn(string $name): mixed => match ($name) {
                RouteContext::ROUTE => $route,
                RouteContext::ROUTE_PARSER => $routeParser,
                RouteContext::ROUTING_RESULTS => $routingResults,
                default => null,
            }
        );

        return $request;
    }
}
