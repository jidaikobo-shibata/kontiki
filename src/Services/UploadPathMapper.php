<?php

declare(strict_types=1);

namespace Jidaikobo\Kontiki\Services;

use InvalidArgumentException;

final class UploadPathMapper
{
    private string $baseUrl;
    private string $baseDirectory;

    /** @var array{scheme: string, host: string, port?: int, path: string} */
    private array $baseUrlParts;

    public function __construct(string $baseUrl, string $baseDirectory)
    {
        $parts = parse_url($baseUrl);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Upload base URL must be absolute.');
        }

        $basePath = $this->normalizeAbsolutePath($parts['path'] ?? '/');
        $directory = $this->normalizeAbsolutePath($baseDirectory);
        if ($basePath === null || $directory === null) {
            throw new InvalidArgumentException('Upload paths must be absolute.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->baseDirectory = $directory;
        $this->baseUrlParts = [
            'scheme' => strtolower($parts['scheme']),
            'host' => strtolower($parts['host']),
            'path' => $basePath,
        ];
        if (isset($parts['port'])) {
            $this->baseUrlParts['port'] = $parts['port'];
        }
    }

    public function pathToUrl(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if ($this->looksLikeUrl($path)) {
            $path = $this->urlToPath($path);
            if ($path === '') {
                return '';
            }
        }

        $normalizedPath = $this->normalizeAbsolutePath($path);
        if (
            $normalizedPath === null
            || !$this->isWithin($normalizedPath, $this->baseDirectory)
        ) {
            return '';
        }

        $tail = ltrim(substr($normalizedPath, strlen($this->baseDirectory)), '/');

        return $this->baseUrl . ($tail !== '' ? '/' . $tail : '');
    }

    public function urlToPath(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (!$this->looksLikeUrl($url)) {
            $path = $this->normalizeAbsolutePath($url);
            return $path !== null && $this->isWithin($path, $this->baseDirectory)
                ? $path
                : '';
        }

        $parts = parse_url($url);
        if (
            !is_array($parts)
            || !isset($parts['scheme'], $parts['host'])
            || strtolower($parts['scheme']) !== $this->baseUrlParts['scheme']
            || strtolower($parts['host']) !== $this->baseUrlParts['host']
            || ($parts['port'] ?? null) !== ($this->baseUrlParts['port'] ?? null)
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return '';
        }

        $urlPath = rawurldecode($parts['path'] ?? '/');
        $normalizedPath = $this->normalizeAbsolutePath($urlPath);
        $basePath = $this->baseUrlParts['path'];
        if ($normalizedPath === null || !$this->isWithin($normalizedPath, $basePath)) {
            return '';
        }

        $tail = ltrim(substr($normalizedPath, strlen($basePath)), '/');

        return $this->baseDirectory
            . ($tail !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tail) : '');
    }

    private function looksLikeUrl(string $value): bool
    {
        $parts = parse_url($value);

        return is_array($parts) && (isset($parts['scheme']) || isset($parts['host']));
    }

    private function isWithin(string $path, string $basePath): bool
    {
        if ($basePath === '/') {
            return str_starts_with($path, '/');
        }

        return $path === $basePath || str_starts_with($path, $basePath . '/');
    }

    private function normalizeAbsolutePath(string $path): ?string
    {
        if (str_contains($path, "\0")) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        if (!str_starts_with($path, '/')) {
            return null;
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    return null;
                }
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return '/' . implode('/', $segments);
    }
}
