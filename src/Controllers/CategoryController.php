<?php

namespace Jidaikobo\Kontiki\Controllers;

use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\CategoryModel;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\TableService;

class CategoryController extends BaseController
{
    // use Traits\IndexTrait;
    // use Traits\IndexTaxonomyTrait;
    // use Traits\CreateEditTrait;
    // use Traits\DeleteTrait;

    protected string $adminDirName = 'post/category';
    protected string $label = 'Post/Category';
    protected CategoryModel $model;

    private FormService $formService;
    private TableService $tableService;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        FormService $formService,
        TableService $tableService,
        CategoryModel $model
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
}
