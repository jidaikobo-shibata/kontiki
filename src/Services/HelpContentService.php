<?php

namespace Jidaikobo\Kontiki\Services;

use InvalidArgumentException;
use RuntimeException;

class HelpContentService
{
    private string $localeDirectory;
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(
        string $localeDirectory,
        private string $language,
        ?AdminUrlGenerator $adminUrlGenerator = null
    ) {
        $this->localeDirectory = rtrim($localeDirectory, DIRECTORY_SEPARATOR);
        $this->adminUrlGenerator = $adminUrlGenerator ?? new AdminUrlGenerator('');

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $language)) {
            throw new InvalidArgumentException('The help language contains invalid characters.');
        }
    }

    public function renderHelp(): string
    {
        $path = $this->resolve('help.php');
        $dashboardUrl = $this->adminUrlGenerator->path('/dashboard');
        $postCreateUrl = $this->adminUrlGenerator->path('/post/create');
        $markdownHelpUrl = $this->adminUrlGenerator->path('/help/markdown');

        ob_start();
        try {
            require $path;
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }

    public function readMarkdownHelp(): string
    {
        $content = file_get_contents($this->resolve('markdown-help.php'));
        if ($content === false) {
            throw new RuntimeException('Unable to read the Markdown help file.');
        }

        return $content;
    }

    private function resolve(string $filename): string
    {
        $path = $this->localeDirectory
            . DIRECTORY_SEPARATOR
            . $this->language
            . DIRECTORY_SEPARATOR
            . 'file'
            . DIRECTORY_SEPARATOR
            . $filename;

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("The help file is not readable: {$filename}");
        }

        return $path;
    }
}
