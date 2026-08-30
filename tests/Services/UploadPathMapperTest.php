<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\UploadPathMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UploadPathMapperTest extends TestCase
{
    private UploadPathMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new UploadPathMapper(
            'https://example.com/uploads',
            '/var/www/site/uploads'
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function pathToUrlProvider(): iterable
    {
        yield 'base directory' => [
            '/var/www/site/uploads',
            'https://example.com/uploads',
        ];
        yield 'nested file' => [
            '/var/www/site/uploads/2026/image.png',
            'https://example.com/uploads/2026/image.png',
        ];
        yield 'backslash path' => [
            '\\var\\www\\site\\uploads\\2026\\image.png',
            'https://example.com/uploads/2026/image.png',
        ];
        yield 'existing safe URL' => [
            'https://example.com/uploads/2026/image.png?download=1#preview',
            'https://example.com/uploads/2026/image.png',
        ];
    }

    #[DataProvider('pathToUrlProvider')]
    public function testMapsSafePathsToUrls(string $path, string $expected): void
    {
        self::assertSame($expected, $this->mapper->pathToUrl($path));
    }

    /** @return iterable<string, array{string}> */
    public static function unsafePathProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'relative path' => ['2026/image.png'];
        yield 'prefix collision' => ['/var/www/site/uploads-other/image.png'];
        yield 'traversal' => ['/var/www/site/uploads/../secret.txt'];
        yield 'different host URL' => ['https://evil.example/uploads/image.png'];
        yield 'URL prefix collision' => ['https://example.com/uploads-other/image.png'];
    }

    #[DataProvider('unsafePathProvider')]
    public function testRejectsUnsafePaths(string $path): void
    {
        self::assertSame('', $this->mapper->pathToUrl($path));
    }

    public function testMapsSafeUrlAndDropsQueryAndFragment(): void
    {
        self::assertSame(
            '/var/www/site/uploads/2026/image.png',
            $this->mapper->urlToPath(
                'https://example.com/uploads/2026/image.png?size=large#preview'
            )
        );
    }

    public function testSupportsUploadUrlAtHostRoot(): void
    {
        $mapper = new UploadPathMapper(
            'https://static.example.com',
            '/var/www/uploads'
        );

        self::assertSame(
            '/var/www/uploads/image.png',
            $mapper->urlToPath('https://static.example.com/image.png')
        );
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeUrlProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'different scheme' => ['http://example.com/uploads/image.png'];
        yield 'different host' => ['https://evil.example/uploads/image.png'];
        yield 'explicit credentials' => ['https://user@example.com/uploads/image.png'];
        yield 'prefix collision' => ['https://example.com/uploads-other/image.png'];
        yield 'plain traversal' => ['https://example.com/uploads/../secret.txt'];
        yield 'encoded traversal' => ['https://example.com/uploads/%2e%2e/secret.txt'];
        yield 'encoded slash traversal' => ['https://example.com/uploads/%2e%2e%2fsecret.txt'];
        yield 'outside filesystem path' => ['/var/www/site/secret.txt'];
    }

    #[DataProvider('unsafeUrlProvider')]
    public function testRejectsUnsafeUrls(string $url): void
    {
        self::assertSame('', $this->mapper->urlToPath($url));
    }
}
