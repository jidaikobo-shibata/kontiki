<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class IndexExpiredRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/index/expired", [$controllerClass, 'indexExpired']);
    }
}
