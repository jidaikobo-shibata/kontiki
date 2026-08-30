<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use DI\Container;
use Jidaikobo\Kontiki\Config\ApplicationFactory;
use Jidaikobo\Kontiki\Core\Database;
use Jidaikobo\Kontiki\Services\FileLifecycleService;
use Jidaikobo\Kontiki\Services\CsrfValidationService;
use Jidaikobo\Kontiki\Services\UploadPathMapper;
use Jidaikobo\Kontiki\Services\UploadedFileAdapter;
use PHPUnit\Framework\TestCase;
use Slim\App;

final class ApplicationFactoryTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $previousEnvironment = [];

    protected function setUp(): void
    {
        $values = [
            'BASEPATH' => '/cms/admin',
            'BASEURL' => 'http://localhost',
            'BASEURL_UPLOAD_DIR' => '/uploads',
            'PROJECT_PATH' => '/tmp/kontiki-application-factory-test',
            'UPLOADDIR' => '/uploads',
        ];
        foreach ($values as $key => $value) {
            $this->previousEnvironment[$key] = $_ENV[$key] ?? null;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    public function testCreatesConfiguredSlimApplicationAndRegistersDependencies(): void
    {
        $app = (new ApplicationFactory())->create();

        self::assertInstanceOf(App::class, $app);
        self::assertSame('/cms/admin', $app->getBasePath());
        self::assertInstanceOf(Container::class, $app->getContainer());
        self::assertTrue($app->getContainer()->has(Database::class));
        self::assertInstanceOf(
            UploadPathMapper::class,
            $app->getContainer()->get(UploadPathMapper::class)
        );
        self::assertInstanceOf(
            UploadedFileAdapter::class,
            $app->getContainer()->get(UploadedFileAdapter::class)
        );
        self::assertInstanceOf(
            FileLifecycleService::class,
            $app->getContainer()->get(FileLifecycleService::class)
        );
        self::assertInstanceOf(
            CsrfValidationService::class,
            $app->getContainer()->get(CsrfValidationService::class)
        );
    }
}
