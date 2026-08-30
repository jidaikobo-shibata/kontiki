<?php

namespace Jidaikobo\Kontiki\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\AccountModel;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\FormPageService;
use Jidaikobo\Kontiki\Services\ModelValidationService;
use Jidaikobo\Kontiki\Services\RecordPersistenceService;
use Jidaikobo\Kontiki\Services\SaveRedirectService;
use Jidaikobo\Kontiki\Services\SaveMessageService;
use Jidaikobo\Kontiki\Services\RoutesService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;

class AccountController extends BaseController
{
    use Traits\CreateEditTrait;

    protected string $adminDirName = 'account';
    protected string $label = 'account';
    private Auth $auth;
    private FormPageService $formPageService;
    private ModelValidationService $modelValidationService;
    private RecordPersistenceService $persistenceService;
    private SaveRedirectService $saveRedirectService;
    private SaveMessageService $saveMessageService;
    private AccountModel $model;

    protected ?string $backStringAfterSaveKey = 'x_save_success';
    protected ?string $backStringAfterSave = ':name Saved successfully.';

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        Auth $auth,
        FormService $formService,
        AccountModel $model,
        RecordPersistenceService $persistenceService,
        ?CsrfValidationService $csrfValidationService = null,
        ?FormPageService $formPageService = null,
        ?ModelValidationService $modelValidationService = null,
        ?SaveRedirectService $saveRedirectService = null,
        ?SaveMessageService $saveMessageService = null
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService,
            $csrfValidationService
        );
        $this->auth = $auth;
        $this->formPageService = $formPageService
            ?? new FormPageService($formService);
        $this->modelValidationService = $modelValidationService
            ?? new ModelValidationService($flashManager);
        $this->model = $model;
        $this->persistenceService = $persistenceService;
        $this->saveRedirectService = $saveRedirectService
            ?? new SaveRedirectService();
        $this->saveMessageService = $saveMessageService
            ?? new SaveMessageService($flashManager);
    }

    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->get('/account/settings', AccountController::class . ':handleRenderEditForm');
        $app->post("/account/edit/{id}", AccountController::class . ':handleEdit');

        // redirect
        $app->get('/account/index', AccountController::class . ':accoutEditRedirect');
        $app->get("/account/edit/{id}", AccountController::class . ':accoutEditRedirect');
    }

    /** @param array<string, mixed> $args */
    public function accoutEditRedirect(
        Request $request,
        Response $response,
        array $args
    ): Response {
        return $this->redirectResponse(
            $request,
            $response,
            "/account/settings"
        );
    }

    /** @param array<string, mixed> $args */
    public function handleRenderEditForm(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $currentUserId = $this->currentUserId();
        if ($currentUserId === null) {
            return $response->withStatus(403);
        }
        $args['id'] = $currentUserId;

        return $this->renderEditForm($request, $response, $args);
    }

    /** @param array<string, mixed> $_args */
    public function handleEdit(
        Request $request,
        Response $response,
        array $_args
    ): Response {
        $currentUserId = $this->currentUserId();
        if ($currentUserId === null) {
            return $response->withStatus(403);
        }

        return $this->handleSave($request, $response, 'edit', $currentUserId);
    }

    private function currentUserId(): ?int
    {
        $id = $this->auth->getCurrentUser()['id'] ?? null;
        if (is_int($id) && $id > 0) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
            return (int) $id;
        }

        return null;
    }
}
