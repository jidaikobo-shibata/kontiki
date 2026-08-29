<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use DI\Container;
use Jidaikobo\Kontiki\Config\ApplicationFactory;
use Jidaikobo\Kontiki\Core\Database;
use PHPUnit\Framework\TestCase;
use Slim\App;

final class ApplicationFactoryTest extends TestCase
{
    private mixed $previousBasePath;

    protected function setUp(): void
    {
        $this->previousBasePath = $_ENV['BASEPATH'] ?? null;
        $_ENV['BASEPATH'] = '/cms/admin';
        $_SERVER['BASEPATH'] = '/cms/admin';
    }

    protected function tearDown(): void
    {
        if ($this->previousBasePath === null) {
            unset($_ENV['BASEPATH'], $_SERVER['BASEPATH']);
            return;
        }

        $_ENV['BASEPATH'] = $this->previousBasePath;
        $_SERVER['BASEPATH'] = $this->previousBasePath;
    }

    public function testCreatesConfiguredSlimApplicationAndRegistersDependencies(): void
    {
        $app = (new ApplicationFactory())->create();

        self::assertInstanceOf(App::class, $app);
        self::assertSame('/cms/admin', $app->getBasePath());
        self::assertInstanceOf(Container::class, $app->getContainer());
        self::assertTrue($app->getContainer()->has(Database::class));
    }
}
