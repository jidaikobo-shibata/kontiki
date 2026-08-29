<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use Jidaikobo\Kontiki\Config\ProjectPathResolver;
use PHPUnit\Framework\TestCase;

final class ProjectPathResolverTest extends TestCase
{
    public function testExplicitPathTakesPriorityAndRemovesTrailingSeparator(): void
    {
        $resolver = new ProjectPathResolver(
            static fn(): array => ['install_path' => '/composer/root'],
            '/packages/kontiki'
        );

        self::assertSame('/sites/example', $resolver->resolve('production', '/sites/example/'));
    }

    public function testComposerRootInstallPathIsUsedWhenAvailable(): void
    {
        $resolver = new ProjectPathResolver(
            static fn(): array => ['install_path' => '/workspace/site/'],
            '/packages/kontiki'
        );

        self::assertSame('/workspace/site', $resolver->resolve('production'));
    }

    public function testDevelopmentFallsBackToPackageRoot(): void
    {
        $resolver = new ProjectPathResolver(static fn(): array => [], '/packages/kontiki');

        self::assertSame('/packages/kontiki', $resolver->resolve('development'));
    }

    public function testProductionFallbackPreservesLegacyVendorLayoutRule(): void
    {
        $resolver = new ProjectPathResolver(
            static fn(): array => [],
            '/workspace/site/vendor/jidaikobo/kontiki'
        );

        self::assertSame('/workspace/site', $resolver->resolve('production'));
    }
}
