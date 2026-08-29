<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Jidaikobo\Kontiki\Core\Auth;
use RuntimeException;
use Slim\App;

final class RouteRegistrar
{
    /** @param App<Container> $app */
    public function register(App $app): void
    {
        $container = $app->getContainer();
        $auth = $container->get(Auth::class);
        $routes = class_exists('App\Config\Routes')
            ? new \App\Config\Routes()
            : new Routes();

        $register = [$routes, 'register'];
        if (!is_callable($register)) {
            throw new RuntimeException('The routes provider must have a callable register method.');
        }

        $register($app, $container, $auth);
    }
}
