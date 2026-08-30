<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Middleware;

use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Middleware\AdminAuthorizationMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Views\PhpRenderer;

final class AdminAuthorizationMiddlewareTest extends TestCase
{
    public function testAllowsAdministratorsToReachTheHandler(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('isAdminLoggedIn')->willReturn(true);
        $view = $this->createMock(PhpRenderer::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $expected = new Response(204);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($expected);

        $actual = (new AdminAuthorizationMiddleware($auth, $view))
            ->process($request, $handler);

        self::assertSame($expected, $actual);
    }

    public function testReturnsNotFoundWithoutCallingTheHandlerForEditors(): void
    {
        $auth = $this->createMock(Auth::class);
        $auth->method('isAdminLoggedIn')->willReturn(false);
        $view = $this->createMock(PhpRenderer::class);
        $view->method('fetch')->with('error/404.php')->willReturn('<p>Not found</p>');
        $view->method('render')->willReturnCallback(
            static fn(ResponseInterface $response): ResponseInterface => $response
        );
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $actual = (new AdminAuthorizationMiddleware($auth, $view))->process(
            $this->createMock(ServerRequestInterface::class),
            $handler
        );

        self::assertSame(404, $actual->getStatusCode());
        self::assertSame('text/html', $actual->getHeaderLine('Content-Type'));
    }
}
