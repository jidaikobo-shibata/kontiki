<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Tests\Config;

use Jidaikobo\Kontiki\Config\AdminAssetConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdminAssetConfigTest extends TestCase
{
    public function testItReadsTheConfiguredFavicon(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kontiki-favicon-');
        self::assertNotFalse($path);

        try {
            file_put_contents($path, 'favicon bytes');
            $config = new AdminAssetConfig('#fff', '#000', $path);

            self::assertSame('#fff', $config->themeColor);
            self::assertSame('#000', $config->themeBackgroundColor);
            self::assertSame('favicon bytes', $config->favicon());
        } finally {
            unlink($path);
        }
    }

    public function testItRejectsAnUnreadableFavicon(): void
    {
        $this->expectException(RuntimeException::class);

        (new AdminAssetConfig('#fff', '#000', '/missing/favicon.ico'))->favicon();
    }

    public function testItReadsOnlyAllowlistedBootstrapAssets(): void
    {
        $directory = sys_get_temp_dir() . '/kontiki-bootstrap-' . bin2hex(random_bytes(6));
        mkdir($directory . '/bootstrap', 0700, true);
        file_put_contents($directory . '/bootstrap/bootstrap.min.css', 'bootstrap css');

        try {
            $config = new AdminAssetConfig('#fff', '#000', __FILE__, $directory);
            self::assertSame('bootstrap css', $config->bootstrap('bootstrap.min.css'));

            $this->expectException(RuntimeException::class);
            $config->bootstrap('../secret');
        } finally {
            unlink($directory . '/bootstrap/bootstrap.min.css');
            rmdir($directory . '/bootstrap');
            rmdir($directory);
        }
    }
}
