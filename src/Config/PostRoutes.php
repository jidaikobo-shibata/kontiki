<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use DI\Container;
use Jidaikobo\Kontiki\Controllers\PostController;
use Slim\App;

final class PostRoutes
{
    /** @param App<Container> $app */
    public function register(App $app): void
    {
        $app->get('/post/index', [PostController::class, 'indexAll'])
            ->setName('post|x_index|dashboard,sidebar,index');
        $app->get('/post/index/published', [PostController::class, 'indexPublished']);
        $app->get('/post/index/pending', [PostController::class, 'indexPending']);
        $app->get('/post/index/draft', [PostController::class, 'indexDraft']);
        $app->get('/post/index/reserved', [PostController::class, 'indexReserved']);
        $app->get('/post/index/expired', [PostController::class, 'indexExpired']);

        $app->get('/post/create', [PostController::class, 'handleRenderCreateForm'])
            ->setName('post|x_create|dashboard,sidebar,createButton');
        $app->post('/post/create', [PostController::class, 'handleCreate']);
        $app->get('/post/edit/{id}', [PostController::class, 'handleRenderEditForm']);
        $app->post('/post/edit/{id}', [PostController::class, 'handleEdit']);

        $app->get('/post/index/trash', [PostController::class, 'trashIndex']);
        $app->get('/post/trash/{id}', [PostController::class, 'trash']);
        $app->post('/post/trash/{id}', [PostController::class, 'handleTrash']);
        $app->get('/post/restore/{id}', [PostController::class, 'restore']);
        $app->post('/post/restore/{id}', [PostController::class, 'handleRestore']);

        $app->get('/post/delete/{id}', [PostController::class, 'delete']);
        $app->post('/post/delete/{id}', [PostController::class, 'handleDelete']);
        $app->get('/post/preview', [PostController::class, 'handlePreview']);
        $app->get('/post/preview/{id}', [PostController::class, 'handlePreviewById']);
    }
}
