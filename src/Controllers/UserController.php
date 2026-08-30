<?php

namespace Jidaikobo\Kontiki\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\UserModel;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\FormPageService;
use Jidaikobo\Kontiki\Services\ModelValidationService;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\SaveRedirectService;
use Jidaikobo\Kontiki\Services\SaveMessageService;
use Jidaikobo\Kontiki\Services\TableService;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;

class UserController extends BaseController
{
    use Traits\IndexTrait;
    use Traits\IndexAllTrait;
    use Traits\CreateEditTrait;
    use Traits\DeleteTrait;

    protected string $adminDirName = 'user';
    protected string $label = 'User';

    private UserModel $model;
    private FormService $formService;
    private FormPageService $formPageService;
    private ModelValidationService $modelValidationService;
    private RecordPersistenceService $persistenceService;
    private SaveRedirectService $saveRedirectService;
    private SaveMessageService $saveMessageService;
    private TableService $tableService;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        FormService $formService,
        TableService $tableService,
        UserModel $model,
        RecordPersistenceService $persistenceService,
        ?CsrfValidationService $csrfValidationService = null
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService,
            $csrfValidationService
        );
        $this->formService = $formService;
        $this->formService->setModel($model);
        $this->formPageService = new FormPageService($formService);
        $this->modelValidationService = new ModelValidationService($flashManager);
        $this->tableService = $tableService;
        $this->tableService->setModel($model);
        $this->model = $model;
        $this->persistenceService = $persistenceService;
        $this->saveRedirectService = new SaveRedirectService();
        $this->saveMessageService = new SaveMessageService($flashManager);
    }
}
