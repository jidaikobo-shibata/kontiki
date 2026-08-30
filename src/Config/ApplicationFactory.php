<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class ApplicationFactory
{
    /** @return App<Container> */
    public function create(): App
    {
        $container = new Container();
        $app = SlimAppFactory::createFromContainer($container);
        $app->setBasePath((string) env('BASEPATH', '/'));

        (new Dependencies($app))->register();

        return $app;
    }
}
