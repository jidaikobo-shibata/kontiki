<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class IndexAllRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/index", [$controllerClass, 'indexAll'])
            ->setName("{$basePath}|x_index|dashboard,sidebar,index");
    }
}
