<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Config;

use Slim\Interfaces\RouteInterface;

final class GuestRouteRegistry
{
    /** @var array<string, true> */
    private array $routeIdentifiers = [];

    public function allow(RouteInterface $route): void
    {
        $this->routeIdentifiers[$route->getIdentifier()] = true;
    }

    public function allows(RouteInterface $route): bool
    {
        return isset($this->routeIdentifiers[$route->getIdentifier()]);
    }
}
