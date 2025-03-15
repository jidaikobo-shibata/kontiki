<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Phinx\Console\PhinxApplication;

function prompt($message, $default = null, $allowedValues = null) {
    while (true) {
        echo $message;
        if ($default !== null) {
            echo " [$default]";
        }
        if (is_array($allowedValues)) {
            echo " (" . implode(" / ", $allowedValues) . ")";
        }
        echo ": ";

        $input = trim(fgets(STDIN));

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

        return $input;
    }
}

$basePath = basename(dirname(__DIR__));

echo "Welcome to Kontiki CMS Setup.\n\n";

// Prompt the user for input
do {
    $projectName = prompt("Project name", "My CMS");
    $projectLang = prompt("Project language", "en", ['en', 'ja']);
    $projectTimezone = prompt("Project timezone", date_default_timezone_get());
    $projectEnv = prompt("Project environment", "production", ['staging', 'production']);
    $projectAdminDir = prompt("Project Administration dir", "admin");
    $projectBaseurl = prompt("Base URL (ex: https://example.com)");

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

// Create `.env`
$envContent = <<<EOL
# Application language setting
LANG="$projectLang"

# Timezone
TIMEZONE="$projectTimezone"

# Copyright text used in the application
COPYRIGHT="$projectName"

# Base URL
BASEURL="$projectBaseurl"

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

# admin theme color
ADMIN_THEME_COLOR="#c2c7d0"
ADMIN_THEME_BGCOLOR="#59524c"
EOL;

$envFilePath = __DIR__ . "/../config/$projectEnv/.env";
if (file_put_contents($envFilePath, $envContent) === false) {
    echo "\n Error: Failed to create `.env` file at $envFilePath!\n";
    exit(1);
}
echo "\n `.env` file has been created at $envFilePath!\n";

// Ensure the database directory exists
$dbDir = __DIR__ . "/../db/$projectEnv";
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
    echo "\n Created database directory at $dbDir\n";
}

// Ensure SQLite database file exists
$dbFile = "$dbDir/database.sqlite3";
if (!file_exists($dbFile)) {
    touch($dbFile);
    echo "\n Created SQLite database file at $dbFile\n";
}

// Ensure the admin dir directory exists
$adminDir = __DIR__ . "/../$projectAdminDir";
if (!is_dir($adminDir)) {
    mkdir($adminDir, 0777, true);
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
if (file_put_contents($htaccessFilePath, $htaccessContent) === false) {
    echo "\n Error: Failed to create `.htaccess` file at $htaccessFilePath!\n";
    exit(1);
}
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
if (file_put_contents($indexFilePath, $indexContent) === false) {
    echo "\n Error: Failed to create `.index` file at $indexFilePath!\n";
    exit(1);
}
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
} catch (Exception $e) {
    echo "\n Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n Installation complete!\n";
