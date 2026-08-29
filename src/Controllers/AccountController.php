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
use Jidaikobo\Kontiki\Services\RoutesService;

class AccountController extends BaseController
{
    use Traits\CreateEditTrait;

    protected string $adminDirName = 'account';
    protected string $label = 'account';
    private Auth $auth;
    private FormService $formService;
    private AccountModel $model;

    protected string $backStringAfterSaveKey = 'x_save_success';
    protected string $backStringAfterSave = ':name Saved successfully.';

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        Auth $auth,
        FormService $formService,
        AccountModel $model
    ) {
        parent::__construct(
            $csrfManager,
            $flashManager,
            $view,
            $routesService
        );
        $this->auth = $auth;
        $this->formService = $formService;
        $this->formService->setModel($model);
        $this->model = $model;
    }

    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->get('/account/settings', AccountController::class . ':handleRenderEditForm');
        $app->post("/account/edit/{id}", AccountController::class . ':handleEdit');

        // redirect
        $app->get('/account/index', AccountController::class . ':accoutEditRedirect');
        $app->get("/account/edit/{id}", AccountController::class . ':accoutEditRedirect');
    }

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

    public function handleRenderEditForm(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $args['id'] = $this->auth->getCurrentUser()['id'] ?? 0;
        if ($args['id'] == 0) {
            die();
        }
        return $this->renderEditForm($request, $response, $args);
    }

    public function handleEdit(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $id = $args['id'];
        return $this->handleSave($request, $response, 'edit', $id);
    }
}
