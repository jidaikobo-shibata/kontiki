<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class CreateEditRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/create", [$controllerClass, 'handleRenderCreateForm'])
            ->setName("{$basePath}|x_create|dashboard,sidebar,createButton");
        $app->post("/{$basePath}/create", [$controllerClass, 'handleCreate']);
        $app->get("/{$basePath}/edit/{id}", [$controllerClass, 'handleRenderEditForm']);
        $app->post("/{$basePath}/edit/{id}", [$controllerClass, 'handleEdit']);
    }
}
