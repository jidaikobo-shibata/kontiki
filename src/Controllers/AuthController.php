<?php

namespace Jidaikobo\Kontiki\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\PhpRenderer;
use Jidaikobo\Kontiki\Core\Auth;
use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Managers\FlashManager;
use Jidaikobo\Kontiki\Models\UserModel;
use Jidaikobo\Kontiki\Services\FormService;
use Jidaikobo\Kontiki\Services\RateLimitService;
use Jidaikobo\Kontiki\Services\RoutesService;

class AuthController extends BaseController
{
    private Auth $auth;
    private FormService $formService;
    private RateLimitService $rateLimitService;
    private UserModel $model;

    public function __construct(
        CsrfManager $csrfManager,
        FlashManager $flashManager,
        PhpRenderer $view,
        RoutesService $routesService,
        Auth $auth,
        FormService $formService,
        RateLimitService $rateLimitService,
        UserModel $model
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
        $this->rateLimitService = $rateLimitService;
        $this->model = $model;
    }

    public static function registerRoutes(App $app, string $basePath = ''): void
    {
        $app->get('/login', AuthController::class . ':showLoginForm')->setName('login');
        $app->post('/login', AuthController::class . ':processLogin');
        $app->get('/logout', AuthController::class . ':logout');
    }

    public function showLoginForm(Request $request, Response $response): Response
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        if ($this->rateLimitService->isIpBlocked($ip)) {
            http_response_code(403);
            die("Access denied due to too many failed login attempts.");
        }

        if ($this->auth->isLoggedIn()) {
            return $this->redirectResponse($request, $response, 'dashboard');
        }

        $redirectUrl = $request->getQueryParams()['redirect'] ?? '';
        $data = $this->flashManager->getData(
            'data',
            ['username' => '', 'redirectUrl' => $redirectUrl]
        );

        $content = $this->view->fetch('auth/login.php', $data);
        $content = $this->formService->addMessages(
            $content,
            $this->flashManager->getData('errors', [])
        );

        return $this->renderResponse(
            $response,
            __('login', 'Login'),
            $content,
            'layout-simple.php'
        );
    }

    public function processLogin(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $redirectRaw = $data['redirectUrl'] ?? '';
        $redirectUrl = trim($redirectRaw) !== '' ? $redirectRaw : 'dashboard';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Login successful
        if ($this->auth->login($username, $password)) {
            $this->rateLimitService->resetRateLimit($ip);
            $this->rateLimitService->cleanOldRateLimitData();
            return $this->redirectResponse($request, $response, $redirectUrl);
        }

        // Login Failed
        $this->rateLimitService->recordFailedLogin($ip);
        $this->flashManager->addErrors([
            ['messages' => [__('wrong_username_or_password', 'Incorrect username or password')]],
        ]);
        $this->flashManager->setData(
            'data',
            ['username' => $username, 'redirectUrl' => $redirectUrl]
        ); // not keep password
        return $this->redirectResponse($request, $response, 'login');
    }

    public function logout(Request $request, Response $response): Response
    {
        $this->auth->logout();
        return $this->redirectResponse($request, $response, 'login');
    }
}
