<?php

namespace Jidaikobo\Kontiki;

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\App;
use Jidaikobo\Kontiki\Middleware\AuthMiddleware;
use Jidaikobo\Kontiki\Middleware\SecurityHeadersMiddleware;
use Jidaikobo\Kontiki\Config\ProjectPathResolver;

class Bootstrap
{
    public static function init(string $env = 'production', ?string $projectPath = null)
    {
        // check response performance
        $GLOBALS['KONTIKI_START_TIME'] = microtime(true);

        // load config
        $projectPath = (new ProjectPathResolver())->resolve($env, $projectPath);
        $dotenv = Dotenv::createImmutable($projectPath . "/config/{$env}/");
        $dotenv->load();

        // Load Functions
        require __DIR__ . '/functions/functions.php';

        // setenv
        setenv('ENV', $env);
        setenv('PROJECT_PATH', $projectPath);

        // Load default language on class load
        $language = env('APPLANG', 'en');
        Utils\Lang::setLanguage($language);

        // Configure a PHP-DI container
        $container = new Container();
        AppFactory::setContainer($container);

        // Create a Slim application
        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);
        $basePath = env('BASEPATH', '/');
        $app->setBasePath($basePath);

        // Add auth check
        $app->add(AuthMiddleware::class);

        // Add headers for security measures
        $app->add(SecurityHeadersMiddleware::class);

        // Set dependencies
        $dependencies = new Config\Dependencies($app);
        $dependencies->register();

        // Set Route
        $auth = $app->getContainer()->get(Core\Auth::class);
        $routesClass = class_exists('App\Config\Routes')
            ? new \App\Config\Routes()
            : new \Jidaikobo\Kontiki\Config\Routes();
        $routesClass->register($app, $container, $auth);

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
