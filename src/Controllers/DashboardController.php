<?php

namespace Jidaikobo\Kontiki\Controllers;

use Jidaikobo\Kontiki\Services\RoutesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\PhpRenderer;

class DashboardController
{
    private PhpRenderer $view;
    private array $routes;

    public function __construct(
        PhpRenderer $view,
        RoutesService $routesService
    ) {
        $this->view = $view;
        $this->routes = $routesService->getRoutesByType('dashboard');
        $this->setViewAttributes($routesService);
    }

    protected function setViewAttributes($routesService): void
    {
        $this->view->setAttributes([
                'lang' => env('APPLANG', 'en'),
                'sidebarItems' => $routesService->getRoutesByType('sidebar')
            ]);
    }

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
            ]
        );
    }
}
