<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Jidaikobo\Kontiki\Controllers\UserController;
use Slim\App;

final class UserRoutes
{
    /** @param App<Container> $app */
    public function register(App $app): void
    {
        $app->get('/user/index', [UserController::class, 'indexAll'])
            ->setName('user|x_index|dashboard,sidebar,index');

        $app->get('/user/create', [UserController::class, 'handleRenderCreateForm'])
            ->setName('user|x_create|dashboard,sidebar,createButton');
        $app->post('/user/create', [UserController::class, 'handleCreate']);
        $app->get('/user/edit/{id}', [UserController::class, 'handleRenderEditForm']);
        $app->post('/user/edit/{id}', [UserController::class, 'handleEdit']);

        $app->get('/user/delete/{id}', [UserController::class, 'delete']);
        $app->post('/user/delete/{id}', [UserController::class, 'handleDelete']);
    }
}
