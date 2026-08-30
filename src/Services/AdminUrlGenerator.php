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

    public function withoutBasePath(string $path): string
    {
        if ($this->basePath === '') {
            return $path;
        }

        if ($path === $this->basePath) {
            return '/';
        }

        $prefix = $this->basePath . '/';
        return str_starts_with($path, $prefix)
            ? substr($path, strlen($this->basePath))
            : $path;
    }
}
