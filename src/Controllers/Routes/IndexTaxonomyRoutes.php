<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class IndexTaxonomyRoutes
{
    public static function register(
        RouteCollectorProxy $app,
        string $basePath,
        string $controllerClass
    ): void {
        $app->get("/{$basePath}/index/taxonomy", [$controllerClass, 'indexTaxonomy'])
            ->setName("{$basePath}|x_index_taxonomy|sidebar,dashboard");
    }
}
