<?php

namespace Jidaikobo\Kontiki\Controllers;

use Jidaikobo\Kontiki\Managers\CsrfManager;
use Jidaikobo\Kontiki\Services\RoutesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

class DashboardController
{
    private PhpRenderer $view;
    /** @var array<mixed> */
    private array $routes;

    public function __construct(
        PhpRenderer $view,
        RoutesService $routesService,
        private CsrfManager $csrfManager
    ) {
        $this->view = $view;
        $this->routes = $routesService->getRoutesByType('dashboard');
        $this->setViewAttributes($routesService);
    }

    protected function setViewAttributes(RoutesService $routesService): void
    {
        $viewAttributes = $this->view->getAttributes();
        $this->view->setAttributes([
                'lang' => $viewAttributes['lang'] ?? env('APPLANG', 'en'),
                'basePath' => $routesService->getAdminUrlGenerator()->basePath(),
                'faviconPath' => $viewAttributes['faviconPath']
                    ?? env('ADMIN_FAVICON_PATH', ''),
                'copyright' => $viewAttributes['copyright'] ?? env('COPYRIGHT', ''),
                'homeUrl' => $viewAttributes['homeUrl'] ?? env('BASEURL', '#'),
                'sidebarItems' => $routesService->getRoutesByType('sidebar')
            ]);
    }

    /** @param App<\DI\Container> $app */
    public static function registerRoutes(App $app): void
    {
        $app->get('/dashboard', DashboardController::class . ':dashboard')
            ->setName('dashboard');
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $content = $this->view->fetch(
            'dashboard/dashboard.php',
            ['dashboardItems' => $this->routes]
        );
        return $this->view->render(
            $response,
            'layout.php',
            [
                'pageTitle' => __('management_portal', 'Management Portal'),
                'content' => $content,
                'csrfToken' => $this->csrfManager->getToken(),
            ]
        );
    }
}
