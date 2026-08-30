<?php

namespace Jidaikobo\Kontiki\Services;

class AdminUrlGenerator
{
    private string $basePath;

    public function __construct(string $basePath)
    {
        $normalized = '/' . trim($basePath, '/');
        $this->basePath = $normalized === '/' ? '' : $normalized;
    }

    public function path(string $path): string
    {
        if ($path === '') {
            return $this->basePath === '' ? '/' : $this->basePath;
        }

        return $this->basePath . '/' . ltrim($path, '/');
    }

    public function basePath(): string
    {
        return $this->basePath;
    }
}
