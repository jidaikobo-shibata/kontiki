<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Database;

use Jidaikobo\Kontiki\Cli\MigrationManager;
use PDO;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;

abstract class DatabaseTestCase extends TestCase
{
    protected string $projectDir;
    protected PDO $pdo;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('PDO_SQLITE is required for database tests.');
        }

        $this->projectDir = sys_get_temp_dir()
            . '/kontiki-database-test-'
            . bin2hex(random_bytes(8));

        $this->createDirectory($this->projectDir . '/config/testing');
        $this->createDirectory($this->projectDir . '/db/testing');
        $this->createDirectory($this->projectDir . '/db/seeds');

        file_put_contents(
            $this->projectDir . '/config/testing/.env',
            "DB_DATABASE=db/testing/database.sqlite3\n"
        );
        touch($this->projectDir . '/db/testing/database.sqlite3');

        $manager = new MigrationManager($this->projectDir);
        $manager->migrate('testing', new NullOutput());

        $this->pdo = new PDO(
            'sqlite:' . $this->databasePath(),
            options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    protected function tearDown(): void
    {
        unset($this->pdo);

        if (!isset($this->projectDir) || !is_dir($this->projectDir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->projectDir,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($this->projectDir);
    }

    protected function databasePath(): string
    {
        return $this->projectDir . '/db/testing/database.sqlite3';
    }

    private function createDirectory(string $path): void
    {
        if (!mkdir($path, 0700, true) && !is_dir($path)) {
            throw new RuntimeException("Could not create test directory: {$path}");
        }
    }
}
