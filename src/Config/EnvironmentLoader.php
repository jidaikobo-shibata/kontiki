<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use Dotenv\Dotenv;

final class EnvironmentLoader
{
    public function load(string $projectPath, string $environment): void
    {
        $environmentDirectory = sprintf(
            '%s/config/%s',
            rtrim($projectPath, DIRECTORY_SEPARATOR),
            $environment
        );

        Dotenv::createImmutable($environmentDirectory)->load();
    }
}
