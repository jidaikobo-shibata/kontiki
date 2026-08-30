<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Services;

use InvalidArgumentException;
use Jidaikobo\Kontiki\Services\PreviewRendererFactory;
use PHPUnit\Framework\TestCase;

final class PreviewRendererFactoryTest extends TestCase
{
    public function testPrefersApplicationPreviewDirectory(): void
    {
        $projectPath = sys_get_temp_dir() . '/kontiki-preview-' . bin2hex(random_bytes(6));
        $applicationPath = $projectPath . '/app/views/post';
        mkdir($applicationPath, 0700, true);

        try {
            $renderer = (new PreviewRendererFactory($projectPath))->create('post');

            self::assertSame($applicationPath . '/', $renderer->getTemplatePath());
        } finally {
            rmdir($applicationPath);
            rmdir($projectPath . '/app/views');
            rmdir($projectPath . '/app');
            rmdir($projectPath);
        }
    }

    public function testFallsBackToProjectSourcePreviewDirectory(): void
    {
        $renderer = (new PreviewRendererFactory('/srv/kontiki/'))->create('post');

        self::assertSame(
            '/srv/kontiki/src/views/post/',
            $renderer->getTemplatePath()
        );
    }

    public function testNormalizesAdminDirectorySeparators(): void
    {
        $renderer = (new PreviewRendererFactory('/srv/kontiki'))->create('/post/');

        self::assertSame(
            '/srv/kontiki/src/views/post/',
            $renderer->getTemplatePath()
        );
    }

    public function testRejectsTraversalAdminDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PreviewRendererFactory('/srv/kontiki'))->create('../config');
    }
}
