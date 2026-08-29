<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class IndexDraftRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/index/draft", [$controllerClass, 'indexDraft']);
    }
}
