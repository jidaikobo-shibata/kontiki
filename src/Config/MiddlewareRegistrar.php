<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Jidaikobo\Kontiki\Middleware\AuthMiddleware;
use Jidaikobo\Kontiki\Middleware\SecurityHeadersMiddleware;
use Slim\App;

final class MiddlewareRegistrar
{
    /** @param App<Container> $app */
    public function register(App $app): void
    {
        $app->add(AuthMiddleware::class);
        $app->add(SecurityHeadersMiddleware::class);
    }
}
