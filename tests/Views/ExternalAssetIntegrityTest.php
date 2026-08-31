<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Views;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalAssetIntegrityTest extends TestCase
{
    private const BOOTSTRAP_CSS_INTEGRITY =
        'sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH';
    private const BOOTSTRAP_JS_INTEGRITY =
        'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz';

    /** @return iterable<string, array{string, list<string>}> */
    public static function viewProvider(): iterable
    {
        yield 'administration layout' => [
            'layout.php',
            [
                self::BOOTSTRAP_CSS_INTEGRITY,
                self::BOOTSTRAP_JS_INTEGRITY,
            ],
        ];
        yield 'login layout' => [
            'layout-simple.php',
            [self::BOOTSTRAP_CSS_INTEGRITY],
        ];
        yield 'help layout' => [
            'layout-help.php',
            [self::BOOTSTRAP_CSS_INTEGRITY],
        ];
        yield 'fallback preview' => [
            'post/preview.php',
            [self::BOOTSTRAP_CSS_INTEGRITY],
        ];
    }

    /** @param list<string> $integrities */
    #[DataProvider('viewProvider')]
    public function testExternalAssetsHavePinnedIntegrity(
        string $relativePath,
        array $integrities
    ): void {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/src/views/' . $relativePath
        );

        self::assertIsString($contents);
        self::assertStringNotContainsString('bootstrap@5.3.0', $contents);
        self::assertStringNotContainsString('admin-lte', $contents);
        self::assertSame(
            substr_count($contents, 'cdn.jsdelivr.net'),
            substr_count($contents, 'crossorigin="anonymous"')
        );

        foreach ($integrities as $integrity) {
            self::assertStringContainsString(
                'integrity="' . $integrity . '"',
                $contents
            );
        }
    }
}
