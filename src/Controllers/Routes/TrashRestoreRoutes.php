<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class TrashRestoreRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/index/trash", [$controllerClass, 'trashIndex']);
        $app->get("/{$basePath}/trash/{id}", [$controllerClass, 'trash']);
        $app->post("/{$basePath}/trash/{id}", [$controllerClass, 'handleTrash']);
        $app->get("/{$basePath}/restore/{id}", [$controllerClass, 'restore']);
        $app->post("/{$basePath}/restore/{id}", [$controllerClass, 'handleRestore']);
    }
}
