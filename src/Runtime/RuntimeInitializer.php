<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Runtime;

use Jidaikobo\Kontiki\Utils\Lang;

final class RuntimeInitializer
{
    public function initialize(string $environment, string $projectPath): void
    {
        $GLOBALS['KONTIKI_START_TIME'] = microtime(true);

        require_once dirname(__DIR__) . '/functions/functions.php';

        $_ENV['ENV'] = $environment;
        $_SERVER['ENV'] = $environment;
        $_ENV['PROJECT_PATH'] = $projectPath;
        $_SERVER['PROJECT_PATH'] = $projectPath;

        Lang::setLanguage((string) env('APPLANG', 'en'));
    }
}
