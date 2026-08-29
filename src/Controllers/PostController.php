<?php

namespace Jidaikobo\Kontiki\Controllers;

use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\TableService;

class PostController extends BaseController
{
    use Traits\IndexTrait;
    use Traits\IndexAllTrait;
    use Traits\IndexPublishedTrait;
    use Traits\IndexPendingTrait;
    use Traits\IndexDraftTrait;
    use Traits\IndexReservedTrait;
    use Traits\IndexExpiredTrait;
    use Traits\CreateEditTrait;
    use Traits\TrashRestoreTrait;
    use Traits\DeleteTrait;
    use Traits\PreviewTrait;

    protected string $adminDirName = 'post';
    protected string $label = 'Post';

    private PostModel $model;
    private FormService $formService;
    private TableService $tableService;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        FormService $formService,
        TableService $tableService,
        PostModel $model
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService
        );
        $this->formService = $formService;
        $this->formService->setModel($model);
        $this->tableService = $tableService;
        $this->tableService->setModel($model);
        $this->model = $model;
    }

    protected function setViewAttributes($routesService): void
    {
        parent::setViewAttributes($routesService);
        $this->view->addAttribute('buttonPosition', 'meta');
    }
}
