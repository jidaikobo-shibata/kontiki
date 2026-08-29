<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Frontend;

use Jidaikobo\Kontiki\Bootstrap as BaseBootstrap;

class Bootstrap
{
    /**
     * Initialize frontend helpers while preserving the existing void API.
     *
     * @return void
     */
    public static function init(string $env = 'production')
    {
        // prepare timer, log and functions
        BaseBootstrap::init($env);

        // prepare frontend functions
        require __DIR__ . '/../functions/frontend.php';
    }
}
