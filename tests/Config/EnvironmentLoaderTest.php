<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use Dotenv\Exception\InvalidPathException;
use Jidaikobo\Kontiki\Config\EnvironmentLoader;
use PHPUnit\Framework\TestCase;

final class EnvironmentLoaderTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/kontiki-env-' . bin2hex(random_bytes(8));
        mkdir($this->projectPath . '/config/staging', 0700, true);
    }

    protected function tearDown(): void
    {
        unset($_ENV['KONTIKI_ENV_LOADER_TEST'], $_SERVER['KONTIKI_ENV_LOADER_TEST']);
        $envFile = $this->projectPath . '/config/staging/.env';
        if (is_file($envFile)) {
            unlink($envFile);
        }
        rmdir($this->projectPath . '/config/staging');
        rmdir($this->projectPath . '/config');
        rmdir($this->projectPath);
    }

    public function testLoadsRequestedEnvironmentFile(): void
    {
        file_put_contents(
            $this->projectPath . '/config/staging/.env',
            "KONTIKI_ENV_LOADER_TEST=loaded\n"
        );

        (new EnvironmentLoader())->load($this->projectPath . '/', 'staging');

        self::assertSame('loaded', $_ENV['KONTIKI_ENV_LOADER_TEST']);
        self::assertSame('loaded', $_SERVER['KONTIKI_ENV_LOADER_TEST']);
    }

    public function testMissingEnvironmentFilePreservesDotenvException(): void
    {
        $this->expectException(InvalidPathException::class);

        (new EnvironmentLoader())->load($this->projectPath, 'production');
    }
}
