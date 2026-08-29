<?php

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Slim\App;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Controllers;

class Routes
{
    public function register(
        App $app,
        Container $_container,
        Auth $auth
    ): void {
        Controllers\AdminController::registerRoutes($app);
        Controllers\AuthController::registerRoutes($app);
        Controllers\DashboardController::registerRoutes($app);
        Controllers\FileController::registerRoutes($app);
        Controllers\PostController::registerRoutes($app, 'post');
        if ($auth->isAdminLoggedIn()) {
            Controllers\UserController::registerRoutes($app, 'user');
        }
        Controllers\AccountController::registerRoutes($app);
        Controllers\HelpController::registerRoutes($app);
//        Controllers\CategoryController::registerRoutes($app, 'post/category');
    }
}
