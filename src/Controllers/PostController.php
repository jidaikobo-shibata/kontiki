<?php

namespace Jidaikobo\Kontiki\Controllers;

use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\PostModel;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\ConfirmationFormService;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\FormPageService;
use Jidaikobo\Kontiki\Services\ModelValidationService;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\RecordMutationService;
use Jidaikobo\Kontiki\Services\RecordMutationFeedbackService;
use Jidaikobo\Kontiki\Services\SaveRedirectService;
use Jidaikobo\Kontiki\Services\SaveMessageService;
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
    private FormPageService $formPageService;
    private ModelValidationService $modelValidationService;
    private RecordPersistenceService $persistenceService;
    private RecordMutationService $recordMutationService;
    private ConfirmationFormService $confirmationFormService;
    private RecordMutationFeedbackService $recordMutationFeedbackService;
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
        PostModel $model,
        RecordPersistenceService $persistenceService,
        ?CsrfValidationService $csrfValidationService = null,
        ?FormPageService $formPageService = null,
        ?ModelValidationService $modelValidationService = null,
        ?SaveRedirectService $saveRedirectService = null,
        ?SaveMessageService $saveMessageService = null,
        ?RecordMutationService $recordMutationService = null,
        ?ConfirmationFormService $confirmationFormService = null,
        ?RecordMutationFeedbackService $recordMutationFeedbackService = null
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService,
            $csrfValidationService
        );
        $this->formService = $formService;
        $this->formPageService = $formPageService
            ?? new FormPageService($formService);
        $this->modelValidationService = $modelValidationService
            ?? new ModelValidationService($flashManager);
        $this->tableService = $tableService;
        $this->model = $model;
        $this->persistenceService = $persistenceService;
        $this->recordMutationService = $recordMutationService
            ?? new RecordMutationService();
        $this->confirmationFormService = $confirmationFormService
            ?? new ConfirmationFormService($formService);
        $this->recordMutationFeedbackService = $recordMutationFeedbackService
            ?? new RecordMutationFeedbackService($flashManager);
        $this->saveRedirectService = $saveRedirectService
            ?? new SaveRedirectService();
        $this->saveMessageService = $saveMessageService
            ?? new SaveMessageService($flashManager);
    }

    protected function setViewAttributes($routesService): void
    {
        parent::setViewAttributes($routesService);
        $this->view->addAttribute('buttonPosition', 'meta');
    }
}
