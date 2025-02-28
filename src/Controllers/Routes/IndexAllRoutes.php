<?php

namespace Jidaikobo\Kontiki\Controllers\Routes;

use Slim\Routing\RouteCollectorProxy;

class IndexAllRoutes
{
    public static function register(RouteCollectorProxy $group, string $basePath, string $controllerClass): void
    {
        $group->get('/index', [$controllerClass, 'indexAll'])
            ->setName("{$basePath}|x_index|dashboard,sidebar,index");
    }
}
