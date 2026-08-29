<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Runtime;

use Jidaikobo\Kontiki\Utils\Lang;

final class RuntimeInitializer
{
    public function initialize(string $environment, string $projectPath): void
    {
        $GLOBALS['KONTIKI_START_TIME'] = microtime(true);

        require dirname(__DIR__) . '/functions/functions.php';

        setenv('ENV', $environment);
        setenv('PROJECT_PATH', $projectPath);

        Lang::setLanguage((string) env('APPLANG', 'en'));
    }
}
