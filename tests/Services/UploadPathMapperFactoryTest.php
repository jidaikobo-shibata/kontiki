<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use Jidaikobo\Kontiki\Services\UploadPathMapperFactory;
use PHPUnit\Framework\TestCase;

final class UploadPathMapperFactoryTest extends TestCase
{
    public function testCreatesMapperFromSeparateApplicationSettings(): void
    {
        $mapper = (new UploadPathMapperFactory(
            'https://example.com/cms/',
            '/uploads/',
            '/var/www/site/',
            'public/uploads/'
        ))->create();

        self::assertSame(
            'https://example.com/cms/uploads/image.png',
            $mapper->pathToUrl('/var/www/site/public/uploads/image.png')
        );
        self::assertSame(
            '/var/www/site/public/uploads/image.png',
            $mapper->urlToPath('https://example.com/cms/uploads/image.png')
        );
    }
}
