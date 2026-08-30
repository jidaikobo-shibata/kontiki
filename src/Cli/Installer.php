<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Cli;

use PDO;
use Phinx\Console\PhinxApplication;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class Installer
{
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * @param array{environment: string, admin_path: string} $options
     * @return list<string>
     */
    public function plan(array $options): array
    {
        $environment = $options['environment'];
        $adminPath = $options['admin_path'];

        return [
            '.htaccess',
            "config/{$environment}/.env",
            "db/{$environment}/database.sqlite3",
            "{$adminPath}/.htaccess",
            "{$adminPath}/index.php",
            'app/views/post/preview.php',
            'phinx.php',
        ];
    }

    /**
     * @param array{
     *     name: string,
     *     language: string,
     *     timezone: string,
     *     environment: string,
     *     admin_path: string,
     *     base_url: string
     * } $options
     * @return array{username: string, password: string}
     */
    public function install(array $options, OutputInterface $output): array
    {
        $targets = $this->plan($options);
        $this->assertTargetsDoNotExist($targets);
        $this->assertRuntimeRequirements();

        $environment = $options['environment'];
        $adminPath = $options['admin_path'];
        $databaseRelativePath = "db/{$environment}/database.sqlite3";
        $createdTargets = [];

        try {
            $this->create($createdTargets, '.htaccess', $this->projectHtaccess());
            $this->create($createdTargets, "config/{$environment}/.env", $this->env($options), 0600);
            $this->create($createdTargets, $databaseRelativePath, '', 0600);
            $this->create($createdTargets, "{$adminPath}/.htaccess", $this->htaccess($adminPath));
            $this->create($createdTargets, "{$adminPath}/index.php", $this->index($environment));
            $this->create($createdTargets, 'app/views/post/preview.php', $this->preview());
            $this->create($createdTargets, 'phinx.php', $this->phinxConfiguration());

            $this->migrate($environment, $output);

            $username = 'admin';
            $password = $this->generatePassword();
            $this->replaceInitialCredentials($databaseRelativePath, $username, $password);

            return ['username' => $username, 'password' => $password];
        } catch (Throwable $exception) {
            $failedTargets = $this->rollbackCreatedTargets($createdTargets);
            if ($failedTargets !== []) {
                throw new RuntimeException(
                    "Installation failed and some generated files could not be removed:\n- "
                    . implode("\n- ", $failedTargets),
                    0,
                    $exception
                );
            }

            throw new RuntimeException(
                'Installation failed; generated files were removed. ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function assertRuntimeRequirements(): void
    {
        if (!extension_loaded('pdo_sqlite') || !in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('Kontiki installation requires the PDO_SQLITE PHP extension.');
        }

        $migrationPath = dirname(__DIR__, 2) . '/db/migrations';
        if (!is_dir($migrationPath) || !is_readable($migrationPath)) {
            throw new RuntimeException('Kontiki migration files are not readable.');
        }
    }

    /**
     * @param list<string> $targets
     * @return list<string>
     */
    private function rollbackCreatedTargets(array $targets): array
    {
        $failedTargets = [];
        foreach (array_reverse($targets) as $target) {
            $path = $this->path($target);
            if ((is_file($path) || is_link($path)) && !@unlink($path)) {
                $failedTargets[] = $target;
            }
        }

        return $failedTargets;
    }

    /** @param list<string> $targets */
    private function assertTargetsDoNotExist(array $targets): void
    {
        $existing = [];
        foreach ($targets as $target) {
            if (file_exists($this->path($target)) || is_link($this->path($target))) {
                $existing[] = $target;
            }
        }
        if ($existing !== []) {
            throw new RuntimeException(
                "Installation refused because these targets already exist:\n- "
                . implode("\n- ", $existing)
            );
        }
    }

    private function write(string $relativePath, string $content, int $mode = 0644): void
    {
        $path = $this->path($relativePath);
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Could not create directory: {$directory}");
        }

        $handle = @fopen($path, 'x+b');
        if ($handle === false) {
            throw new RuntimeException("Could not create file: {$relativePath}");
        }

        try {
            $offset = 0;
            $length = strlen($content);
            while ($offset < $length) {
                $written = @fwrite($handle, substr($content, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException("Could not write file: {$relativePath}");
                }
                $offset += $written;
            }
            if (!@fflush($handle)) {
                throw new RuntimeException("Could not flush file: {$relativePath}");
            }
        } catch (Throwable $exception) {
            @fclose($handle);
            @unlink($path);
            throw $exception;
        }

        if (!@fclose($handle) || !@chmod($path, $mode)) {
            @unlink($path);
            throw new RuntimeException("Could not secure file permissions: {$relativePath}");
        }
    }

    /** @param list<string> $createdTargets */
    private function create(
        array &$createdTargets,
        string $relativePath,
        string $content,
        int $mode = 0644
    ): void {
        $this->write($relativePath, $content, $mode);
        $createdTargets[] = $relativePath;
    }

    private function migrate(string $environment, OutputInterface $output): void
    {
        $application = new PhinxApplication();
        $application->setAutoExit(false);
        $exitCode = $application->run(new ArrayInput([
            'command' => 'migrate',
            '--configuration' => $this->path('phinx.php'),
            '--environment' => $environment,
            '--no-interaction' => true,
        ]), $output);

        if ($exitCode !== 0) {
            throw new RuntimeException("Database migration failed with exit code {$exitCode}.");
        }
    }

    private function replaceInitialCredentials(
        string $databaseRelativePath,
        string $username,
        string $password
    ): void {
        $pdo = new PDO('sqlite:' . $this->path($databaseRelativePath));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $statement = $pdo->prepare(
            'UPDATE users SET username = :username, password = :password WHERE id = 1'
        );
        $statement->execute([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Could not secure the initial administrator account.');
        }
    }

    private function generatePassword(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    /**
     * @param array{
     *     name: string,
     *     language: string,
     *     timezone: string,
     *     environment: string,
     *     admin_path: string,
     *     base_url: string
     * } $options
     */
    private function env(array $options): string
    {
        $basePath = '/' . basename($this->projectDir) . '/' . $options['admin_path'];

        return sprintf(
            <<<'ENV'
APPLANG=%s
TIMEZONE=%s
COPYRIGHT=%s
BASEURL=%s
BASEURL_UPLOAD_DIR=/uploads
SESSION_COOKIE_SECURE=%s
BASEPATH=%s
DB_DATABASE=db/%s/database.sqlite3
UPLOADDIR=/../uploads
ALLOWED_MIME_TYPES=["image/jpeg","image/png","application/pdf"]
MAXSIZE=5000000
ADMIN_FAVICON_PATH=favicon.ico
POST_HIDE_PARENT=true
POST_HIDE_AUTHOR=true
POST_HIDE_METADATA_EXCERPT=true
POST_HIDE_METADATA_EYECATCH=true
POST_VIEW_URL=
ADMIN_THEME_COLOR="#c2c7d0"
ADMIN_THEME_BGCOLOR="#59524c"

ENV,
            $this->quoteEnv($options['language']),
            $this->quoteEnv($options['timezone']),
            $this->quoteEnv($options['name']),
            $this->quoteEnv($options['base_url']),
            parse_url($options['base_url'], PHP_URL_SCHEME) === 'https' ? 'true' : 'false',
            $this->quoteEnv($basePath),
            $options['environment']
        );
    }

    private function quoteEnv(string $value): string
    {
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    }

    private function htaccess(string $adminPath): string
    {
        $rewriteBase = '/' . basename($this->projectDir) . '/' . $adminPath . '/';

        return <<<HTACCESS
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase {$rewriteBase}
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>

<FilesMatch "^\\.">
    Require all denied
</FilesMatch>
HTACCESS;
    }

    private function projectHtaccess(): string
    {
        return <<<'HTACCESS'
Options -Indexes

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(?:app|config|db|src|vendor)(?:/|$) - [F,L,NC]
</IfModule>

<FilesMatch "^(?:\.|composer\.(?:json|lock)$|phinx\.php$)">
    Require all denied
</FilesMatch>
HTACCESS;
    }

    private function index(string $environment): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

\$app = Jidaikobo\\Kontiki\\Bootstrap::init('{$environment}', dirname(__DIR__));
Jidaikobo\\Kontiki\\Bootstrap::run(\$app);
PHP;
    }

    private function preview(): string
    {
        return <<<'PHP'
<?php

/** @var string $lang */
/** @var array{title: string, content: string} $data */
?><!doctype html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($data['title']) ?></title>
</head>
<body>
    <main>
        <h1><?= e($data['title']) ?></h1>
        <?= Jidaikobo\MarkdownExtra::defaultTransform($data['content']) ?>
    </main>
</body>
</html>
PHP;
    }

    private function phinxConfiguration(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

$migrationPath = __DIR__ . '/vendor/jidaikobo/kontiki/db/migrations';

return [
    'paths' => [
        'migrations' => $migrationPath,
        'seeds' => __DIR__ . '/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => 'sqlite',
            'name' => 'db/production/database.sqlite3',
            'suffix' => '',
        ],
        'staging' => [
            'adapter' => 'sqlite',
            'name' => 'db/staging/database.sqlite3',
            'suffix' => '',
        ],
    ],
    'version_order' => 'creation',
];
PHP;
    }

    private function path(string $relativePath): string
    {
        return $this->projectDir . '/' . ltrim($relativePath, '/');
    }
}
