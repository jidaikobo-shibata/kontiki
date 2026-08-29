<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Phinx\Console\PhinxApplication;

function prompt(
    string $message,
    ?string $default = null,
    ?array $allowedValues = null,
    ?callable $validator = null
): string {
    while (true) {
        echo $message;
        if ($default !== null) {
            echo " [$default]";
        }
        if (is_array($allowedValues)) {
            echo " (" . implode(" / ", $allowedValues) . ")";
        }
        echo ": ";

        $line = fgets(STDIN);
        if ($line === false) {
            throw new RuntimeException('Installation input ended unexpectedly.');
        }
        $input = trim($line);

        // use default
        if ($input === "" && $default !== null) {
            $input = $default;
        }

        // required
        if ($input === "") {
            echo "Input is required, please try again.\n";
            continue;
        }

        // check value
        if ($allowedValues !== null && !in_array($input, $allowedValues, true)) {
            echo "Invalid choice. Please enter one of: " . implode(", ", $allowedValues) . "\n";
            continue;
        }

        if ($validator !== null) {
            $validationError = $validator($input);
            if ($validationError !== null) {
                echo $validationError . "\n";
                continue;
            }
        }

        return $input;
    }
}

function quoteEnvValue(string $value): string
{
    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException("Failed to create directory: $path");
    }
}

function writeRequiredFile(string $path, string $content, string $label): void
{
    if (file_put_contents($path, $content, LOCK_EX) === false) {
        throw new RuntimeException("Failed to create $label at $path");
    }
}

$basePath = basename(dirname(__DIR__));

echo "Welcome to Kontiki CMS Setup.\n\n";

// Prompt the user for input
do {
    $projectName = prompt(
        "Project name",
        "My CMS",
        null,
        static function (string $value): ?string {
            if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                return 'Project name must not contain control characters.';
            }
            return strlen($value) <= 200
                ? null
                : 'Project name must be 200 bytes or fewer.';
        }
    );
    $projectLang = prompt("Project language", "en", ['en', 'ja']);
    $projectTimezone = prompt(
        "Project timezone",
        date_default_timezone_get(),
        null,
        static fn(string $value): ?string => in_array(
            $value,
            timezone_identifiers_list(),
            true
        ) ? null : 'Enter a valid PHP timezone identifier.'
    );
    $projectEnv = prompt("Project environment", "production", ['staging', 'production']);
    $projectAdminDir = prompt(
        "Project Administration dir",
        "admin",
        null,
        static fn(string $value): ?string => preg_match(
            '/\A[A-Za-z0-9][A-Za-z0-9_-]{0,63}\z/D',
            $value
        ) === 1 ? null : 'Use 1-64 letters, numbers, underscores, or hyphens.'
    );
    $projectBaseurl = prompt(
        "Base URL (ex: https://example.com)",
        null,
        null,
        static function (string $value): ?string {
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                return 'Enter a valid absolute URL.';
            }
            $scheme = parse_url($value, PHP_URL_SCHEME);
            return in_array($scheme, ['http', 'https'], true)
                ? null
                : 'Base URL must use http or https.';
        }
    );

    echo "\nPlease check your input:\n";
    echo "----------------------------------\n";
    echo " Project Name     : $projectName\n";
    echo " Project Language : $projectLang\n";
    echo " Project Timezone : $projectTimezone\n";
    echo " Project Environment : $projectEnv\n";
    echo " Project Admin Dir : $projectAdminDir\n";
    echo " Project URL : $projectBaseurl\n";
    echo "----------------------------------\n";

    $confirm = prompt("Are these okay?", "yes", ['yes', 'no']);
} while ($confirm !== "yes");

$envProjectLang = quoteEnvValue($projectLang);
$envProjectTimezone = quoteEnvValue($projectTimezone);
$envProjectName = quoteEnvValue($projectName);
$envProjectBaseurl = quoteEnvValue(rtrim($projectBaseurl, '/'));

// Create `.env`
$envContent = <<<EOL
# Application language setting
APPLANG=$envProjectLang

# Timezone
TIMEZONE=$envProjectTimezone

# Copyright text used in the application
COPYRIGHT=$envProjectName

# Base URL
BASEURL=$envProjectBaseurl

# Upload Directory
BASEURL_UPLOAD_DIR=/uploads

# Base path for Slim's setBasePath
BASEPATH=/$basePath/$projectAdminDir

# Database connection information
DB_DATABASE=db/$projectEnv/database.sqlite3

# Upload directory
# Relative path from the project root
UPLOADDIR=/../uploads

# Allowed MIME types (in JSON format)
ALLOWED_MIME_TYPES=["image/jpeg","image/png","application/pdf"]

# Maximum file upload size (in bytes)
MAXSIZE=5000000

# admin favicon path
ADMIN_FAVICON_PATH=favicon.ico

# Post Default Settings
POST_HIDE_PARENT=true
POST_HIDE_AUTHOR=true
POST_HIDE_METADATA_EXCERPT=true
POST_HIDE_METADATA_EYECATCH=true
POST_VIEW_URL=

# admin theme color
ADMIN_THEME_COLOR="#c2c7d0"
ADMIN_THEME_BGCOLOR="#59524c"
EOL;

$envFilePath = __DIR__ . "/../config/$projectEnv/.env";
writeRequiredFile($envFilePath, $envContent, '`.env` file');
echo "\n `.env` file has been created at $envFilePath!\n";

// Ensure the database directory exists
$dbDir = __DIR__ . "/../db/$projectEnv";
if (!is_dir($dbDir)) {
    ensureDirectory($dbDir);
    echo "\n Created database directory at $dbDir\n";
}

// Ensure SQLite database file exists
$dbFile = "$dbDir/database.sqlite3";
if (!file_exists($dbFile)) {
    if (!touch($dbFile)) {
        throw new RuntimeException("Failed to create database file: $dbFile");
    }
    echo "\n Created SQLite database file at $dbFile\n";
}

// Ensure the admin dir directory exists
$adminDir = __DIR__ . "/../$projectAdminDir";
if (!is_dir($adminDir)) {
    ensureDirectory($adminDir);
    echo "\n Created administration directory at $adminDir\n";
}

// Create `.htaccess`
$htaccessContent = sprintf(
    "Order allow,deny
Allow from all

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /%s/%s/

    RewriteCond %%{REQUEST_FILENAME} !-f
    RewriteCond %%{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>

# anti directly-index
Options -Indexes

# deny for dot file
<FilesMatch \"^\\.\">
    Require all denied
</FilesMatch>
",
    $basePath,
    $projectAdminDir
);

$htaccessFilePath = __DIR__ . "/../{$projectAdminDir}/.htaccess";
writeRequiredFile($htaccessFilePath, $htaccessContent, '`.htaccess` file');
echo "\n `.htaccess` file has been created at $htaccessFilePath!\n";

// Create `index`
$indexContent = sprintf(
    "<?php

// autoload
require __DIR__ . '/../vendor/autoload.php';

// Execute Slim
\$env = \"%s\";
\$app = Jidaikobo\Kontiki\Bootstrap::init(\$env);
Jidaikobo\Kontiki\Bootstrap::run(\$app);
",
    $projectEnv
);

$indexFilePath = __DIR__ . "/../{$projectAdminDir}/index.php";
writeRequiredFile($indexFilePath, $indexContent, '`index.php` file');
echo "\n `.index` file has been created at $indexFilePath!\n";

// Run Phinx migrations without `system()`
try {
    echo "\nRunning database migrations for `$projectEnv` environment...\n";

    $phinxApp = new PhinxApplication();
    $phinxApp->setAutoExit(false);

    $input = new ArrayInput([
        'command' => 'migrate',
        '-e' => $projectEnv,
    ]);
    $output = new ConsoleOutput();

    $exitCode = $phinxApp->run($input, $output);

    if ($exitCode !== 0) {
        throw new RuntimeException("Phinx migration failed with exit code: $exitCode");
    }

    echo "\n Database migrations completed successfully!\n";
} catch (Throwable $e) {
    echo "\n Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n Installation complete!\n";
