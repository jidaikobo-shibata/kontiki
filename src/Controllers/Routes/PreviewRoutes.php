<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class PreviewRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/preview", [$controllerClass, 'handlePreview']);
        $app->get("/{$basePath}/preview/{id}", [$controllerClass, 'handlePreviewById']);
    }
}
