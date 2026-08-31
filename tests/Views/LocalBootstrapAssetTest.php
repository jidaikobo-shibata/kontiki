<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Views;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalBootstrapAssetTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function viewProvider(): iterable
    {
        yield 'administration layout' => ['layout.php', true];
        yield 'login layout' => ['layout-simple.php', false];
        yield 'help layout' => ['layout-help.php', false];
        yield 'fallback preview' => ['post/preview.php', false];
    }

    #[DataProvider('viewProvider')]
    public function testBootstrapUsesLocalVersionedRoutes(
        string $relativePath,
        bool $expectsJavaScript
    ): void {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/src/views/' . $relativePath
        );

        self::assertIsString($contents);
        self::assertStringContainsString('/vendor/bootstrap.min.css', $contents);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $contents);
        self::assertStringNotContainsString('admin-lte', $contents);
        self::assertSame(
            $expectsJavaScript,
            str_contains($contents, '/vendor/bootstrap.bundle.min.js')
        );
    }
}
