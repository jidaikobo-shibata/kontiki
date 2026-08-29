<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli;

use Dotenv\Dotenv;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrationManager
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $realProjectDir = realpath($projectDir);
        if ($realProjectDir === false || !is_dir($realProjectDir)) {
            throw new RuntimeException("Project directory does not exist: {$projectDir}");
        }

        $this->projectDir = $realProjectDir;
    }

    public function migrate(string $environment, OutputInterface $output): void
    {
        $this->manager($environment, $output)->migrate($environment);
    }

    /** @return array{hasMissingMigration: bool, hasDownMigration: bool} */
    public function status(string $environment, OutputInterface $output): array
    {
        $status = $this->manager($environment, $output)->printStatus($environment);

        return [
            'hasMissingMigration' => (bool) ($status['hasMissingMigration'] ?? false),
            'hasDownMigration' => (bool) ($status['hasDownMigration'] ?? false),
        ];
    }

    /** @return array{project_dir: string, database: string, environment: string} */
    public function describe(string $environment): array
    {
        return [
            'project_dir' => $this->projectDir,
            'database' => $this->databasePath($environment),
            'environment' => $environment,
        ];
    }

    private function manager(string $environment, OutputInterface $output): Manager
    {
        $databasePath = $this->databasePath($environment);
        $packageDir = dirname(__DIR__, 2);
        $config = new Config([
            'paths' => [
                'migrations' => $packageDir . '/db/migrations',
                'seeds' => $this->projectDir . '/db/seeds',
            ],
            'environments' => [
                'default_migration_table' => 'phinxlog',
                'default_environment' => $environment,
                $environment => [
                    'adapter' => 'sqlite',
                    'name' => $databasePath,
                    'suffix' => '',
                ],
            ],
            'version_order' => 'creation',
        ]);

        return new Manager($config, new ArrayInput([]), $output);
    }

    private function databasePath(string $environment): string
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_-]{0,63}\z/D', $environment) !== 1) {
            throw new RuntimeException('Environment contains invalid characters.');
        }

        $envFile = $this->projectDir . "/config/{$environment}/.env";
        if (!is_file($envFile) || !is_readable($envFile)) {
            throw new RuntimeException("Environment file is not readable: {$envFile}");
        }
        $contents = file_get_contents($envFile);
        if ($contents === false) {
            throw new RuntimeException("Could not read environment file: {$envFile}");
        }

        $values = Dotenv::parse($contents);
        $database = $values['DB_DATABASE'] ?? null;
        if (!is_string($database) || trim($database) === '') {
            throw new RuntimeException('DB_DATABASE is missing from the environment file.');
        }

        $configuredPath = trim($database);
        $candidate = str_starts_with($configuredPath, DIRECTORY_SEPARATOR)
            ? $configuredPath
            : $this->projectDir . '/' . $configuredPath;
        $realDatabase = realpath($candidate);
        if ($realDatabase === false || !is_file($realDatabase)) {
            throw new RuntimeException("Database file does not exist: {$candidate}");
        }

        return $realDatabase;
    }
}
