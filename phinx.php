<?php
/*
Ussage:
/usr/bin/php vendor/bin/phinx migrate
/usr/bin/php vendor/bin/phinx rollback
php vendor/bin/phinx migrate
php vendor/bin/phinx rollback
php vendor/bin/phinx seed:run

直接叩く
php vendor/robmorgan/phinx/bin/phinx migrate --environment=production
*/

return
[
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'suffix' => '',
            'adapter' => 'sqlite',
            'name' => 'db/development/database.sqlite3',
            'charset' => 'utf8',
        ],
        'production' => [
            'suffix' => '',
            'adapter' => 'sqlite',
            'name' => 'db/production/database.sqlite3',
            'charset' => 'utf8',
        ],
    ],
    'version_order' => 'creation'
];
