<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Jidaikobo\Kontiki\Controllers\UserController;
use Jidaikobo\Kontiki\Middleware\AdminAuthorizationMiddleware;
use Slim\App;
use Slim\Interfaces\RouteInterface;

final class UserRoutes
{
    /** @param App<Container> $app */
    public function register(App $app): void
    {
        $this->adminOnly(
            $app->get('/user/index', [UserController::class, 'indexAll'])
                ->setName('user|x_index|dashboard,sidebar,index|admin')
        );

        $this->adminOnly(
            $app->get('/user/create', [UserController::class, 'handleRenderCreateForm'])
                ->setName('user|x_create|dashboard,sidebar,createButton|admin')
        );
        $this->adminOnly($app->post('/user/create', [UserController::class, 'handleCreate']));
        $this->adminOnly($app->get('/user/edit/{id}', [UserController::class, 'handleRenderEditForm']));
        $this->adminOnly($app->post('/user/edit/{id}', [UserController::class, 'handleEdit']));

        $this->adminOnly($app->get('/user/delete/{id}', [UserController::class, 'delete']));
        $this->adminOnly($app->post('/user/delete/{id}', [UserController::class, 'handleDelete']));
    }

    private function adminOnly(RouteInterface $route): void
    {
        $route->add(AdminAuthorizationMiddleware::class);
    }
}
