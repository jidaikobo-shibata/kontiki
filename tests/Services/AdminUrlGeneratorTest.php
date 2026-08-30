<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AdminUrlGeneratorTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function pathProvider(): iterable
    {
        yield 'nested base path' => ['/cms/admin', '/post/edit/1', '/cms/admin/post/edit/1'];
        yield 'trailing base slash' => ['/cms/admin/', 'post', '/cms/admin/post'];
        yield 'empty base path' => ['', '/dashboard', '/dashboard'];
        yield 'root base path' => ['/', '/dashboard', '/dashboard'];
        yield 'base path without leading slash' => ['cms/admin', '/dashboard', '/cms/admin/dashboard'];
        yield 'empty target at root' => ['', '', '/'];
        yield 'empty target below root' => ['/cms/admin', '', '/cms/admin'];
    }

    #[DataProvider('pathProvider')]
    public function testItGeneratesAnAdminPath(
        string $basePath,
        string $path,
        string $expected
    ): void {
        self::assertSame($expected, (new AdminUrlGenerator($basePath))->path($path));
    }

    public function testItExposesTheNormalizedBasePathWithoutRootSlash(): void
    {
        self::assertSame('/cms/admin', (new AdminUrlGenerator('/cms/admin/'))->basePath());
        self::assertSame('', (new AdminUrlGenerator('/'))->basePath());
    }

    public function testItRemovesOnlyAMatchingBasePathBoundary(): void
    {
        $generator = new AdminUrlGenerator('/cms/admin');

        self::assertSame('/post/edit/1', $generator->withoutBasePath('/cms/admin/post/edit/1'));
        self::assertSame('/', $generator->withoutBasePath('/cms/admin'));
        self::assertSame('/cms/administrator', $generator->withoutBasePath('/cms/administrator'));
        self::assertSame('/outside', $generator->withoutBasePath('/outside'));
    }
}
