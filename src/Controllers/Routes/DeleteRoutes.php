<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class DeleteRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/delete/{id}", [$controllerClass, 'delete']);
        $app->post("/{$basePath}/delete/{id}", [$controllerClass, 'handleDelete']);
    }
}
