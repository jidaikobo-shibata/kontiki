<?php

namespace Jidaikobo\Kontiki\Services;

use Slim\Interfaces\RouteCollectorInterface;

class RoutesService
{
    private RouteCollectorInterface $routeCollector;
    private array $routesCache = [];

    public function __construct(
        RouteCollectorInterface $routeCollector
    ) {
        $this->routeCollector = $routeCollector;
        $this->cacheRoutes();
    }

    private function cacheRoutes(): void
    {
        $routes = $this->routeCollector->getRoutes();
        $this->routesCache = [];

        foreach ($routes as $route) {
            $controller = $this->extractControllerFromPattern($route->getPattern());

            $name = $route->getName() ?? '';
            [$routeName, $langStyle, $types] = explode('|', $name) + [null, '', ''];
            $englishStyle = str_replace('x_', ':name ', $langStyle);
            $name = $langStyle ? __($langStyle, $englishStyle, ['name' => __($routeName)]) : null;

            $this->routesCache[$controller][] = [
                'methods' => implode(', ', $route->getMethods()),
                'path' => env('BASEPATH') . $route->getPattern(),
                'name' => $name,
                'type' => explode(',', $types)
            ];
        }
    }

    public function getRoutes(): array
    {
        return $this->routesCache;
    }

    public function getRoutesByController(string $controller): array
    {
        $target = $controller;
        if (strpos($controller, '/') !== false) {
            $controllerSegments = explode('/', $controller);
            $target = reset($controllerSegments);
        }
        return $this->routesCache[$target] ?? [];
    }

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
