<?php

namespace Jidaikobo\Kontiki\Services;

use Jidaikobo\Kontiki\Core\Auth;
use Slim\Interfaces\RouteCollectorInterface;

class RoutesService
{
    private RouteCollectorInterface $routeCollector;
    private AdminUrlGenerator $adminUrlGenerator;
    /**
     * @var array<string, array<int, array{
     *     methods: string,
     *     path: string,
     *     name: string|null,
     *     type: array<int, string>
     * }>>
     */
    private array $routesCache = [];

    public function __construct(
        RouteCollectorInterface $routeCollector,
        ?AdminUrlGenerator $adminUrlGenerator = null,
        private ?Auth $auth = null
    ) {
        $this->routeCollector = $routeCollector;
        $this->adminUrlGenerator = $adminUrlGenerator
            ?? new AdminUrlGenerator(env('BASEPATH', ''));
        $this->cacheRoutes();
    }

    private function cacheRoutes(): void
    {
        $routes = $this->routeCollector->getRoutes();
        $this->routesCache = [];

        foreach ($routes as $route) {
            $controller = $this->extractControllerFromPattern($route->getPattern());

            $name = $route->getName() ?? '';
            [$routeName, $langStyle, $types, $requiredRole] = explode('|', $name)
                + [null, '', '', ''];
            if (!$this->isVisibleForCurrentUser($requiredRole)) {
                continue;
            }
            $englishStyle = str_replace('x_', ':name ', $langStyle);
            $name = $langStyle ? __($langStyle, $englishStyle, ['name' => __($routeName)]) : null;

            $this->routesCache[$controller][] = [
                'methods' => implode(', ', $route->getMethods()),
                'path' => $this->adminUrlGenerator->path($route->getPattern()),
                'name' => $name,
                'type' => explode(',', $types)
            ];
        }
    }

    private function isVisibleForCurrentUser(string $requiredRole): bool
    {
        if ($requiredRole === '' || $this->auth === null) {
            return true;
        }

        return ($this->auth->getCurrentUser()['role'] ?? null) === $requiredRole;
    }

    /**
     * @return array<string, array<int, array{
     *     methods: string,
     *     path: string,
     *     name: string|null,
     *     type: array<int, string>
     * }>>
     */
    public function getRoutes(): array
    {
        return $this->routesCache;
    }

    public function getAdminUrlGenerator(): AdminUrlGenerator
    {
        return $this->adminUrlGenerator;
    }

    /**
     * @return array<int, array{
     *     methods: string,
     *     path: string,
     *     name: string|null,
     *     type: array<int, string>
     * }>
     */
    public function getRoutesByController(string $controller): array
    {
        $target = $controller;
        if (strpos($controller, '/') !== false) {
            $controllerSegments = explode('/', $controller);
            $target = reset($controllerSegments);
        }
        return $this->routesCache[$target] ?? [];
    }

    /**
     * @return array<string, array<int, array{
     *     methods: string,
     *     path: string,
     *     name: string|null,
     *     type: array<int, string>
     * }>>
     */
    public function getRoutesByType(string $type): array
    {
        $filtered = array_filter(array_map(function ($routes) use ($type) {
            return array_filter($routes, function ($route) use ($type) {
                return in_array($type, $route['type'], true);
            });
        }, $this->routesCache));
        return $filtered;
    }

    /**
     * Extract the controller name from the route pattern.
     *
     * @param  string $pattern The route pattern (e.g., "/users").
     * @return string The extracted controller name (e.g., "users").
     */
    private function extractControllerFromPattern(string $pattern): string
    {
        $segments = explode('/', trim($pattern, '/'));
        return $segments[0];
    }
}
