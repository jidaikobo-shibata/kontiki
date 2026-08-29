<?php

namespace Jidaikobo\Kontiki;

use Slim\App;
use Jidaikobo\Kontiki\Config\ApplicationFactory;
use Jidaikobo\Kontiki\Config\ProjectPathResolver;
use Jidaikobo\Kontiki\Config\EnvironmentLoader;
use Jidaikobo\Kontiki\Config\MiddlewareRegistrar;
use Jidaikobo\Kontiki\Config\RouteRegistrar;

class Bootstrap
{
    public static function init(string $env = 'production', ?string $projectPath = null)
    {
        // check response performance
        $GLOBALS['KONTIKI_START_TIME'] = microtime(true);

        // load config
        $projectPath = (new ProjectPathResolver())->resolve($env, $projectPath);
        (new EnvironmentLoader())->load($projectPath, $env);

        // Load Functions
        require __DIR__ . '/functions/functions.php';

        // setenv
        setenv('ENV', $env);
        setenv('PROJECT_PATH', $projectPath);

        // Load default language on class load
        $language = env('APPLANG', 'en');
        Utils\Lang::setLanguage($language);

        $app = (new ApplicationFactory())->create();
        (new MiddlewareRegistrar())->register($app);
        (new RouteRegistrar())->register($app);

        return $app;
    }

    public static function run(App $app): void
    {
        $app->run();
        self::performance();
    }

    public static function performance($timer = false): void
    {
        // $timer = true;
        if ($timer) {
            $elapsedTime = microtime(true) - $GLOBALS['KONTIKI_START_TIME'];
            jlog("Total execution time: " . number_format($elapsedTime, 6) . " seconds");
        }
    }
}
