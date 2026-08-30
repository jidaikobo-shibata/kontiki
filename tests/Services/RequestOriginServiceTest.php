<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\RequestOriginService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class RequestOriginServiceTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function refererProvider(): iterable
    {
        yield 'same host' => ['https://example.test/admin/post', 'https://example.test/admin', true];
        yield 'host is case insensitive' => ['https://EXAMPLE.test/admin', 'https://example.TEST/', true];
        yield 'host with different port' => ['https://example.test:8443/admin', 'https://example.test/', true];
        yield 'host as subdomain suffix' => ['https://example.test.evil.test/', 'https://example.test/', false];
        yield 'host embedded in path' => ['https://evil.test/example.test/', 'https://example.test/', false];
        yield 'missing referer' => ['', 'https://example.test/', false];
        yield 'relative referer' => ['/admin/post', 'https://example.test/', false];
    }

    #[DataProvider('refererProvider')]
    public function testItRequiresAnExactRefererHost(
        string $referer,
        string $requestUri,
        bool $expected
    ): void {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', $requestUri)
            ->withHeader('Referer', $referer);

        self::assertSame(
            $expected,
            (new RequestOriginService())->hasInternalReferer($request)
        );
    }

    public function testItFallsBackToTheHostHeader(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/admin')
            ->withHeader('Host', 'example.test:8443')
            ->withHeader('Referer', 'https://example.test:8443/post');

        self::assertTrue((new RequestOriginService())->hasInternalReferer($request));
    }
}
