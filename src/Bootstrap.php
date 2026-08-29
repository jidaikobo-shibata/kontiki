<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki;

use DI\Container;
use Slim\App;
use Jidaikobo\Kontiki\Config\ApplicationFactory;
use Jidaikobo\Kontiki\Config\ProjectPathResolver;
use Jidaikobo\Kontiki\Config\EnvironmentLoader;
use Jidaikobo\Kontiki\Config\MiddlewareRegistrar;
use Jidaikobo\Kontiki\Config\RouteRegistrar;
use Jidaikobo\Kontiki\Runtime\PerformanceReporter;
use Jidaikobo\Kontiki\Runtime\RuntimeInitializer;

class Bootstrap
{
    /**
     * @return App<Container>
     */
    public static function init(string $env = 'production', ?string $projectPath = null)
    {
        $projectPath = (new ProjectPathResolver())->resolve($env, $projectPath);
        (new EnvironmentLoader())->load($projectPath, $env);
        (new RuntimeInitializer())->initialize($env, $projectPath);

        $app = (new ApplicationFactory())->create();
        (new MiddlewareRegistrar())->register($app);
        (new RouteRegistrar())->register($app);

        return $app;
    }

    /**
     * @param App<Container> $app
     */
    public static function run(App $app): void
    {
        $app->run();
        self::performance();
    }

    public static function performance(bool $timer = false): void
    {
        (new PerformanceReporter())->report(
            (float) $GLOBALS['KONTIKI_START_TIME'],
            $timer
        );
    }
}
