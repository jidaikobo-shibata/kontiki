<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use Closure;
use Composer\InstalledVersions;

final class ProjectPathResolver
{
    /** @var Closure(): array<string, mixed> */
    private Closure $rootPackageProvider;

    /**
     * @param null|callable(): array<string, mixed> $rootPackageProvider
     */
    public function __construct(
        ?callable $rootPackageProvider = null,
        private ?string $packageRoot = null
    ) {
        $this->rootPackageProvider = Closure::fromCallable(
            $rootPackageProvider ?? static fn(): array => InstalledVersions::getRootPackage()
        );
        $this->packageRoot ??= dirname(__DIR__, 2);
    }

    public function resolve(string $environment, ?string $explicitPath = null): string
    {
        if (is_string($explicitPath) && $explicitPath !== '') {
            return rtrim($explicitPath, DIRECTORY_SEPARATOR);
        }

        $rootPackage = ($this->rootPackageProvider)();
        $installPath = $rootPackage['install_path'] ?? null;
        if (is_string($installPath) && $installPath !== '') {
            return rtrim($installPath, DIRECTORY_SEPARATOR);
        }

        if ($environment === 'development') {
            return $this->packageRoot;
        }

        return dirname($this->packageRoot, 3);
    }
}
