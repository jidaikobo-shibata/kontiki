<?php

namespace Jidaikobo\Kontiki\Config;

use RuntimeException;

class AdminAssetConfig
{
    public function __construct(
        public readonly string $themeColor,
        public readonly string $themeBackgroundColor,
        private string $faviconPath
    ) {
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
}
