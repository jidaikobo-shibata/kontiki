<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use InvalidArgumentException;
use Jidaikobo\Kontiki\Config\SessionCookieConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SessionCookieConfigTest extends TestCase
{
    /** @return iterable<string, array{string, string, bool}> */
    public static function configurationProvider(): iterable
    {
        yield 'https fallback' => ['', 'https://example.com', true];
        yield 'http fallback' => ['', 'http://example.com', false];
        yield 'proxy explicit secure' => ['true', 'http://internal.example', true];
        yield 'explicit insecure override' => ['false', 'https://example.com', false];
        yield 'numeric true' => ['1', '', true];
        yield 'numeric false' => ['0', '', false];
    }

    #[DataProvider('configurationProvider')]
    public function testResolvesExplicitSettingBeforeBaseUrl(
        string $configuredValue,
        string $baseUrl,
        bool $expected
    ): void {
        self::assertSame(
            $expected,
            SessionCookieConfig::resolve($configuredValue, $baseUrl)->secure
        );
    }

    public function testRejectsAmbiguousConfiguration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SessionCookieConfig::resolve('yes', 'https://example.com');
    }
}
