<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Runtime;

use Jidaikobo\Kontiki\Runtime\RuntimeInitializer;
use PHPUnit\Framework\TestCase;

final class RuntimeInitializerTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $previousEnvironment = [];

    protected function setUp(): void
    {
        foreach (['ENV', 'PROJECT_PATH'] as $key) {
            $this->previousEnvironment[$key] = [
                'env' => $_ENV[$key] ?? null,
                'server' => $_SERVER[$key] ?? null,
            ];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $key => $values) {
            foreach (['env' => '_ENV', 'server' => '_SERVER'] as $source => $global) {
                if ($values[$source] === null) {
                    unset($GLOBALS[$global][$key]);
                } else {
                    $GLOBALS[$global][$key] = $values[$source];
                }
            }
        }
        unset($GLOBALS['KONTIKI_START_TIME']);
    }

    public function testInitializesEnvironmentProjectPathAndTimer(): void
    {
        $before = microtime(true);

        (new RuntimeInitializer())->initialize('staging', '/workspace/site');

        self::assertSame('staging', $_ENV['ENV']);
        self::assertSame('/workspace/site', $_ENV['PROJECT_PATH']);
        self::assertIsFloat($GLOBALS['KONTIKI_START_TIME']);
        self::assertGreaterThanOrEqual($before, $GLOBALS['KONTIKI_START_TIME']);
        self::assertTrue(function_exists('env'));
        self::assertTrue(function_exists('setenv'));
    }
}
