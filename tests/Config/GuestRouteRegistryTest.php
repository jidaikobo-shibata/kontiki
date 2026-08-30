<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use Jidaikobo\Kontiki\Config\GuestRouteRegistry;
use PHPUnit\Framework\TestCase;
use Slim\Interfaces\RouteInterface;

final class GuestRouteRegistryTest extends TestCase
{
    public function testAllowsOnlyTheRegisteredRouteIdentifier(): void
    {
        $allowed = $this->createMock(RouteInterface::class);
        $allowed->method('getIdentifier')->willReturn('route-allowed');
        $sameIdentifier = $this->createMock(RouteInterface::class);
        $sameIdentifier->method('getIdentifier')->willReturn('route-allowed');
        $different = $this->createMock(RouteInterface::class);
        $different->method('getIdentifier')->willReturn('route-protected');
        $registry = new GuestRouteRegistry();

        $registry->allow($allowed);

        self::assertTrue($registry->allows($sameIdentifier));
        self::assertFalse($registry->allows($different));
    }
}
