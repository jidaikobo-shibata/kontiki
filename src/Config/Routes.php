<?php

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Slim\App;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Controllers;

class Routes
{
    /** @param App<Container> $app */
    public function register(
        App $app,
        Container $_container,
        Auth $auth
    ): void {
        Controllers\AdminController::registerRoutes($app);
        Controllers\AuthController::registerRoutes($app);
        Controllers\DashboardController::registerRoutes($app);
        Controllers\FileController::registerRoutes($app);
        (new PostRoutes())->register($app);
        if ($auth->isAdminLoggedIn()) {
            (new UserRoutes())->register($app);
        }
        Controllers\AccountController::registerRoutes($app);
        Controllers\HelpController::registerRoutes($app);
//        Controllers\CategoryController::registerRoutes($app, 'post/category');
    }
}
