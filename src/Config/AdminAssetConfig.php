<?php

namespace Jidaikobo\Kontiki\Config;

use RuntimeException;

class AdminAssetConfig
{
    public function __construct(
        public readonly string $themeColor,
        public readonly string $themeBackgroundColor,
        private string $faviconPath,
        private ?string $vendorAssetPath = null
    ) {
        $this->vendorAssetPath ??= dirname(__DIR__, 2) . '/src/assets/vendor';
    }

    public function favicon(): string
    {
        if (!is_file($this->faviconPath) || !is_readable($this->faviconPath)) {
            throw new RuntimeException('Unable to read the administration favicon.');
        }

        $content = file_get_contents($this->faviconPath);
        if ($content === false) {
            throw new RuntimeException('Unable to read the administration favicon.');
        }

        return $content;
    }

    public function bootstrap(string $filename): string
    {
        $allowed = ['bootstrap.min.css', 'bootstrap.bundle.min.js'];
        if (!in_array($filename, $allowed, true)) {
            throw new RuntimeException('Unknown Bootstrap asset.');
        }

        $path = $this->vendorAssetPath . '/bootstrap/' . $filename;
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Unable to read the Bootstrap asset.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Unable to read the Bootstrap asset.');
        }

        return $content;
    }
}
