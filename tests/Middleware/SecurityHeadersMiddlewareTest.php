<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Middleware;

use Jidaikobo\Kontiki\Config\SessionCookieConfig;
use Jidaikobo\Kontiki\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAppliesRestrictiveCspWithoutReflectingTheHostAsCorsOrigin(): void
    {
        $response = $this->process(new SessionCookieConfig(false));
        $csp = $response->getHeaderLine('Content-Security-Policy');

        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("base-uri 'self'", $csp);
        self::assertStringContainsString("form-action 'self'", $csp);
        self::assertStringContainsString("frame-ancestors 'self'", $csp);
        self::assertStringNotContainsString('code.jquery.com', $csp);
        self::assertStringNotContainsString('cdnjs.cloudflare.com', $csp);
        self::assertStringContainsString("font-src 'self'", $csp);
        self::assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        self::assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
        self::assertFalse($response->hasHeader('Strict-Transport-Security'));
    }

    public function testAppliesHstsOnlyForDeclaredHttpsDeployment(): void
    {
        $response = $this->process(new SessionCookieConfig(true));

        self::assertSame(
            'max-age=31536000; includeSubDomains',
            $response->getHeaderLine('Strict-Transport-Security')
        );
    }

    private function process(SessionCookieConfig $config): ResponseInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());

        return (new SecurityHeadersMiddleware($config))->process(
            $this->createMock(ServerRequestInterface::class),
            $handler
        );
    }
}
